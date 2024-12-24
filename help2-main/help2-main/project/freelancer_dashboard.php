<?php
session_start();
require_once 'db_connect.php';

function calculateProfileCompleteness($user_data) {
    $total_fields = 10;
    $filled_fields = 0;

    $fields_to_check = [
        'first_name', 'surname', 'email', 'mobile', 
        'address1', 'postcode', 'state', 'area', 
        'country', 'education'
    ];

    foreach ($fields_to_check as $field) {
        if (!empty($user_data[$field])) {
            $filled_fields++;
        }
    }

    $completeness = floor(($filled_fields / $total_fields) * 100);

    $status = 'Incomplete';
    if ($completeness <= 30) {
        $status = 'Very Low';
    } elseif ($completeness <= 50) {
        $status = 'Low';
    } elseif ($completeness <= 70) {
        $status = 'Almost Complete';
    } elseif ($completeness <= 99) {
        $status = 'Nearly Complete';
    } else {
        $status = 'Complete';
    }

    return [
        'percentage' => $completeness,
        'status' => $status
    ];
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT first_name, surname, email, mobile, address1, address2, postcode, state, area, country, education FROM users WHERE id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
$stmt->close();

$profile_data = calculateProfileCompleteness($userData);


// Change freelancer_id to client_id in job applications count query
$stmt = $conn->prepare("SELECT COUNT(*) as job_applications_count FROM job_applications WHERE client_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$job_applications = $result->fetch_assoc();
$stmt->close();

// Change freelancer_id to freelancer_id in jobs count query - this one is correct 
$stmt = $conn->prepare("SELECT COUNT(*) as active_job_posts_count FROM jobs WHERE freelancer_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$job_posts = $result->fetch_assoc();
$stmt->close();

// Change freelancer_id to freelancer_id in job listings query - this one is correct
$stmt = $conn->prepare("SELECT id, title, job_type, reward, start_date FROM jobs WHERE freelancer_id = ? ORDER BY id DESC LIMIT 3");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$job_listings_result = $stmt->get_result();
$job_listings = [];
while ($job = $job_listings_result->fetch_assoc()) {
    $job_listings[] = $job;
}
$stmt->close();


$userData['job_applications_count'] = $job_applications['job_applications_count'];
$userData['active_job_posts_count'] = $job_posts['active_job_posts_count'];
$userData['profile_completeness'] = $profile_data['percentage'];
$userData['profile_status'] = $profile_data['status'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Freelancer Dashboard</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@coreui/icons/css/coreui-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/coreui@4.1.0/dist/css/coreui.min.css" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script src="https://unpkg.com/react@17/umd/react.development.js"></script>
    <script src="https://unpkg.com/react-dom@17/umd/react-dom.development.js"></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>

    <style>
        :root {
            --sidebar-width: 280px;
            --sidebar-bg: #1c1c1e;
    --sidebar-hover: #2c2c2e;
    --card-bg: #ffffff;
    --text-primary: #000000;
    --text-secondary: #6e6e73;
    --accent-blue: #0071e3;
    --accent-green: #00b06b;
    --accent-yellow: #ffd60a;
    --shadow-sm: rgba(0, 0, 0, 0.05);
    --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);
}
        body {
            background-color: var(--page-bg);
            font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Text', 'Segoe UI', sans-serif;
            -webkit-font-smoothing: antialiased;
            color: var(--text-primary);
        }

        .wrapper {
            display: flex;
            min-height: 100vh;
            width: 100%;
            background-color: var(--page-bg);
        }

        .sidebar {
    background: var(--sidebar-bg);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    width: var(--sidebar-width);  /* Use the variable */
    min-width: var(--sidebar-width);  /* Add this line */
    min-height: 100vh;
    position: fixed;
    padding: 1.5rem 1rem;
    border-right: 1px solid rgba(255, 255, 255, 0.1);
    z-index: 100;
}

.content {
    margin-left: var(--sidebar-width);
    padding: 2rem;
    width: calc(100% - var(--sidebar-width));
    background: #f5f5f7;
    min-height: 100vh;
    display: flex;
    justify-content: center;
}

        /* Profile Completeness Card */
        .profile-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
        }

        .profile-card h5 {
            color: var(--text-primary);
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .progress-container {
            background: #f3f4f6;
            border-radius: 8px;
            height: 8px;
            overflow: hidden;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .progress-bar {
            background: linear-gradient(90deg, #34d399, #10b981);
            height: 100%;
            border-radius: 8px;
            transition: width 0.5s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        /* Stats Cards Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .stat-card {
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
            transition: transform 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
        }

        /* Container max-width */
        .dashboard-container {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            padding: 0 2rem;
        }

        /* Smooth transitions */
        * {
            transition: background-color 0.2s ease,
                      transform 0.2s ease,
                      box-shadow 0.2s ease;
        }

        :root {
            --sidebar-bg: #f4f4f5;
            --sidebar-hover: #ffffff;
            --sidebar-text: #374151;
            --sidebar-active: #ffffff;
            --sidebar-active-text: #0061e4;
            --sidebar-active-bg: #ffffff;
            --sidebar-border: #e5e7eb;
            --shadow: rgba(0, 0, 0, 0.05);
        }

        body {
            background-color: #ffffff;
            font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Text', 'Segoe UI', sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        .sidebar {
            background: var(--sidebar-bg);
            width: 240px;
            height: 100vh;
            position: fixed;
            padding: 1.5rem 0.75rem;
            border-right: 1px solid var(--sidebar-border);
        }

        .nav-item {
            margin-bottom: 0.25rem;
        }

        .nav-link {
    color: rgba(255, 255, 255, 0.85);
    padding: 0.9rem 1.25rem;
    margin: 0.25rem 0.75rem;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 500;
    letter-spacing: -0.01em;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    transition: all 0.2s cubic-bezier(0.65, 0, 0.35, 1);
}

.nav-link i {
    font-size: 1.1rem;
    opacity: 0.75;
}

        .nav-link:hover {
    background: var(--sidebar-hover);
    transform: translateX(4px);
}

.nav-link.active {
    background: var(--accent-blue);
    color: white;
    font-weight: 600;
}

        .nav-link.active i {
            opacity: 1;
        }

        .content {
            margin-left: 240px;
            padding: 2rem;
            background: #ffffff;
        }

        /* Danger link special styling */
        .nav-link.text-danger {
    color: #ff453a;
}

.nav-link.text-danger:hover {
    background: rgba(255, 69, 58, 0.1);
}

        @media (prefers-color-scheme: dark) {
            :root {
                --sidebar-bg: #1c1c1e;
                --sidebar-hover: #2c2c2e;
                --sidebar-text: #a0a0a5;
                --sidebar-active: #2c2c2e;
                --sidebar-active-text: #0a84ff;
                --sidebar-border: #2c2c2e;
            }
            
            body {
                background-color: #000000;
                color: #ffffff;
            }
        }

        :root {
            --sidebar-bg: #1c1c1e;
            --sidebar-hover: #2c2c2e;
            --card-bg: #ffffff;
            --text-primary: #000000;
            --text-secondary: #6e6e73;
            --accent-blue: #0071e3;
            --accent-green: #00b06b;
            --accent-yellow: #ffd60a;
            --shadow-sm: rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        body {
            background-color: #f5f5f7;
            font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', sans-serif;
            -webkit-font-smoothing: antialiased;
            letter-spacing: -0.01em;
        }

        /* Enhanced Profile Card */
        .profile-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 1.75rem;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
        }

        .profile-card h5 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1.25rem;
            letter-spacing: -0.02em;
        }

        /* Improved Progress Bar */
        .progress-container {
            background: #f2f2f7;
            border-radius: 8px;
            height: 8px;
            overflow: hidden;
        }

        .progress-bar {
            background: linear-gradient(90deg, var(--accent-blue), #40a9ff);
            height: 100%;
            border-radius: 8px;
            transition: width 0.6s cubic-bezier(0.65, 0, 0.35, 1);
        }

        /* Enhanced Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .stat-card {
            border-radius: 16px;
            padding: 1.75rem;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s cubic-bezier(0.65, 0, 0.35, 1);
        }

        .stat-card:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.12);
        }

        /* Card Typography */
        .stat-card h4 {
            font-size: 1.5rem;
            font-weight: 600;
            letter-spacing: -0.03em;
            margin-bottom: 1rem;
            color: #ffffff;
        }

        .stat-card p {
            font-size: 1.1rem;
            line-height: 1.5;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 1.5rem;
            font-weight: 400;
        }

        /* Enhanced Sidebar */
        .sidebar {
            background: var(--sidebar-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        .nav-link {
            font-size: 0.9rem;
            font-weight: 500;
            letter-spacing: -0.01em;
            padding: 0.9rem 1.25rem;
            margin: 0.25rem 0.75rem;
            border-radius: 8px;
            transition: all 0.2s cubic-bezier(0.65, 0, 0.35, 1);
        }

        .nav-link:hover {
            background: var(--sidebar-hover);
            transform: translateX(4px);
        }

        .nav-link.active {
            background: var(--accent-blue);
            color: white;
            font-weight: 600;
        }

        /* Button Styling */
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            letter-spacing: -0.01em;
            transition: all 0.2s cubic-bezier(0.65, 0, 0.35, 1);
        }

        /* Card Gradients */
        .bg-gradient-blue {
            background: linear-gradient(135deg, #0062e3, #33a5ff);
        }

        .bg-gradient-green {
            background: linear-gradient(135deg, #00b06b, #33d494);
        }

        .bg-gradient-yellow {
            background: linear-gradient(135deg, #ffd60a, #ffdf33);
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Changed this line to remove bg-dark class -->
        <div class="sidebar">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="freelancer_dashboard.php" aria-label="Dashboard">
                        <i class="cil-speedometer" aria-hidden="true"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="create_job.php" aria-label="Create Job">
                        <i class="cil-plus" aria-hidden="true"></i> Create Job
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="jobs.php" aria-label="Job Listings">
                        <i class="cil-briefcase" aria-hidden="true"></i> Job Listings
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="my_applications.php" aria-label="My Applications">
                        <i class="cil-task" aria-hidden="true"></i> My Applications
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="edit_profile.php" aria-label="My Profile">
                        <i class="cil-user" aria-hidden="true"></i> My Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="my_job_posts.php" aria-label="My Job Posts">
                        <i class="cil-folder" aria-hidden="true"></i> My Job Posts
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="notifications.php" aria-label="Notifications">
                        <i class="cil-bell" aria-hidden="true"></i> Notifications
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-danger" href="logout.php" aria-label="Logout">
                        <i class="cil-account-logout" aria-hidden="true"></i> Logout
                    </a>
                </li>
            </ul>
        </div>

        <div class="content">
    <div class="dashboard-container">
        <div id="react-dashboard-root"></div>
    </div>
</div>

    <script type="text/babel">
        const FreelancerDashboard = ({ initialData, jobListings }) => {
            const [profileCompleteness, setProfileCompleteness] = React.useState(initialData.profile_completeness);
            const [profileStatus, setProfileStatus] = React.useState(initialData.profile_status);

            const JobListingsPreview = () => {
                if (!jobListings || jobListings.length === 0) {
                    return (
                        <div className="text-center py-8 bg-white rounded-lg shadow">
                            <div 
                                className="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-current border-r-transparent align-[-0.125em] motion-reduce:animate-[spin_1.5s_linear_infinite]"
                                role="status"
                            />
                            <p className="mt-4 text-gray-600">No job listings available</p>
                        </div>
                    );
                }

                return (
                    <div className="bg-white rounded-lg shadow p-6">
                        <h5 className="text-lg font-semibold mb-4">Recent Job Listings</h5>
                        <div className="space-y-4">
                            {jobListings.map((job) => (
                                <div key={job.id} className="border-b pb-3 last:border-b-0">
                                    <div className="flex justify-between items-center">
                                        <div>
                                            <h6 className="font-medium text-gray-800">{job.title}</h6>
                                            <p className="text-sm text-gray-500">
                                                {job.job_type} | ₹{Number(job.reward).toLocaleString()}
                                            </p>
                                        </div>
                                        <a 
                                            href={`jobs.php?job_id=${job.id}`} 
                                            className="text-blue-500 hover:text-blue-700 text-sm"
                                        >
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            ))}
                        </div>
                        <div className="text-center mt-4">
                            <a 
                                href="my_job_posts.php" 
                                className="text-blue-500 hover:text-blue-700 text-sm"
                            >
                                View All Job Posts
                            </a>
                        </div>
                    </div>
                );
            };

            const DashboardCard = ({ title, description, buttonText, href, bgClass }) => (
                <div className={`stat-card ${bgClass}`}>
                    <h4 className="text-xl font-bold mb-3">{title}</h4>
                    <p className="mb-4">{description}</p>
                    <a href={href} 
                       className="btn bg-white/90 hover:bg-white text-black font-semibold 
                                transition-all duration-200 ease-out hover:scale-105">
                        {buttonText}
                    </a>
                </div>
            );

            return (
                <div>
                    <div className="profile-card mb-4">
                        <h5>Profile Completeness</h5>
                        <div className="progress-container">
                            <div 
                                className="progress-bar"
                                style={{ width: `${profileCompleteness}%` }}
                            />
                        </div>
                        <div className="mt-2">
                            <span className="text-sm font-medium text-gray-700">
                                {profileStatus} ({profileCompleteness}%)
                            </span>
                        </div>
                    </div>

                    <div className="stats-grid">
                        <DashboardCard 
                            title="Job Applications" 
                            description={`You have ${initialData.job_applications_count} new job applications to review.`}
                            buttonText="View Applications"
                            href="my_applications.php"
                            bgClass="bg-gradient-blue"
                        />
                        <DashboardCard 
                            title="Job Posts" // Changed from "Active Job Posts"
                            description={`You have created ${initialData.active_job_posts_count} total job postings.`} // Updated text
                            buttonText="Manage Posts"
                            href="my_job_posts.php"
                            bgClass="bg-gradient-green"
                        />
                        <DashboardCard 
                            title="Profile Status" 
                            description={`Your profile is ${profileCompleteness}% complete.`}
                            buttonText="Edit Profile"
                            href="edit_profile.php"
                            bgClass="bg-gradient-yellow"
                        />
                    </div>

                    <JobListingsPreview />
                </div>
            );
        };

        ReactDOM.render(
            <React.StrictMode>
                <FreelancerDashboard 
                    initialData={{
                        username: "<?php echo $userData['first_name'] . ' ' . $userData['surname']; ?>",
                        profile_completeness: <?php echo $userData['profile_completeness']; ?>,
                        job_applications_count: <?php echo $userData['job_applications_count']; ?>,
                        active_job_posts_count: <?php echo $userData['active_job_posts_count']; ?>,
                        profile_status: "<?php echo $userData['profile_status']; ?>"
                    }} 
                    jobListings={<?php echo json_encode($job_listings); ?>}
                />
            </React.StrictMode>,
            document.getElementById('react-dashboard-root')
        );
    </script>
</body>
</html>