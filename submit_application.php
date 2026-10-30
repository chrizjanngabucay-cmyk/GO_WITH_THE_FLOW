<?php
// TINANGGAL ANG session_start() DAHIL NASA config.php NA
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_application'])) {
    
    $scholarship_id = intval($_POST['scholarship_id'] ?? 0);
    $user_id = $_SESSION['user_id'];

    // Kunin ang pangalan ng scholarship mula sa database
    $stmt = $conn->prepare("SELECT name FROM scholarships WHERE id = ?");
    $stmt->execute([$scholarship_id]);
    $scholar = $stmt->fetch();

    if (!$scholar) {
        $msg = "❌ Invalid scholarship selected.";
    } else {
        $scholarship_name = $scholar['name'];

        // Ipunin ang mga na-upload na dokumento
        $uploaded_files = [];
        $upload_dir = 'uploads/applications/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Hanapin lahat ng na-upload na file
        foreach ($_FILES as $field => $file) {
            if (!empty($file['name'])) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $safe_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', basename($file['name'], ".{$ext}"));
                $filename = $user_id . '_' . time() . "_{$safe_name}.{$ext}";
                $target = $upload_dir . $filename;

                if (move_uploaded_file($file['tmp_name'], $target)) {
                    $uploaded_files[] = $target;
                }
            }
        }

        // I-sa JSON format
        $documents_json = json_encode($uploaded_files);

        // Ipasok sa database
        try {
            $stmt = $conn->prepare("
                INSERT INTO applications (user_id, scholarship_id, scholarship_name, documents, status, submitted_at)
                VALUES (?, ?, ?, ?, 'pending', NOW())
            ");
            $stmt->execute([$user_id, $scholarship_id, $scholarship_name, $documents_json]);

            header("Location: dashboard.php?success=submitted");
            exit;
        } catch (PDOException $e) {
            $msg = "❌ Error: " . $e->getMessage();
        }
    }
}

header("Location: dashboard.php");
exit;