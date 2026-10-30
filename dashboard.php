<?php
include 'config.php';
redirectIfNotLoggedIn();

$type = isset($_GET['type']) ? $_GET['type'] : 'new';
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'overview';
$scholarship_id = isset($_GET['scholarship_id']) ? intval($_GET['scholarship_id']) : 0;
$user_id = $_SESSION['user_id'];
$applicant_id = "TPZ-" . str_pad($user_id, 4, '0', STR_PAD_LEFT);

// Kunin profile ng user
$stmt = $conn->prepare("SELECT full_name, profile_pic, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$profile_pic = !empty($user['profile_pic']) ? "uploads/profiles/" . $user['profile_pic'] : "";
$first_name = explode(' ', $user['full_name'])[0];

// Kunin lahat ng scholarship
$scholarships = $conn->query("SELECT * FROM scholarships ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Kunin mga naisumite nang application ng user — KASAMA ANG SCHEDULE
$stmt = $conn->prepare("
    SELECT scholarship_id, status, submitted_at, exam_date, exam_venue, interview_date, interview_venue 
    FROM applications 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$user_apps = [];
$my_apps_full = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $user_apps[$row['scholarship_id']] = $row['status'];
    $my_apps_full[] = $row;
}

// Tingnan kung pwede pang mag-apply
function canApply($sch_id, $user_apps) {
    if (!isset($user_apps[$sch_id])) {
        return true;
    }
    $status = $user_apps[$sch_id];
    return $status === 'pending';
}

$scholarships_by_id = [];
foreach ($scholarships as $s) {
    $scholarships_by_id[$s['id']] = $s;
}
$selected_scholarship = null;
if ($scholarship_id && isset($scholarships_by_id[$scholarship_id])) {
    $selected_scholarship = $scholarships_by_id[$scholarship_id];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Tapaz Scholarship</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Roboto, sans-serif; }
        :root {
            --navy: #003366;
            --gold: #F2C94C;
            --gray: #f5f7fa;
            --muted: #6b7280;
            --green: #10b981;
            --red: #ef4444;
            --blue: #3b82f6;
        }
        body { background: var(--gray); min-height: 100vh; }
        
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

        .layout { display: flex; min-height: calc(100vh - 70px); }
        
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
        .back-link { display: inline-flex; align-items: center; gap: 6px; color: var(--navy); text-decoration: none; margin-bottom: 15px; font-size: 14px; }
        .back-link:hover { text-decoration: underline; }

        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 25px 0; }
        .stat-card {
            background: white; border-radius: 12px; padding: 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .stat-card h3 { font-size: 13px; color: var(--muted); text-transform: uppercase; margin-bottom: 10px; }
        .stat-card .value { font-size: 28px; font-weight: 700; color: var(--navy); }
        .stat-card .note { font-size: 12px; color: var(--muted); margin-top: 5px; }

        .content-card {
            background: white; border-radius: 12px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-top: 20px;
        }
        .content-card h3 { font-size: 18px; color: var(--navy); margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
        
        .scholar-item, .app-item, .ann-item {
            padding: 24px 0; border-bottom: 1px solid #f0f0f0;
        }
        .scholar-item:last-child, .app-item:last-child, .ann-item:last-child { border-bottom: none; }
        .scholar-item h4, .app-item h4, .ann-item h4 { font-size: 16px; color: var(--navy); margin-bottom: 8px; }
        .scholar-item p, .app-item p, .ann-item p { font-size: 14px; color: var(--muted); margin-bottom: 10px; line-height: 1.6; }
        
        .badge {
            display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; margin-bottom: 12px;
        }
        .badge.green { background: #ecfdf3; color: #065f46; }
        .badge.blue { background: #eef4fb; color: #1e40af; }
        .badge.orange { background: #fff4e6; color: #92400e; }
        .badge.gray { background: #f3f4f6; color: #4b5563; }
        .badge.red { background: #fee2e2; color: #991b1b; }
        
        .btn {
            display: inline-block; padding: 10px 24px; border-radius: 6px; font-size: 14px; font-weight: 600; text-decoration: none; border: none; cursor: pointer; transition: background 0.2s;
        }
        .btn.gold { background: var(--gold); color: #000; }
        .btn.gold:hover { background: #e6b800; }
        .btn.small { padding: 7px 16px; font-size: 13px; }
        .btn.green { background: var(--green); color: white; }
        .btn.green:hover { background: #0ca86e; }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn.gray { background: #d1d5db; color: #4b5563; cursor: not-allowed; }
        
        .empty-state { text-align: center; padding: 60px 20px; color: var(--muted); }
        .empty-state h4 { margin-bottom: 10px; font-size: 18px; color: var(--navy); }
        .empty-state p { font-size: 15px; margin-bottom: 10px; }

        .note-box { margin-top: 24px; padding: 16px 20px; background: #fffbeb; border-radius: 8px; border-left: 4px solid var(--gold); color: #92400e; font-size: 14px; }
        .note-box.red { background: #fef2f2; border-left-color: var(--red); color: #991b1b; }
        .note-box.green { background: #ecfdf3; border-left-color: var(--green); color: #065f46; }
        
        /* SCHEDULE BOX */
        .schedule-box {
            background: #f0fdf4; border-radius: 8px; padding: 16px 20px; margin-top: 15px;
            border-left: 4px solid var(--green);
        }
        .schedule-box h4 {
            font-size: 15px; color: var(--green); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;
        }
        .schedule-row { display: flex; gap: 20px; flex-wrap: wrap; }
        .schedule-col { flex: 1; min-width: 220px; }
        .schedule-col p { margin: 6px 0; font-size: 14px; }
        .schedule-col strong { color: var(--navy); }
        
        .req-item { 
            padding: 16px; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 12px;
            display: flex; align-items: center; gap: 12px; transition: all 0.3s;
        }
        .req-item.completed { border-color: var(--green); background: #f0fdf4; }
        .req-check { 
            font-size: 20px; color: transparent; transition: color 0.3s; min-width: 24px;
        }
        .req-check.checked { color: var(--green); }
        .req-content { flex: 1; }
        .req-content label { display: block; font-weight: 600; margin-bottom: 8px; color: var(--navy); }
        .req-content input[type="file"] { padding: 8px; font-size: 13px; }
        
        .progress-bar { height: 8px; background: #e5e7eb; border-radius: 4px; margin: 20px 0; overflow: hidden; }
        .progress-fill { height: 100%; background: var(--green); width: 0%; transition: width 0.3s; border-radius: 4px; }
        .progress-text { font-size: 14px; color: var(--muted); margin-bottom: 15px; }
        
        .submit-section { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; text-align: right; }
    </style>
</head>
<body>
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
                <span class="user-type"><?= ucfirst($type) ?> Applicant</span>
            </div>
            <nav class="sidebar-menu">
                <a href="dashboard.php?type=<?= $type ?>" class="<?= !isset($_GET['tab']) || $active_tab === 'overview' ? 'active' : '' ?>">📋 Overview</a>
                <a href="dashboard.php?type=<?= $type ?>&tab=scholarships" class="<?= $active_tab === 'scholarships' ? 'active' : '' ?>">🎓 Scholarships</a>
                <a href="dashboard.php?type=<?= $type ?>&tab=applications" class="<?= $active_tab === 'applications' ? 'active' : '' ?>">📄 My Applications</a>
                <a href="dashboard.php?type=<?= $type ?>&tab=documents" class="<?= $active_tab === 'documents' ? 'active' : '' ?>">📁 Documents</a>
                <a href="dashboard.php?type=<?= $type ?>&tab=announcements" class="<?= $active_tab === 'announcements' ? 'active' : '' ?>">🔔 Announcements</a>
                <a href="my_account.php?type=<?= $type ?>">👤 My Account</a>
                <a href="logout.php">🚪 Log out</a>
            </nav>
            <div class="sidebar-help">
                <h4>Need assistance?</h4>
                <p>Visit the Municipal Scholarship Office, Monday–Friday, 8 AM – 5 PM.</p>
            </div>
        </aside>

        <main class="main-content">
            <?php if ($active_tab === 'apply' && $selected_scholarship): ?>
                <?php
                $can_apply = canApply($selected_scholarship['id'], $user_apps);
                $current_status = isset($user_apps[$selected_scholarship['id']]) ? $user_apps[$selected_scholarship['id']] : null;
                ?>

                <?php if (!$can_apply): ?>
                    <a href="dashboard.php?type=<?= $type ?>&tab=scholarships" class="back-link">← Back to Scholarships</a>
                    <div class="page-header">
                        <h2><?= htmlspecialchars($selected_scholarship['name']) ?></h2>
                    </div>
                    <div class="note-box red">
                        <strong>⚠️ You cannot apply again.</strong><br>
                        Your application status: <strong><?= htmlspecialchars(ucfirst($current_status)) ?></strong>.<br>
                        <?php if ($current_status === 'approved'): ?>
                            You have already been accepted for this scholarship.
                        <?php elseif ($current_status === 'rejected'): ?>
                            Your application has been reviewed and was not approved.
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <a href="dashboard.php?type=<?= $type ?>&tab=scholarships" class="back-link">← Back to Scholarships</a>
                    
                    <div class="page-header">
                        <span class="badge green">Open • Accepting Applications</span>
                        <h2><?= htmlspecialchars($selected_scholarship['name']) ?></h2>
                        <p><?= nl2br(htmlspecialchars($selected_scholarship['description'] ?? '')) ?></p>
                        <p style="margin-top: 8px; font-size: 14px;">
                            <strong>Deadline:</strong> <?= !empty($selected_scholarship['app_deadline']) ? date('F j, Y g:i A', strtotime($selected_scholarship['app_deadline'])) : 'To be announced' ?>
                        </p>
                    </div>

                   <form action="submit_application.php" method="POST" enctype="multipart/form-data" class="content-card" id="applicationForm">
    <h3>📋 Required Documents</h3>
    <p style="margin-bottom: 10px; color: var(--muted);">Upload all required documents below. Accepted formats: PDF, JPG, PNG (Max 5MB each).</p>
    
    <?php 
    $requirements = [];
    if (!empty($selected_scholarship['requirements'])) {
        $requirements = explode("\n", trim($selected_scholarship['requirements']));
        $requirements = array_filter($requirements, function($r) {
            return !empty(trim($r));
        });
    }
    if (empty($requirements)) {
        echo '<p style="color:var(--muted);">Requirements will be announced soon.</p>';
    } else {
    ?>
        <p class="progress-text">Completed: <span id="completedCount">0</span> of <?= count($requirements) ?> documents</p>
        <div class="progress-bar">
            <div class="progress-fill" id="progressFill"></div>
        </div>

        <input type="hidden" name="scholarship_id" value="<?= $selected_scholarship['id'] ?>">
        
        <?php foreach ($requirements as $index => $req): ?>
            <div class="req-item" id="req_<?= $index ?>">
                <span class="req-check" id="check_<?= $index ?>">✓</span>
                <div class="req-content">
                    <label for="req_file_<?= $index ?>"><?= htmlspecialchars(trim($req)) ?></label>
                    <input type="file" name="req_file_<?= $index ?>" id="req_file_<?= $index ?>" 
                        accept=".pdf,.jpg,.jpeg,.png" required onchange="updateStatus(<?= $index ?>)">
                    <input type="hidden" name="req_name_<?= $index ?>" value="<?= htmlspecialchars(trim($req)) ?>">
                </div>
            </div>
        <?php endforeach; ?>

        <div class="submit-section">
            <p style="margin-bottom: 12px; color: var(--muted); font-size: 13px;">
                By clicking "Submit Application", all your documents will be sent for review.
            </p>
            <button type="submit" name="submit_application" class="btn green" id="submitBtn" disabled>✅ Submit Application</button>
        </div>
    <?php } ?>
</form>

<script>
    const total = <?= count($requirements) ?>;
    let completed = 0;

    function updateStatus(index) {
        const input = document.getElementById('req_file_' + index);
        const item = document.getElementById('req_' + index);
        const check = document.getElementById('check_' + index);

        if (input.files.length > 0) {
            if (!item.classList.contains('completed')) {
                item.classList.add('completed');
                check.classList.add('checked');
                completed++;
            }
        } else {
            if (item.classList.contains('completed')) {
                item.classList.remove('completed');
                check.classList.remove('checked');
                completed--;
            }
        }

        document.getElementById('completedCount').textContent = completed;
        document.getElementById('progressFill').style.width = (completed / total * 100) + '%';
        document.getElementById('submitBtn').disabled = (completed < total);
    }
</script>
<?php endif; ?>

<?php elseif ($active_tab === 'scholarships'): ?>
    <div class="page-header">
        <h2>Available Scholarships</h2>
        <p>Programs published by the Scholarship Office.</p>
    </div>

    <div class="note-box">
        ⚠️ <strong>Notice:</strong> Scholarship programs will appear here once published.
    </div>

    <div class="content-card">
        <?php if (empty($scholarships)): ?>
            <div class="empty-state">
                <h4>No Programs Available Yet</h4>
                <p>Please check back later. New programs will be posted here once published.</p>
            </div>
        <?php else: ?>
            <?php foreach ($scholarships as $s): ?>
                <div class="scholar-item">
                    <span class="badge green">Open • Accepting Applications</span>
                    <h4>🎓 <?= htmlspecialchars($s['name']) ?></h4>
                    <p><?= htmlspecialchars($s['description'] ?? '') ?></p>
                    <p style="font-size: 13px; color: var(--muted); margin-top: 8px;">
                        <strong>Deadline:</strong> 
                        <?= !empty($s['app_deadline']) ? date('F j, Y g:i A', strtotime($s['app_deadline'])) : 'To be announced' ?>
                    </p>
                    <?php if (canApply($s['id'], $user_apps)): ?>
                        <a href="dashboard.php?type=<?= $type ?>&tab=apply&scholarship_id=<?= $s['id'] ?>" 
                           class="btn gold small" style="margin-top: 10px;">Apply Now</a>
                    <?php else: ?>
                        <?php $status = $user_apps[$s['id']]; ?>
                        <span class="badge <?= $status === 'approved' ? 'green' : ($status === 'rejected' ? 'red' : 'orange') ?>" 
                              style="margin-top: 10px; display: inline-block;">
                            <?= ucfirst($status) ?> — Cannot re-apply
                        </span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

<?php elseif ($active_tab === 'applications'): ?>
    <div class="page-header">
        <h2>My Applications</h2>
        <p>Track the status and schedule of your submitted applications.</p>
        <?php if (isset($_GET['success']) && $_GET['success'] === 'submitted'): ?>
            <div class="note-box green" style="margin-top: 15px;">
                ✅ Your application has been submitted successfully!
            </div>
        <?php endif; ?>
    </div>

    <div class="content-card">
        <?php if (empty($my_apps_full)): ?>
            <div class="empty-state">
                <h4>No Applications Yet</h4>
                <p>You haven't submitted any applications.</p>
                <a href="dashboard.php?type=<?= $type ?>&tab=scholarships" class="btn gold" style="margin-top: 20px;">Browse Scholarships</a>
            </div>
        <?php else: ?>
            <?php foreach ($my_apps_full as $app): ?>
                <?php
                $s = $app['status'] ?: 'pending';
                $status_colors = [
                    'pending' => '#f59e0b',
                    'reviewing' => '#3b82f6',
                    'approved' => '#10b981',
                    'rejected' => '#ef4444'
                ];
                $sch_name = $scholarships_by_id[$app['scholarship_id']]['name'] ?? 'Scholarship Program';
                ?>
                <div class="app-item">
                    <h4><?= htmlspecialchars($sch_name) ?></h4>
                    <p>Submitted: <?= date('F j, Y g:i A', strtotime($app['submitted_at'])) ?></p>
                    <p>Status: 
                        <strong style="color:<?= $status_colors[$s] ?>;">
                            <?= ucfirst($s) ?>
                        </strong>
                    </p>

                    <?php if ($s === 'approved' && (!empty($app['exam_date']) || !empty($app['interview_date']))): ?>
                        <div class="schedule-box">
                            <h4>📅 Scheduled Assessment</h4>
                            <div class="schedule-row">
                                <?php if (!empty($app['exam_date'])): ?>
                                    <div class="schedule-col">
                                        <p><strong>📝 Written Exam</strong></p>
                                        <p>Date & Time: <?= date('F j, Y', strtotime($app['exam_date'])) ?> at <?= date('h:i A', strtotime($app['exam_date'])) ?></p>
                                        <p>Venue: <?= !empty($app['exam_venue']) ? htmlspecialchars($app['exam_venue']) : 'To be announced' ?></p>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($app['interview_date'])): ?>
                                    <div class="schedule-col">
                                        <p><strong>🗣️ Interview</strong></p>
                                        <p>Date & Time: <?= date('F j, Y', strtotime($app['interview_date'])) ?> at <?= date('h:i A', strtotime($app['interview_date'])) ?></p>
                                        <p>Venue: <?= !empty($app['interview_venue']) ? htmlspecialchars($app['interview_venue']) : 'To be announced' ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php elseif ($s === 'approved'): ?>
                        <div class="note-box" style="margin-top: 15px;">
                            📋 Your application is approved. Schedule details will appear here once set.
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

<?php elseif ($active_tab === 'documents'): ?>
    <div class="page-header">
        <h2>My Documents</h2>
        <p>Upload and manage your requirements here.</p>
    </div>
    <div class="content-card">
        <p>Documents will appear here once you start and submit an application.</p>
    </div>

<?php elseif ($active_tab === 'announcements'): ?>
    <div class="page-header">
        <h2>Announcements</h2>
        <p>Official updates from the Scholarship Office.</p>
    </div>
    <div class="content-card">
        <div class="ann-item">
            <span class="badge green">New</span>
            <h4>Portal Now Open</h4>
            <p>Scholarship programs are now open for application. Please check the Scholarships page to apply.</p>
        </div>
    </div>

<?php else: ?>
    <div class="page-header">
        <p>Welcome, <?= $first_name ?>! 👋</p>
        <h2>Overview</h2>
        <p>Track your applications and stay updated.</p>
    </div>

    <div class="grid-3">
        <div class="stat-card">
            <h3>Applications Submitted</h3>
            <div class="value"><?= count($my_apps_full) ?></div>
            <p class="note">Total submitted</p>
        </div>
        <div class="stat-card">
            <h3>Available Programs</h3>
            <div class="value" style="color: var(--green);"><?= count($scholarships) ?></div>
            <p class="note">Open for application</p>
        </div>
        <div class="stat-card">
            <h3>Account Status</h3>
            <div class="value" style="color: var(--green);">Active</div>
            <p class="note">Profile complete</p>
        </div>
    </div>

    <div class="content-card">
        <h3>Quick Actions</h3>
        <p style="margin-bottom: 20px; color: var(--muted);">
            <?= empty($scholarships) ? 'No programs available yet. Please check back later.' : 'Browse open programs and apply today.' ?>
        </p>
        <?php if (!empty($scholarships)): ?>
            <a href="dashboard.php?type=<?= $type ?>&tab=scholarships" class="btn gold">View Available Scholarships</a>
        <?php endif; ?>
        <a href="my_account.php?type=<?= $type ?>" class="btn small" style="background: #eee; color: #333; margin-left: 10px;">Update My Profile</a>
    </div>
<?php endif; ?>
        </main>
    </div>
</body>
</html>