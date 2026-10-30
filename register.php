<?php include 'config.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $account_type = $_POST['account_type']; // ✅ Dagdag

    if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters!";
    } else {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $is_admin = ($account_type === 'admin') ? 1 : 0; // ✅ Dagdag
            
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password, is_admin) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$full_name, $email, $phone, $hashed_password, $is_admin]); // ✅ Dinagdag ang is_admin
            
            $success = "✅ Account created! You can now <a href='login.php'>Login</a>";
        } catch(PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                $error = "Email already registered!";
            } else {
                $error = "Error occurred. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — Tapaz Scholarship</title>
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
            padding: 40px 30px;
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
        .form-group { margin-bottom: 18px; }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            color: #333;
            font-weight: 500;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
        }
        .form-group input:focus, .form-group select:focus {
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
        .success {
            background: #e6ffe6;
            color: #006600;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        .link a {
            color: #003366;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="card">
        <h2>Create Account</h2>
        <p class="subtitle">Join the Tapaz Scholarship Program</p>

        <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif ?>
        <?php if ($success): ?><div class="success"><?= $success ?></div><?php endif ?>

        <form method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" required>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" name="phone">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required>
            </div>
            <!-- ✅ Dinagdag: Account Type -->
            <div class="form-group">
                <label>Account Type</label>
                <select name="account_type" required>
                    <option value="applicant">🎓 Scholarship Applicant</option>
                    <option value="admin">🔐 Administrator</option>
                </select>
            </div>
            
            <button type="submit" class="btn">Register</button>
        </form>

        <div class="link">
            Already have an account? <a href="login.php">Login Here</a>
        </div>
        <div class="link">
            <a href="index.html">← Back to Home</a>
        </div>
    </div>
</body>
</html>