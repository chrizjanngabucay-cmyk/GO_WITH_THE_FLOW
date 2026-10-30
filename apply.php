
<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$scholarshipId = filter_input(
    INPUT_GET,
    'scholarship_id',
    FILTER_VALIDATE_INT
);

if (!$scholarshipId || $scholarshipId < 1) {
    exit('Invalid scholarship ID.');
}

$requiredDocuments = [
    'Certificate of Residency',
    'Latest Grades / Report Card',
    'Certificate of Indigency',
    'PSA Birth Certificate',
    '2x2 ID Photo'
];

$message = '';
$error = '';

try {
    // Confirm that the scholarship exists.
    $stmt = $pdo->prepare(
        'SELECT * FROM scholarships WHERE id = ? LIMIT 1'
    );
    $stmt->execute([$scholarshipId]);
    $scholarship = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$scholarship) {
        exit('Scholarship not found.');
    }

    // Reuse an existing application or create a Draft.
    $stmt = $pdo->prepare(
        'SELECT * FROM applications
         WHERE user_id = ? AND scholarship_id = ?
         LIMIT 1'
    );
    $stmt->execute([$userId, $scholarshipId]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$application) {
        $stmt = $pdo->prepare(
            "INSERT INTO applications
             (user_id, scholarship_id, status)
             VALUES (?, ?, 'Draft')"
        );
        $stmt->execute([$userId, $scholarshipId]);

        $applicationId = (int) $pdo->lastInsertId();
        $application = [
            'id' => $applicationId,
            'status' => 'Draft'
        ];
    } else {
        $applicationId = (int) $application['id'];
    }

    // Process an upload or submission.
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (($application['status'] ?? '') !== 'Draft') {
            $error = 'This application has already been submitted.';
        } elseif (
            isset($_POST['upload_requirement']) &&
            isset($_FILES['requirement_file'])
        ) {
            $requirementName = trim(
                (string) $_POST['upload_requirement']
            );

            if (!in_array($requirementName, $requiredDocuments, true)) {
                throw new RuntimeException('Invalid requirement.');
            }

            $file = $_FILES['requirement_file'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException(
                    'Upload failed. Please select the file again.'
                );
            }

            if ($file['size'] > 5 * 1024 * 1024) {
                throw new RuntimeException(
                    'Maximum file size is 5 MB.'
                );
            }

            $extension = strtolower(
                pathinfo($file['name'], PATHINFO_EXTENSION)
            );

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];

            if (!in_array($extension, $allowedExtensions, true)) {
                throw new RuntimeException(
                    'Only JPG, PNG, and PDF files are allowed.'
                );
            }

            // Check actual file type, not just the filename.
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);

            $allowedMimeTypes = [
                'jpg'  => ['image/jpeg'],
                'jpeg' => ['image/jpeg'],
                'png'  => ['image/png'],
                'pdf'  => ['application/pdf']
            ];

            if (!in_array($mime, $allowedMimeTypes[$extension], true)) {
                throw new RuntimeException(
                    'The selected file type is not valid.'
                );
            }

            // Store uploads outside the publicly accessible project.
            $uploadDirectory = __DIR__ . '/private_uploads';

            if (
                !is_dir($uploadDirectory) &&
                !mkdir($uploadDirectory, 0750, true)
            ) {
                throw new RuntimeException(
                    'Unable to create upload directory.'
                );
            }

            $safeName = bin2hex(random_bytes(24)) . '.' . $extension;
            $destination = $uploadDirectory . '/' . $safeName;

            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                throw new RuntimeException(
                    'Unable to save the uploaded file.'
                );
            }

            $relativePath = 'private_uploads/' . $safeName;

            // Replace the previous upload for this requirement.
            $stmt = $pdo->prepare(
                'SELECT id, file_path FROM requirements
                 WHERE application_id = ? AND requirement_name = ?
                 LIMIT 1'
            );
            $stmt->execute([$applicationId, $requirementName]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $stmt = $pdo->prepare(
                    'UPDATE requirements
                     SET file_path = ?, uploaded_at = NOW()
                     WHERE id = ?'
                );
                $stmt->execute([$relativePath, $existing['id']]);

                // Remove the old file only after the database update.
                $oldPath = __DIR__ . '/' . $existing['file_path'];
                if (
                    is_file($oldPath) &&
                    strpos(realpath($oldPath), realpath($uploadDirectory) . DIRECTORY_SEPARATOR) === 0
                ) {
                    @unlink($oldPath);
                }
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO requirements
                     (application_id, requirement_name, file_path, uploaded_at)
                     VALUES (?, ?, ?, NOW())'
                );
                $stmt->execute([
                    $applicationId,
                    $requirementName,
                    $relativePath
                ]);
            }

            header(
                'Location: apply.php?scholarship_id=' .
                $scholarshipId . '&saved=1'
            );
            exit;
        } elseif (isset($_POST['submit_application'])) {
            // Partial submission is allowed as requested.
            $stmt = $pdo->prepare(
                "UPDATE applications
                 SET status = 'Submitted'
                 WHERE id = ? AND user_id = ? AND status = 'Draft'"
            );
            $stmt->execute([$applicationId, $userId]);

            if ($stmt->rowCount() > 0) {
                header(
                    'Location: apply.php?scholarship_id=' .
                    $scholarshipId . '&submitted=1'
                );
                exit;
            }

            $error = 'Unable to submit this application.';
        }
    }

    if (isset($_GET['saved'])) {
        $message = 'Your uploaded requirement has been saved.';
    }

    if (isset($_GET['submitted'])) {
        $message = 'Your application was submitted to the admin.';
    }

    // Load all saved uploads for this application.
    $stmt = $pdo->prepare(
        'SELECT requirement_name, file_path, uploaded_at
         FROM requirements
         WHERE application_id = ?'
    );
    $stmt->execute([$applicationId]);

    $uploadedFiles = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $uploadedFiles[$row['requirement_name']] = $row;
    }

} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Scholarship Requirements | SIRFRITZ</title>
<style>
*{box-sizing:border-box}
body{margin:0;background:#f3f5f9;font-family:Arial,sans-serif;color:#202938}
.container{max-width:900px;margin:35px auto;padding:20px}
.card{background:white;padding:25px;border-radius:14px;margin-bottom:18px;
box-shadow:0 5px 20px rgba(0,0,0,.06)}
h1{margin-top:0;font-size:26px}
.requirement{display:flex;justify-content:space-between;align-items:center;
gap:15px;flex-wrap:wrap;border:1px solid #e2e6ee;padding:18px;
border-radius:10px;margin:14px 0}
input[type=file]{max-width:100%;margin:10px 0}
button,.btn{display:inline-block;border:0;border-radius:7px;padding:10px 15px;
background:#174c35;color:white;text-decoration:none;cursor:pointer}
button:hover,.btn:hover{background:#236a4b}
.submit{background:#1769aa;padding:13px 20px;font-size:15px}
.notice{padding:12px;border-radius:8px;background:#e6f6ec;color:#17633b}
.error{padding:12px;border-radius:8px;background:#ffe8e8;color:#a21c1c}
.muted{color:#64748b;font-size:13px}
@media(max-width:600px){.container{padding:12px}.card{padding:17px}}
</style>
</head>
<body>
<div class="container">
    <p><a class="btn" href="dashboard.php">← Back to Dashboard</a></p>

    <div class="card">
        <h1>Scholarship Application</h1>
        <p>Upload the required documents for your selected scholarship.</p>
        <p class="muted">
            Allowed files: JPG, PNG, PDF. Maximum size: 5 MB per file.
        </p>

        <?php if ($message): ?>
            <p class="notice"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <p><strong>Application Status:</strong>
            <?= htmlspecialchars($application['status'] ?? 'Draft') ?>
        </p>
    </div>

    <div class="card">
        <h2>Required Documents</h2>

        <?php foreach ($requiredDocuments as $document): ?>
            <?php $saved = $uploadedFiles[$document] ?? null; ?>

            <div class="requirement">
                <div>
                    <strong><?= htmlspecialchars($document) ?></strong><br>

                    <?php if ($saved): ?>
                        <span class="notice">Uploaded and saved</span>
                        <p class="muted">
                            Saved: <?= htmlspecialchars($saved['uploaded_at']) ?>
                        </p>
                    <?php else: ?>
                        <span class="muted">No file uploaded yet.</span>
                    <?php endif; ?>
                </div>

                <?php if (($application['status'] ?? '') === 'Draft'): ?>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden"
                               name="upload_requirement"
                               value="<?= htmlspecialchars($document) ?>">

                        <input type="file"
                               name="requirement_file"
                               accept=".jpg,.jpeg,.png,.pdf"
                               required>

                        <button type="submit">Upload / Replace</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <?php if (($application['status'] ?? '') === 'Draft'): ?>
            <p>You can submit even if some requirements are still missing.</p>

            <form method="POST"
                  onsubmit="return confirm('Submit your application to the admin? You will no longer be able to edit uploads from this page.');">
                <button class="submit" type="submit"
                        name="submit_application" value="1">
                    Submit Application
                </button>
            </form>
        <?php else: ?>
            <p class="notice">
                Your application has been submitted. Contact the scholarship
                administrator if you need to replace a document.
            </p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
