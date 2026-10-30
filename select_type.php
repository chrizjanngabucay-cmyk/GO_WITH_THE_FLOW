<?php include 'config.php';
redirectIfNotLoggedIn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Application Type — Tapaz Scholarship</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Roboto, sans-serif; }
        body {
            /* Background */
            background: linear-gradient(rgba(0, 51, 102, 0.78), rgba(0, 51, 102, 0.78)),
                        url("photos/background.png") center/cover no-repeat fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 20px;
        }
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.25);
            width: 100%;
            max-width: 900px;
            padding: 45px 40px;
            text-align: center;
        }
        .container h1 {
            color: #003366;
            font-size: 28px;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 40px;
            font-size: 17px;
        }
        /* Two Column Layout */
        .cards-wrapper {
            display: flex;
            gap: 30px;
            margin-bottom: 35px;
            flex-wrap: wrap;
        }
        .option-card {
            flex: 1;
            min-width: 320px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 35px 28px;
            text-align: left;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: block;
        }
        /* Hover Effect */
        .option-card:hover {
            border-color: #F2C94C;
            background: #fff9e6;
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(242, 201, 76, 0.25);
        }
        /* Left card — New Applicant */
        .option-card.new {
            border-color: #F2C94C;
            background: #fffbeb;
        }
        .option-card.new:hover {
            background: #fff6cc;
        }
        /* Right card — Renewal */
        .option-card.renewal {
            border-color: #e5e7eb;
            background: #ffffff;
        }
        .option-card.renewal:hover {
            border-color: #003366;
            background: #eef4fb;
        }
        .card-icon {
            font-size: 36px;
            margin-bottom: 15px;
        }
        .option-card h3 {
            color: #003366;
            font-size: 21px;
            margin-bottom: 10px;
        }
        .option-card p {
            color: #666;
            font-size: 15px;
            line-height: 1.6;
            /* Details ALWAYS visible */
            opacity: 1;
        }
        .back-link {
            margin-top: 10px;
        }
        .back-link a {
            color: #666;
            text-decoration: none;
            font-size: 15px;
        }
        .back-link a:hover {
            color: #003366;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome, <?= $_SESSION['full_name'] ?>!</h1>
        <p class="subtitle">How would you like to proceed?</p>

        <div class="cards-wrapper">
            <!-- LEFT: New Applicant -->
            <a href="dashboard.php?type=new" class="option-card new">
                <div class="card-icon">📝</div>
                <h3>New Applicant</h3>
                <p>Applying for the first time. Select this if you do not have an existing scholarship record yet.</p>
            </a>

            <!-- RIGHT: Renewal Applicant -->
            <a href="type=renewal" class="option-card renewal">
                <div class="card-icon">🔄</div>
                <h3>Renewal Applicant</h3>
                <p>Already a scholar and continuing for the next period. Select this to renew your scholarship.</p>
            </a>
        </div>

        <div class="back-link">
            <a href="logout.php">← Log out</a>
        </div>
    </div>
</body>
</html>