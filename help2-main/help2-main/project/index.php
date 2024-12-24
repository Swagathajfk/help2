<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Kat - Elite Freelance Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #0a0a0a;
            --secondary: #1a1a1a;
            --accent: #2a2a2a;
            --gold: #d4af37;
            --silver: #c0c0c0;
            --text: #333;
        }

        body {
            padding-top: 70px; /* Should match navbar height */
            font-family: 'Inter', -apple-system, sans-serif;
            color: var(--text);
            line-height: 1.5;
            background: #ffffff;
        }

        .navbar {
            background: rgba(10, 10, 10, 0.98) !important;
            backdrop-filter: blur(10px);
            padding: 0.5rem 0;
            height: 70px; /* Fixed height for navbar */
        }

        .navbar-brand {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--gold) !important;
        }

        .nav-link {
            font-weight: 500;
            color: white !important;
            padding: 1rem 1.5rem !important;
            transition: color 0.2s ease;
        }

        .nav-link:hover {
            color: var(--gold) !important;
        }

        .hero-section {
            background: var(--primary);
            height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            padding: 0;
            margin-top: 0;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(212, 175, 55, 0.1) 0%, transparent 100%);
            opacity: 0.1;
        }

        .display-1 {
            font-weight: 800;
            font-size: 4rem;
            line-height: 1.1;
            margin-bottom: 1.5rem;
        }

        .lead {
            font-size: 1.2rem;
            font-weight: 400;
            opacity: 0.9;
        }

        .btn {
            padding: 0.8rem 2rem;
            font-weight: 600;
            border-radius: 4px;
            transition: all 0.2s ease;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn-outline-light {
            color: white;
            border: 1px solid white;
            background: transparent;
        }

        .btn-outline-light:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--gold);
        }

        .btn-primary {
            background: var(--gold);
            border: none;
            color: var(--primary);
        }

        .btn-primary:hover {
            background: #c4a032;
            transform: translateY(-1px);
            color: var(--primary);
        }

        .animated-design {
            position: relative;
            width: 100%;
            height: 400px;
            background: var(--primary);
            border-radius: 12px;
            overflow: hidden;
        }

        .animated-design::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                45deg,
                var(--gold) 0%,
                var(--primary) 25%,
                var(--gold) 50%,
                var(--primary) 75%,
                var(--gold) 100%
            );
            animation: shimmer 5s linear infinite;
        }

        @keyframes shimmer {
            0% {
                transform: translate(-50%, -50%) rotate(0deg);
            }
            100% {
                transform: translate(-50%, -50%) rotate(360deg);
            }
        }

        .feature-card {
            padding: 2rem;
            border-radius: 8px;
            background: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            transition: all 0.2s ease;
            height: 100%;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .feature-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }

        .feature-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            margin-bottom: 1rem;
            background: var(--primary);
            color: var(--gold);
            font-size: 1.25rem;
        }

        .testimonial-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.05);
            height: 100%;
        }

        .contact-form {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .form-control {
            padding: 0.8rem;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
        }

        .form-control:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 2px rgba(212, 175, 55, 0.1);
        }

        .success-message {
            display: none;
            background: #28a745;
            color: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 1rem;
            text-align: center;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.2);
            transform: translateY(10px);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .success-message.fade-in {
            transform: translateY(0);
            opacity: 1;
        }

        footer {
            background: var(--primary);
            padding: 1.5rem 0;
            color: white;
        }

        .section-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--primary);
        }

        .section-subtitle {
            font-size: 1rem;
            color: var(--text);
            opacity: 0.8;
            max-width: 600px;
            margin: 0 auto 2rem;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.5s ease forwards;
        }

        @media (max-width: 768px) {
            .display-1 {
                font-size: 2.5rem;
            }
            .hero-section {
                padding: 4rem 0;
                min-height: auto;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#home">
                <i class="bi bi-diamond-fill me-2"></i>Kat
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="#testimonials">Success</a></li>
                    <li class="nav-item"><a class="btn btn-primary ms-3" href="login.php">Get Started</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero-section" id="home">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-1 text-white mb-4">Post Tasks.<br>Get Paid.<br>Repeat. 💰</h1>
                    <p class="lead text-white-50 mb-4">Where side hustles become main hustles. Join the elite freelance marketplace that actually pays the bills.</p>
                    <div class="d-flex gap-2">
                        <a href="register.php" class="btn btn-primary" onclick="window.location.href='register.php'">Start Earning</a>
                        <a href="#contact" class="btn btn-outline-light" onclick="scrollToContact(event)">Learn More</a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="animated-design"></div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5" id="features">
        <div class="container">
            <h2 class="section-title text-center">The Kat Way</h2>
            <p class="section-subtitle text-center">Simple. Professional. Profitable.</p>
            
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-lightning"></i>
                        </div>
                        <h3 class="h5 mb-2">Quick Setup</h3>
                        <p class="mb-0">5 minutes to profile. 24 hours to first gig. No BS in between.</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <h3 class="h5 mb-2">Secured Payment</h3>
                        <p class="mb-0">Money in escrow before work starts. No more ghost clients.</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <h3 class="h5 mb-2">Zero Commission</h3>
                        <p class="mb-0">Keep what you earn. We make money when you make money.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-light py-5" id="testimonials">
    <div class="container">
        <h2 class="section-title text-center">The Elite Club</h2>
        <p class="section-subtitle text-center">Join the freelancers who actually make bank.</p>
        
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="testimonial-card">
                    <p class="mb-3">"Quit my 9-5 in 3 months. Now I work from Bali. Not kidding."</p>
                    <div class="d-flex align-items-center">
                        <img src="images/image1.jpg" alt="Alex" class="rounded-circle me-2" style="width: 50px; height: 50px; object-fit: cover;" />
                        <div>
                            <h5 class="h6 mb-0">Alex K.</h5>
                            <small class="text-muted">Dev Ninja</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="testimonial-card">
                    <p class="mb-3">"Made $10k in my first month. The platform actually delivers."</p>
                    <div class="d-flex align-items-center">
                        <img src="images/image2.jpg" alt="Sarah" class="rounded-circle me-2" style="width: 50px; height: 50px; object-fit: cover;" />
                        <div>
                            <h5 class="h6 mb-0">Sarah M.</h5>
                            <small class="text-muted">Design Guru</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="testimonial-card">
                    <p class="mb-3">"Found my dream clients(not really). No more lowball offers. Just kidding."</p>
                    <div class="d-flex align-items-center">
                        <img src="images/image3.jpg" alt="James" class="rounded-circle me-2" style="width: 50px; height: 50px; object-fit: cover;" />
                        <div>
                            <h5 class="h6 mb-0">James R.</h5>
                            <small class="text-muted">Word Wizard</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

    <section class="py-5" id="contact">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <h2 class="section-title text-center">Ready to Level Up?</h2>
                    <p class="section-subtitle text-center">Get early access. Beat the crowd.</p>
                    
                    <div class="contact-form">
                        <form id="contactForm" onsubmit="return handleSubmit(event)">
                            <div class="mb-3">
                                <input type="email" class="form-control" placeholder="Drop your email" required />
                            </div>
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary w-100">Get Started</button>
                            </div>
                            <div id="successMessage" class="success-message">
                                🎉 You're in! Check your inbox for VIP access.
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container text-center">
            <p class="mb-0">© 2024 Kat. Making Freelancing Actually Worth It.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function scrollToContact(e) {
            e.preventDefault();
            document.querySelector('#contact').scrollIntoView({
                behavior: 'smooth'
            });
        }

        // Original handleSubmit function remains unchanged
        function handleSubmit(event) {
            event.preventDefault();
            const form = document.getElementById('contactForm');
            const successMessage = document.getElementById('successMessage');
            
            // Show success message with animation
            successMessage.style.display = 'block';
            // Small delay to ensure display:block takes effect before adding fade-in
            setTimeout(() => {
                successMessage.classList.add('fade-in');
            }, 10);
            
            // Reset form
            form.reset();
            
            // Hide success message after 3 seconds
            setTimeout(() => {
                successMessage.classList.remove('fade-in');
                // Wait for fade out before hiding
                setTimeout(() => {
                    successMessage.style.display = 'none';
                }, 300);
            }, 3000);
            
            return false;
        }
    </script>
    
</body>
</html>