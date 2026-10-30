<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Municipality of Tapaz — Scholarship Portal</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Roboto, sans-serif; }
        :root {
            --navy: #003366;
            --gold: #F2C94C;
            --white: #FFFFFF;
            --light-gray: #f5f7fa;
        }

        body {
            line-height: 1.6;
            background: var(--light-gray);
        }

        /* ==============================================
           LOGO SIZES
           ============================================== */
        .logo-left {
            height: 70px;
            width: auto;
            object-fit: contain;
        }

        .logo-right {
            height: 90px;
            width: auto;
            object-fit: contain;
        }

        /* ==============================================
           TOP HEADER
           ============================================== */
        .top-header {
            background: var(--white);
            padding: 12px 4%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--gold);
        }

        .header-title {
            text-align: center;
            flex: 1;
            padding: 0 20px;
        }

        .header-title .line1 {
            font-size: 14px;
            color: #666;
        }

        .header-title .line2 {
            font-size: 24px;
            color: var(--navy);
            font-weight: 700;
        }

        /* ==============================================
           NAVIGATION BAR WITH DROPDOWN
           ============================================== */
        .main-nav {
            background: var(--white);
            border-bottom: 1px solid #eee;
            padding: 0 4%;
        }

        .main-nav ul {
            list-style: none;
            display: flex;
            align-items: center;
            margin: 0;
            padding: 0;
        }

        .main-nav li {
            position: relative;
        }

        .main-nav li a {
            display: block;
            padding: 16px 20px;
            color: #333;
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.2s ease;
            border-bottom: 3px solid transparent;
        }

        .main-nav li a:hover,
        .main-nav li a.active {
            color: var(--navy);
            border-bottom: 3px solid var(--gold);
        }

        /* Dropdown Arrow */
        .main-nav .has-dropdown > a::after {
            content: " ▼";
            font-size: 10px;
            margin-left: 5px;
            color: #999;
        }

        /* Dropdown Menu */
        .main-nav .dropdown {
            position: absolute;
            left: 0;
            top: 100%;
            min-width: 200px;
            background: var(--white);
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
            border-radius: 0 0 6px 6px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(8px);
            transition: all 0.25s ease;
            z-index: 999;
        }

        .main-nav .has-dropdown:hover .dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .main-nav .dropdown li a {
            padding: 12px 20px;
            color: #333;
            border-bottom: 1px solid #f5f5f5;
        }

        .main-nav .dropdown li:last-child a {
            border-bottom: none;
        }

        .main-nav .dropdown li a:hover {
            background: rgba(242, 201, 76, 0.15);
            color: var(--navy);
            border-bottom-color: transparent;
        }

        /* Log In Button */
        .login-btn {
            margin-left: auto;
            background: var(--gold);
            color: #000 !important;
            font-weight: 600;
            border-radius: 4px;
            padding: 10px 28px !important;
            border-bottom: none !important;
            transition: background 0.2s ease;
        }

        .login-btn:hover {
            background: #e6b800;
        }

        /* ==============================================
           HERO SECTION
           ============================================== */
        .hero {
            background: linear-gradient(rgba(0, 51, 102, 0.75), rgba(0, 51, 102, 0.75)),
                        url("photos/background.png") center/cover no-repeat;
            min-height: 75vh;
            padding: 60px 4%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 40px;
            flex-wrap: wrap;
        }

        .hero-text {
            flex: 1;
            min-width: 300px;
            color: var(--white);
        }

        .hero-tag {
            color: var(--gold);
            font-size: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
        }

        .hero-text h2 {
            font-size: 42px;
            line-height: 1.2;
            margin-bottom: 20px;
        }

        .hero-text p {
            font-size: 17px;
            opacity: 0.9;
            margin-bottom: 35px;
            max-width: 500px;
        }

        .hero-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn-gold {
            background: var(--gold);
            color: #000;
            border: none;
            padding: 14px 30px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: background 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-gold:hover {
            background: #e6b800;
        }

        .btn-outline {
            background: transparent;
            color: var(--white);
            border: 2px solid var(--white);
            padding: 12px 28px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-outline:hover {
            background: var(--white);
            color: var(--navy);
        }

        /* ==============================================
           APPLICATION CARD
           ============================================== */
        .app-card {
            background: var(--white);
            padding: 35px;
            border-radius: 8px;
            box-shadow: 0 6px 25px rgba(0,0,0,0.15);
            width: 400px;
            flex-shrink: 0;
        }

        .app-card .card-header {
            color: #888;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .app-card h3 {
            font-size: 22px;
            color: var(--navy);
            margin-bottom: 15px;
        }

        .app-card p {
            font-size: 15px;
            color: #666;
            margin-bottom: 25px;
        }

        .deadline-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
            margin-bottom: 25px;
        }

        .deadline-row .label {
            color: #666;
            font-weight: 500;
        }

        .deadline-row .date {
            color: var(--navy);
            font-weight: 600;
        }

        .apply-btn {
            width: 100%;
            background: var(--gold);
            color: #000;
            border: none;
            padding: 15px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: background 0.2s;
            text-decoration: none;
            display: block;
            text-align: center;
        }

        .apply-btn:hover {
            background: #e6b800;
        }

        /* ==============================================
           PROGRAMS SECTION
           ============================================== */
        .programs {
            padding: 60px 4%;
            background: var(--light-gray);
        }

        .programs-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .programs-top h2 {
            font-size: 26px;
            color: var(--navy);
        }

        .browse-all {
            color: #2E7D32;
            text-decoration: none;
            font-weight: 500;
        }

        .program-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
        }

        .program-card {
            background: var(--white);
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .program-icon {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .program-card h4 {
            font-size: 17px;
            color: var(--navy);
            margin-bottom: 8px;
        }

        .program-card p {
            font-size: 14px;
            color: #666;
        }

        /* ==============================================
           RESPONSIVE
           ============================================== */
        @media(max-width: 768px) {
            .hero {
                text-align: center;
                justify-content: center;
                padding: 40px 20px;
            }
            .hero-buttons {
                justify-content: center;
            }
            .app-card {
                width: 100%;
            }
            .top-header {
                flex-direction: column;
                gap: 12px;
                padding: 15px 20px;
            }
            .main-nav ul {
                flex-wrap: wrap;
                justify-content: center;
            }
            .login-btn {
                margin-left: 0;
                margin-top: 8px;
            }
            .programs {
                padding: 40px 20px;
            }
        }
    </style>
</head>
<body>

<!-- ✨ TOP HEADER -->
<div class="top-header">
    <img src="photos/logo.jpg" alt="Municipality of Tapaz" class="logo-left">

    <div class="header-title">
        <div class="line1">Republic of the Philippines — Province of Capiz</div>
        <div class="line2">MUNICIPALITY OF TAPAZ</div>
    </div>

    <img src="photos/Logo-Bagong-Pilipinas.png" alt="Bagong Pilipinas" class="logo-right">
</div>

<!-- ✨ NAVIGATION BAR WITH DROPDOWN -->
<nav class="main-nav">
    <ul>
        <li><a href="#" class="active">Home</a></li>

        <li class="has-dropdown">
            <a href="#">Scholarships</a>
            <ul class="dropdown">
                <li><a href="#">Academic Merit</a></li>
                <li><a href="#">Financial Assistance</a></li>
                <li><a href="#">Skills & Technical</a></li>
            </ul>
        </li>

        <li class="has-dropdown">
            <a href="#">How to Apply</a>
            <ul class="dropdown">
                <li><a href="#">Requirements</a></li>
                <li><a href="#">Step-by-Step Guide</a></li>
                <li><a href="#">FAQs</a></li>
            </ul>
        </li>

        <li class="has-dropdown">
            <a href="#">Announcements</a>
            <ul class="dropdown">
                <li><a href="#">News & Updates</a></li>
                <li><a href="#">Results</a></li>
                <li><a href="#">Deadlines</a></li>
            </ul>
        </li>

        <li><a href="#">Contact</a></li>

        <!-- ✅ NAKA-LINK SA LOGIN -->
        <li><a href="login.php" class="login-btn">Log In</a></li>
    </ul>
</nav>

<!-- ✨ HERO SECTION -->
<section class="hero">
    <div class="hero-text">
        <p class="hero-tag">Municipal Scholarship Program</p>
        <h2>Your future starts here in Tapaz.</h2>
        <p>Explore scholarship opportunities for students in our municipality. We are here to support your education and dreams.</p>
        <div class="hero-buttons">
            <a href="#" class="btn-gold">View Scholarships</a>
            <a href="#" class="btn-outline">How to Apply</a>
        </div>
    </div>

    <!-- ✅ START APPLICATION → PUPUNTA SA LOGIN -->
    <div class="app-card">
        <p class="card-header">Applications Open</p>
        <h3>Academic Year 2026–2027</h3>
        <p>Submit your requirements before the deadline.</p>
        <div class="deadline-row">
            <span class="label">Deadline</span>
            <span class="date">October 30, 2026</span>
        </div>
        <a href="login.php" class="apply-btn">Start Application</a>
    </div>
</section>

<!-- ✨ PROGRAMS -->
<section class="programs">
    <div class="programs-top">
        <h2>Available Scholarship Programs</h2>
        <a href="#" class="browse-all">View All →</a>
    </div>
    <div class="program-grid">
        <div class="program-card">
            <div class="program-icon">🏅</div>
            <h4>Academic Merit Scholarship</h4>
            <p>For students with excellent grades.</p>
        </div>
        <div class="program-card">
            <div class="program-icon">🤝</div>
            <h4>Financial Assistance Grant</h4>
            <p>Support for families with limited income.</p>
        </div>
        <div class="program-card">
            <div class="program-icon">🔧</div>
            <h4>Skills & Technical Scholarship</h4>
            <p>For vocational and technical training.</p>
        </div>
    </div>
</section>

</body>
</html>