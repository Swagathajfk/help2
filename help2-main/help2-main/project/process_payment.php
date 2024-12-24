<?php
include('db_connect.php');
session_start();

// Only freelancers who created the job can pay
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'freelancer') {
    echo json_encode(['success' => false, 'message' => 'Only freelancers can make payments']);
    exit;
}

// Validate required fields
$required_fields = ['job_id', 'amount', 'card_number', 'expiry_date', 'cvv', 'cardholder_name', 'billing_address'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }
}

$job_id = intval($_POST['job_id']);
$amount = floatval($_POST['amount']);
$card_last_four = substr(preg_replace('/[^0-9]/', '', $_POST['card_number']), -4);
$billing_address = htmlspecialchars($_POST['billing_address']);

try {
    $conn->begin_transaction();

    // Check if this is the freelancer's job and has an accepted client
    $job_sql = "SELECT j.*, ja.client_id 
                FROM jobs j 
                JOIN job_applications ja ON j.id = ja.job_id 
                WHERE j.id = ? 
                AND j.freelancer_id = ? 
                AND ja.status = 'accepted' 
                AND j.payment_status = 'unpaid'";

    $stmt = $conn->prepare($job_sql);
    $stmt->bind_param("ii", $job_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Invalid job or payment already processed');
    }

    $job = $result->fetch_assoc();
    
    // Create payment record
    $payment_sql = "INSERT INTO payments (
        job_id, 
        client_id, 
        freelancer_id, 
        amount, 
        status,
        transaction_id, 
        payment_method, 
        billing_address, 
        card_last_four
    ) VALUES (?, ?, ?, ?, 'completed', ?, 'card', ?, ?)";

    $transaction_id = 'TXN' . time() . rand(1000, 9999);
    
    $stmt = $conn->prepare($payment_sql);
    $stmt->bind_param("iiidsss", 
        $job_id,
        $job['client_id'],
        $_SESSION['user_id'],
        $amount,
        $transaction_id,
        $billing_address,
        $card_last_four
    );
    $stmt->execute();
    
    // Update job status
    $update_sql = "UPDATE jobs SET 
                   payment_status = 'paid',
                   payment_date = NOW(),
                   transaction_id = ?
                   WHERE id = ?";
    
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $transaction_id, $job_id);
    $stmt->execute();

    // Add notification for client
    $notify_sql = "INSERT INTO notifications (user_id, message, user_type) 
                   VALUES (?, ?, 'client')";
    
    $notify_message = "Payment received for job #" . $job_id;
    $stmt = $conn->prepare($notify_sql);
    $stmt->bind_param("is", $job['client_id'], $notify_message);
    $stmt->execute();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Payment processed successfully',
        'transaction_id' => $transaction_id
        // Removed redirect_url
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>
