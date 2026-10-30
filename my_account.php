<?php include 'config.php';
redirectIfNotLoggedIn();

$type = isset($_GET['type']) ? $_GET['type'] : 'new';
$applicant_id = "TPZ-" . str_pad($_SESSION['user_id'], 4, '0', STR_PAD_LEFT);

$success_msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_profile'])) {
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    
    if (!empty($_FILES['profile_pic']['name'])) {
        $target_dir = "uploads/profiles/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
        $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif']) && $_FILES['profile_pic']['size'] < 2*1024*1024) {
            $filename = "user_" . $_SESSION['user_id'] . "_" . time() . "." . $ext;
            move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_dir . $filename);
            $stmt = $conn->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
            $stmt->execute([$filename, $_SESSION['user_id']]);
        }
    }
    
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?");
    $stmt->execute([$full_name, $phone, $_SESSION['user_id']]);
    $_SESSION['full_name'] = $full_name;
    
    header("Location: my_account.php?type=$type&saved=1");
    exit;
}
if (isset($_GET['saved'])) $success_msg = "✅ Profile updated successfully!";

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$profile_pic = !empty($user['profile_pic']) ? "uploads/profiles/" . $user['profile_pic'] : "";
$registration_date = date("F j, Y g:i A", strtotime($user['created_at']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account — Tapaz Scholarship</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Roboto, sans-serif; }
        :root {
            --navy: #003366;
            --gold: #F2C94C;
            --gray: #f5f7fa;
            --muted: #6b7280;
            --green: #10b981;
        }
        body { background: var(--gray); min-height: 100vh; }
        
        /* ===== TOP BAR — WALANG NAVIGATION ===== */
        .top-bar {
            background: white; padding: 15px 4%; display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid #e5e7eb;
        }
        .logo-section { display: flex; align-items: center; gap: 12px; }
        .logo-circle {
            width: 40px; height: 40px; border-radius: 50%; background: var(--gold);
            display: flex; align-items: center; justify-content: center; font-weight: bold; color: var(--navy); font-size: 18px;
        }
        .logo-text h1 { font-size: 15px; color: var(--navy); line-height: 1.3; }
        .logo-text p { font-size: 11px; color: var(--muted); }
        .user-box { border: 1px solid #e5e7eb; padding: 8px 16px; border-radius: 6px; font-size: 14px; color: var(--navy); font-weight: 500; }

        .layout { display: flex; min-height: 100vh; }
        
        /* ===== SIDEBAR — NANDITO PA RIN ===== */
        .sidebar { width: 260px; background: var(--navy); color: white; padding: 30px 20px; }
        .user-profile { text-align: center; margin-bottom: 30px; }
        .avatar {
            width: 60px; height: 60px; border-radius: 50%; background: var(--gold);
            margin: 0 auto 10px; display: flex; align-items: center; justify-content: center;
            font-size: 24px; color: var(--navy); font-weight: bold; overflow: hidden;
        }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .user-profile h3 { font-size: 15px; margin-bottom: 3px; }
        .user-profile p { font-size: 12px; opacity: 0.7; margin-bottom: 8px; }
        .user-type {
            display: inline-block; background: rgba(255,255,255,0.15); padding: 4px 12px;
            border-radius: 20px; font-size: 11px;
        }
        .sidebar-menu { margin-top: 25px; }
        .sidebar-menu a {
            display: flex; align-items: center; gap: 10px; color: rgba(255,255,255,0.7);
            text-decoration: none; padding: 12px 14px; border-radius: 8px; margin-bottom: 6px;
            font-size: 14px; transition: all 0.2s;
        }
        .sidebar-menu a:hover { background: rgba(255,255,255,0.05); }
        .sidebar-menu a.active { background: rgba(255,255,255,0.1); color: white; font-weight: 600; }
        .sidebar-help { margin-top: 40px; background: rgba(255,255,255,0.08); padding: 20px; border-radius: 10px; }
        .sidebar-help h4 { font-size: 13px; margin-bottom: 8px; }
        .sidebar-help p { font-size: 12px; opacity: 0.7; line-height: 1.5; }

        .main-content { flex: 1; padding: 30px 4%; }
        .page-header { margin-bottom: 25px; }
        .page-header h2 { font-size: 26px; color: var(--navy); margin-bottom: 5px; }
        .page-header p { color: var(--muted); font-size: 14px; }
        .success-msg {
            background: #e6ffe6; color: #006600; padding: 12px 20px; border-radius: 6px;
            margin-bottom: 20px; font-weight: 500;
        }

        .account-card {
            background: white; border-radius: 12px; padding: 35px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--gold);
        }
        .account-card h3 {
            font-size: 18px; color: var(--navy); margin-bottom: 25px; padding-bottom: 12px;
            border-bottom: 1px solid #eee;
        }
        .profile-grid { display: grid; grid-template-columns: 280px 1fr; gap: 40px; }
        
        .photo-section { text-align: center; }
        .photo-placeholder {
            width: 160px; height: 160px; border-radius: 50%; background: var(--gold);
            margin: 0 auto 15px; display: flex; align-items: center; justify-content: center;
            font-size: 48px; color: var(--navy); font-weight: bold; overflow: hidden;
        }
        .photo-placeholder img {
            width: 100%; height: 100%; object-fit: cover; display: block;
        }
        .upload-label {
            display: inline-block; padding: 10px 20px; background: #eef4fb;
            color: var(--navy); border-radius: 6px; font-size: 14px; cursor: pointer;
            font-weight: 500; margin-top: 8px;
        }
        .upload-label:hover { background: #d7e9fb; }
        input[type="file"] { display: none; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { font-size: 13px; color: var(--muted); font-weight: 600; text-transform: uppercase; }
        .form-group input, .info-box {
            padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 6px;
            font-size: 14px; background: #fafafa;
        }
        .form-group input:focus { outline: none; border-color: var(--gold); }
        .btn-save {
            margin-top: 25px; padding: 12px 35px; background: var(--gold); border: none;
            border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 15px;
        }
        .btn-save:hover { background: #e6b800; }
        .status-badge {
            display: inline-flex; align-items: center; gap: 6px;
            background: #ecfdf3; color: #065f46; padding: 6px 14px; border-radius: 20px;
            font-size: 13px; font-weight: 600; margin-top: 4px;
        }
        .status-badge::before {
            content: ''; width: 8px; height: 8px; background: var(--green); border-radius: 50%;
        }
    </style>
</head>
<body>
    <!-- ===== TOP BAR — Logo at User Name LANG, WALANG NAVIGATION ===== -->
    <div class="top-bar">
        <div class="logo-section">
            <div class="logo-circle">T</div>
            <div class="logo-text">
                <h1>REPUBLIC OF THE PHILIPPINES<br><strong>MUNICIPALITY OF TAPAZ</strong></h1>
                <p>PROVINCE OF CAPIZ</p>
            </div>
        </div>
        <div class="user-box"><?= $_SESSION['full_name'] ?></div>
    </div>

    <div class="layout">
        <!-- ===== SIDEBAR — NANDITO PA RIN LAHAT NG LINKS ===== -->
        <aside class="sidebar">
            <div class="user-profile">
                <div class="avatar">
                    <?php if ($profile_pic): ?>
                        <img src="<?= $profile_pic ?>" alt="Profile">
                    <?php else: ?>
                        <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <h3><?= $user['full_name'] ?></h3>
                <p>Applicant #<?= $applicant_id ?></p>
                <span class="user-type"><?= ucfirst($type) ?></span>
            </div>
            <nav class="sidebar-menu">
                <a href="dashboard.php?type=<?= $type ?>">📋 Overview</a>
                <a href="dashboard.php?type=<?= $type ?>&tab=scholarships">🎓 Scholarships</a>
                <a href="dashboard.php?type=<?= $type ?>&tab=applications">📄 My Applications</a>
                <a href="dashboard.php?type=<?= $type ?>&tab=documents">📁 Documents</a>
                <a href="dashboard.php?type=<?= $type ?>&tab=announcements">🔔 Announcements</a>
                <a href="my_account.php?type=<?= $type ?>" class="active">👤 My Account</a>
                <a href="logout.php">🚪 Log out</a>
            </nav>
            <div class="sidebar-help">
                <h4>Need assistance?</h4>
                <p>Visit the Municipal Scholarship Office, Monday–Friday, 8 AM – 5 PM.</p>
            </div>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h2>My Account</h2>
                <p>View and manage your personal information and profile settings.</p>
            </div>

            <?php if ($success_msg): ?>
                <div class="success-msg"><?= $success_msg ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="account-card">
                <h3>Profile Information</h3>
                
                <div class="profile-grid">
                    <div class="photo-section">
                        <div class="photo-placeholder" id="profilePreview">
                            <?php if ($profile_pic): ?>
                                <img src="<?= $profile_pic ?>" alt="Profile Photo">
                            <?php else: ?>
                                <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                            <?php endif; ?>
                        </div>
                        <label for="profile_pic" class="upload-label">📷 Choose Photo</label>
                        <input type="file" id="profile_pic" name="profile_pic" accept="image/*">
                        <p style="font-size: 12px; color: var(--muted); margin-top: 8px;">JPG, PNG or GIF • Max 2MB</p>
                    </div>

                    <div>
                        <div class="form-grid">
                            <div class="form-group full">
                                <label>Full Name</label>
                                <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Applicant Number</label>
                                <div class="info-box" style="background: #f0f4f8; color: var(--navy); font-weight: 600;">
                                    <?= $applicant_id ?>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Status</label>
                                <div class="status-badge"><?= ucfirst($type) ?> Applicant</div>
                            </div>
                            
                            <div class="form-group full">
                                <label>Email Address</label>
                                <div class="info-box"><?= htmlspecialchars($user['email'] ?? 'Not provided') ?></div>
                            </div>
                            
                            <div class="form-group full">
                                <label>Phone / Contact Number</label>
                                <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="Enter your contact number">
                            </div>
                            
                            <div class="form-group full">
                                <label>Account Registered On</label>
                                <div class="info-box" style="color: var(--muted);">
                                    <?= $registration_date ?>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" name="save_profile" class="btn-save">💾 Save Changes</button>
                    </div>
                </div>
            </form>
        </main>
    </div>

    <script>
        const fileInput = document.getElementById('profile_pic');
        const previewBox = document.getElementById('profilePreview');

        fileInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    previewBox.innerHTML = '<img src="' + e.target.result + '" alt="Profile Preview">';
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>