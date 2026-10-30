<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'config.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header("Location: login.php");
    exit;
}

$msg = "";

// Magdagdag ng Scholarship
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_scholarship'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description'] ?? '');
    $requirements = trim($_POST['requirements']);
    $max_applicants = intval($_POST['max_applicants']);
    $app_deadline = $_POST['app_deadline'];

    $stmt = $conn->prepare("INSERT INTO scholarships (name, description, requirements, max_applicants, app_deadline) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $description, $requirements, $max_applicants, $app_deadline]);
    $msg = "<p style='color:green;'>✅ Scholarship added successfully!</p>";
}

// Mag-edit ng Scholarship
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_scholarship'])) {
    $id = intval($_POST['sch_id']);
    $name = trim($_POST['name']);
    $description = trim($_POST['description'] ?? '');
    $requirements = trim($_POST['requirements']);
    $max_applicants = intval($_POST['max_applicants']);
    $app_deadline = $_POST['app_deadline'];

    $stmt = $conn->prepare("UPDATE scholarships SET name=?, description=?, requirements=?, max_applicants=?, app_deadline=? WHERE id=?");
    $stmt->execute([$name, $description, $requirements, $max_applicants, $app_deadline, $id]);
    $msg = "<p style='color:green;'>✅ Scholarship updated!</p>";
}

// I-update Status ng Application
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $app_id = intval($_POST['application_id']);
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE applications SET status = ? WHERE id = ?");
    $stmt->execute([$status, $app_id]);
    $msg = "<p style='color:green;'>✅ Status updated!</p>";
}

// Mag-set ng Schedule
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['set_schedule'])) {
    $app_id = intval($_POST['application_id']);
    $exam_date = !empty($_POST['exam_date']) ? $_POST['exam_date'] : null;
    $exam_venue = trim($_POST['exam_venue'] ?? '');
    $interview_date = !empty($_POST['interview_date']) ? $_POST['interview_date'] : null;
    $interview_venue = trim($_POST['interview_venue'] ?? '');

    $stmt = $conn->prepare("UPDATE applications SET exam_date=?, exam_venue=?, interview_date=?, interview_venue=? WHERE id=?");
    $stmt->execute([$exam_date, $exam_venue, $interview_date, $interview_venue, $app_id]);
    $msg = "<p style='color:green;'>✅ Schedule saved! Applicant will see it on their dashboard.</p>";
}

// Kunin lahat ng applications
$apps = $conn->query("
    SELECT a.*, u.full_name, u.email, s.name as scholarship_name 
    FROM applications a 
    JOIN users u ON a.user_id = u.id 
    LEFT JOIN scholarships s ON a.scholarship_id = s.id 
    ORDER BY a.submitted_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Ihiwalay ayon sa status
$pending_apps = [];
$approved_apps = [];
$rejected_apps = [];
foreach ($apps as $a) {
    $s = $a['status'] ?: 'pending';
    if ($s === 'approved') $approved_apps[] = $a;
    elseif ($s === 'rejected') $rejected_apps[] = $a;
    else $pending_apps[] = $a;
}

$scholarships = $conn->query("SELECT * FROM scholarships ORDER BY created_at DESC")->fetchAll();

// Kunin ang ia-edit na scholarship
$edit_sch = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM scholarships WHERE id = ?");
    $stmt->execute([intval($_GET['edit'])]);
    $edit_sch = $stmt->fetch();
}

$active_tab = $_GET['tab'] ?? 'pending';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — Tapaz Scholarship</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Roboto, sans-serif; }
        body { background: #f0f4f8; }
        .header { background: #003366; color: white; padding: 18px 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 20px; }
        .header-links a { color: white; margin-left: 15px; text-decoration: none; }
        .container { max-width: 1200px; margin: 25px auto; padding: 0 20px; }
        .card { background: white; border-radius: 10px; padding: 25px; margin-bottom: 25px; box-shadow: 0 3px 10px rgba(0,0,0,0.08); }
        .card h2 { color: #003366; margin-bottom: 20px; font-size: 18px; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .form-row { display: flex; gap: 15px; flex-wrap: wrap; }
        .form-group { flex: 1; min-width: 200px; margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 14px; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; }
        .btn { padding: 10px 20px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px; }
        .btn-yellow { background: #F2C94C; color: #333; }
        .btn-blue { background: #003366; color: white; }
        .btn-green { background: #10b981; color: white; }
        .btn-red { background: #dc3545; color: white; }
        .btn-sm { padding: 6px 12px; font-size: 13px; }
        .status { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .app-item, .sch-item { border-bottom: 1px solid #eee; padding: 20px 0; }
        .app-item:last-child, .sch-item:last-child { border-bottom: none; }
        .msg { margin-bottom: 15px; }
        .docs a { display: block; color: #003366; font-size: 14px; margin: 4px 0; }
        
        /* TABS */
        .tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 0; }
        .tab-btn { padding: 10px 20px; background: none; border: none; border-bottom: 3px solid transparent; cursor: pointer; font-weight: 600; font-size: 14px; margin-bottom: -2px; }
        .tab-btn.active { border-bottom-color: #003366; color: #003366; }
        .count-badge { display: inline-block; background: #eee; padding: 2px 8px; border-radius: 10px; font-size: 12px; margin-left: 6px; }
        .tab-btn.active .count-badge { background: #003366; color: white; }
        
        /* SCHEDULE FORM */
        .schedule-box { background: #f9fafb; padding: 15px; border-radius: 8px; margin-top: 12px; border-left: 3px solid #10b981; }
        .schedule-box h4 { font-size: 14px; color: #003366; margin-bottom: 10px; }
        .schedule-row { display: flex; gap: 12px; flex-wrap: wrap; }
        .schedule-row .form-group { min-width: 180px; margin-bottom: 10px; }
        .sched-info { background: #ecfdf3; padding: 10px 14px; border-radius: 6px; margin-top: 8px; font-size: 13px; }
        .sched-info p { margin: 4px 0; }
    </style>
</head>
<body>

<div class="header">
    <h1>🏛️ Admin Dashboard — Tapaz Scholarship</h1>
    <div class="header-links">
        <span>Welcome, <?= $_SESSION['full_name'] ?? 'Admin' ?></span>
        <a href="select_type.php">User Dashboard</a>
        <a href="logout.php">Log out</a>
    </div>
</div>

<div class="container">
    <?php if ($msg): ?><div class="msg"><?= $msg ?></div><?php endif ?>

    <!-- ✅ ADD / EDIT SCHOLARSHIP FORM -->
    <div class="card">
        <h2><?= $edit_sch ? '✏️ Edit Scholarship' : '➕ Add New Scholarship' ?></h2>
        <form method="POST">
            <?php if ($edit_sch): ?>
                <input type="hidden" name="sch_id" value="<?= $edit_sch['id'] ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label>Name of Scholarship</label>
                <input type="text" name="name" required value="<?= $edit_sch ? htmlspecialchars($edit_sch['name']) : '' ?>" placeholder="e.g. Tapaz Private College Scholarship Program">
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="2" placeholder="Brief description..."><?= $edit_sch ? htmlspecialchars($edit_sch['description'] ?? '') : '' ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Requirements (one per line)</label>
                <textarea name="requirements" rows="4" required placeholder="1. Report Card&#10;2. Certificate of Indigency&#10;3. 2x2 Photo"><?= $edit_sch ? htmlspecialchars($edit_sch['requirements']) : '' ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Max Applicants</label>
                    <input type="number" name="max_applicants" required min="1" value="<?= $edit_sch ? $edit_sch['max_applicants'] : '' ?>" placeholder="50">
                </div>
                <div class="form-group">
                    <label>Application Deadline</label>
                    <input type="datetime-local" name="app_deadline" required value="<?= $edit_sch ? date('Y-m-d\TH:i', strtotime($edit_sch['app_deadline'])) : '' ?>">
                </div>
            </div>
            
            <?php if ($edit_sch): ?>
                <button type="submit" name="edit_scholarship" class="btn btn-yellow">Update Scholarship</button>
                <a href="admin_dashboard.php" class="btn" style="background:#eee; margin-left:8px;">Cancel</a>
            <?php else: ?>
                <button type="submit" name="add_scholarship" class="btn btn-yellow">Add Scholarship</button>
            <?php endif; ?>
        </form>
    </div>

    <!-- ✅ LIST OF SCHOLARSHIPS -->
    <div class="card">
        <h2>🎓 Scholarship Programs</h2>
        <?php if (!$scholarships): ?>
            <p style="color:#666; text-align:center; padding:20px;">No scholarship programs added yet.</p>
        <?php else: ?>
            <?php foreach ($scholarships as $s): ?>
                <div class="sch-item">
                    <h3 style="color:#003366; margin-bottom:8px;"><?= htmlspecialchars($s['name']) ?></h3>
                    <?php if (!empty($s['description'])): ?>
                        <p style="margin:6px 0; color:#555;"><?= htmlspecialchars($s['description']) ?></p>
                    <?php endif; ?>
                    <p style="margin:6px 0;"><strong>Requirements:</strong><br><?= nl2br(htmlspecialchars($s['requirements'])) ?></p>
                    <p style="margin:6px 0;"><strong>Max Applicants:</strong> <?= $s['max_applicants'] ?></p>
                    <p style="margin:6px 0;"><strong>Deadline:</strong> <?= date('F j, Y g:i A', strtotime($s['app_deadline'])) ?></p>
                    <a href="admin_dashboard.php?edit=<?= $s['id'] ?>" class="btn btn-blue btn-sm" style="margin-top:8px;">✏️ Edit</a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ✅ APPLICATIONS — TABBED -->
    <div class="card">
        <h2>📋 Applications</h2>
        
        <div class="tabs">
            <a href="?tab=pending" class="tab-btn <?= $active_tab === 'pending' ? 'active' : '' ?>">
                ⏳ Pending <span class="count-badge"><?= count($pending_apps) ?></span>
            </a>
            <a href="?tab=approved" class="tab-btn <?= $active_tab === 'approved' ? 'active' : '' ?>">
                ✅ Approved <span class="count-badge"><?= count($approved_apps) ?></span>
            </a>
            <a href="?tab=rejected" class="tab-btn <?= $active_tab === 'rejected' ? 'active' : '' ?>">
                ❌ Rejected <span class="count-badge"><?= count($rejected_apps) ?></span>
            </a>
        </div>

        <?php
        // Piliin kung aling listahan ang ipapakita
        $display_apps = [];
        if ($active_tab === 'pending') $display_apps = $pending_apps;
        elseif ($active_tab === 'approved') $display_apps = $approved_apps;
        elseif ($active_tab === 'rejected') $display_apps = $rejected_apps;
        ?>

        <?php if (empty($display_apps)): ?>
            <p style="color:#666; text-align:center; padding:30px;">
                <?php if ($active_tab === 'pending'): ?>
                    No pending applications.
                <?php elseif ($active_tab === 'approved'): ?>
                    No approved applications yet.
                <?php else: ?>
                    No rejected applications.
                <?php endif; ?>
            </p>
        <?php else: ?>
            <?php foreach ($display_apps as $app): ?>
                <div class="app-item">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px; flex-wrap:wrap; gap:10px;">
                        <div>
                            <h3><?= htmlspecialchars($app['scholarship_name'] ?: 'General Application') ?></h3>
                            <p style="color:#666; font-size:14px;">
                                Applicant: <strong><?= htmlspecialchars($app['full_name']) ?></strong> | 
                                Email: <?= htmlspecialchars($app['email']) ?><br>
                                Submitted: <?= date('F j, Y g:i A', strtotime($app['submitted_at'])) ?>
                            </p>
                        </div>
                        <span class="status status-<?= $app['status'] ?>"><?= ucfirst($app['status'] ?: 'Pending') ?></span>
                    </div>
                    
                   <?php if (!empty($app['documents'])): ?>
                        <div class="docs" style="margin:10px 0;">
                            <strong>📎 Documents:</strong>
                            <?php $docs = json_decode($app['documents'], true) ?: []; ?>
                            <?php foreach ($docs as $file): ?>
                                <a href="<?= htmlspecialchars($file) ?>" target="_blank">📄 <?= basename($file) ?></a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($active_tab === 'approved'): ?>
                        <!-- Schedule Form — Only for Approved -->
                        <div class="schedule-box">
                            <h4>📅 Set Exam & Interview Schedule</h4>
                            
                            <?php if (!empty($app['exam_date']) || !empty($app['interview_date'])): ?>
                                <div class="sched-info">
                                    <?php if (!empty($app['exam_date'])): ?>
                                        <p><strong>📝 Exam:</strong> <?= date('F j, Y g:i A', strtotime($app['exam_date'])) ?></p>
                                        <p><strong>Venue:</strong> <?= htmlspecialchars($app['exam_venue'] ?? 'Not specified') ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($app['interview_date'])): ?>
                                        <p><strong>🗣️ Interview:</strong> <?= date('F j, Y g:i A', strtotime($app['interview_date'])) ?></p>
                                        <p><strong>Venue:</strong> <?= htmlspecialchars($app['interview_venue'] ?? 'Not specified') ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" style="margin-top:10px;">
                                <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                                <div class="schedule-row">
                                    <div class="form-group">
                                        <label>Exam Date & Time</label>
                                        <input type="datetime-local" name="exam_date" 
                                            value="<?= !empty($app['exam_date']) ? date('Y-m-d\TH:i', strtotime($app['exam_date'])) : '' ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Exam Venue</label>
                                        <input type="text" name="exam_venue" placeholder="e.g. Iloilo City Hall, Room 201" 
                                            value="<?= htmlspecialchars($app['exam_venue'] ?? '') ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Interview Date & Time</label>
                                        <input type="datetime-local" name="interview_date" 
                                            value="<?= !empty($app['interview_date']) ? date('Y-m-d\TH:i', strtotime($app['interview_date'])) : '' ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Interview Venue</label>
                                        <input type="text" name="interview_venue" placeholder="e.g. Provincial Capitol, Conference B" 
                                            value="<?= htmlspecialchars($app['interview_venue'] ?? '') ?>">
                                    </div>
                                </div>
                                <button type="submit" name="set_schedule" class="btn btn-green">💾 Save Schedule</button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <!-- Status Update Form -->
                    <form method="POST" style="margin-top:15px; display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                        <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                        <div class="form-group" style="width:auto; margin:0;">
                            <select name="status" style="padding:10px; border-radius:6px; border:1px solid #ddd;">
                                <option value="pending" <?= ($app['status'] ?? 'pending') === 'pending' ? 'selected' : '' ?>>⏳ Pending</option>
                                <option value="approved" <?= $app['status'] === 'approved' ? 'selected' : '' ?>>✅ Approved</option>
                                <option value="rejected" <?= $app['status'] === 'rejected' ? 'selected' : '' ?>>❌ Rejected</option>
                            </select>
                        </div>
                        <button type="submit" name="update_status" class="btn btn-blue">Update Status</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

</body>
</html>