<?php include 'config.php';

$error = "";
$login_type = $_GET['type'] ?? 'select';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $login_type = $_POST['login_type'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if ($login_type === 'admin') {
            if (empty($user['is_admin'])) {
                $error = "Access denied — Administrator accounts only!";
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['is_admin'] = true;
                header("Location: admin_dashboard.php");
                exit;
            }
        } else {
            if (!empty($user['is_admin'])) {
                $error = "This is an admin account — please select Admin Login.";
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                header("Location: select_type.php");
                exit;
            }
        }
    } else {
        $error = "Invalid email or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Tapaz Scholarship</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Roboto, sans-serif; }
        body {
            background: linear-gradient(rgba(0, 51, 102, 0.78), rgba(0, 51, 102, 0.78)),
                        url("photos/background.png") center/cover no-repeat fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            background: white;
            padding: 45px 30px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.25);
            width: 100%;
            max-width: 420px;
        }
        .card h2 {
            text-align: center;
            color: #003366;
            margin-bottom: 8px;
        }
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 25px;
        }
        .type-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
        }
        .type-btn {
            flex: 1;
            padding: 12px;
            border: 2px solid #ddd;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .type-btn.active.applicant {
            border-color: #003366;
            background: #e6f0fa;
            color: #003366;
        }
        .type-btn.active.admin {
            border-color: #F2C94C;
            background: #fffbeb;
            color: #92400e;
        }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            color: #333;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 13px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
        }
        .form-group input:focus {
            outline: none;
            border-color: #F2C94C;
            box-shadow: 0 0 0 3px rgba(242,201,76,0.2);
        }
        .btn {
            width: 100%;
            padding: 14px;
            background: #F2C94C;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn:hover { background: #e6b800; }
        .error {
            background: #ffe6e6;
            color: #cc0000;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .link {
            text-align: center;
            margin-top: 22px;
            color: #666;
        }
        .link a {
            color: #003366;
            text-decoration: none;
            font-weight: 600;
        }
        .hidden { display: none; }
    </style>
</head>
<body>
    <div class="card">
        <h2 id="page-title">Welcome Back</h2>
        <p class="subtitle">Municipality of Tapaz Scholarship Portal</p>

        <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif ?>

        <!-- Login Type Selector -->
        <div class="type-buttons">
            <button type="button" class="type-btn applicant <?=$login_type==='applicant'?'active':''?>" id="btn-applicant">
                🎓 Applicant
            </button>
            <button type="button" class="type-btn admin <?=$login_type==='admin'?'active':''?>" id="btn-admin">
                🔐 Admin
            </button>
        </div>

        <form method="POST" id="login-form">
            <input type="hidden" name="login_type" id="login_type" value="<?=$login_type==='admin'?'admin':'applicant'?>">

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>

            <button type="submit" class="btn" id="submit-btn">Log In as Applicant</button>
        </form>

        <div class="link" id="link-applicant" style="<?=$login_type==='admin'?'display:none':''?>">
            Don't have an account? <a href="register.php">Register Here</a>
        </div>
        <div class="link">
            <a href="index.html">← Back to Home</a>
        </div>
    </div>

    <script>
        const btnApplicant = document.getElementById('btn-applicant');
        const btnAdmin = document.getElementById('btn-admin');
        const loginType = document.getElementById('login_type');
        const submitBtn = document.getElementById('submit-btn');
        const pageTitle = document.getElementById('page-title');
        const linkApplicant = document.getElementById('link-applicant');

        function setType(type) {
            if (type === 'admin') {
                btnApplicant.classList.remove('active');
                btnAdmin.classList.add('active');
                loginType.value = 'admin';
                submitBtn.textContent = 'Log In as Admin';
                pageTitle.textContent = 'Administrator Login';
                linkApplicant.style.display = 'none';
            } else {
                btnAdmin.classList.remove('active');
                btnApplicant.classList.add('active');
                loginType.value = 'applicant';
                submitBtn.textContent = 'Log In as Applicant';
                pageTitle.textContent = 'Welcome Back';
                linkApplicant.style.display = 'block';
            }
        }

        btnApplicant.onclick = () => setType('applicant');
        btnAdmin.onclick = () => setType('admin');
    </script>
</body>
</html>