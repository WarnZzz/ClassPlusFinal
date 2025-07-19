<?php
session_start();
include '../Includes/dbcon.php';
date_default_timezone_set('Asia/Kathmandu');

header('Content-Type: application/json');

// Debug: log user role without breaking JSON
error_log('sendClassMessage.php: UserRole in session = ' . ($_SESSION['userRole'] ?? 'empty'));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$classId = isset($_POST['classId']) ? intval($_POST['classId']) : 0;
$senderId = isset($_POST['senderId']) ? intval($_POST['senderId']) : 0;
$messageText = isset($_POST['message']) ? trim($_POST['message']) : '';

// Get sender role directly from session for reliability
// $senderRole = $_SESSION['userRole'] ?? ($_POST['senderRole'] ?? '');
$senderRole = 'teacher'; // Default to 'student' if not set
error_log("sendClassMessage.php: classId=$classId, senderId=$senderId, senderRole='$senderRole'");

if ($classId <= 0 || $senderId <= 0 || empty($senderRole)) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Missing or invalid parameters',
        'debug' => ['classId' => $classId, 'senderId' => $senderId, 'senderRole' => $senderRole]
    ]);
    exit;
}

$filePath = null;
if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../uploads/chatfiles/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileTmpPath = $_FILES['file']['tmp_name'];
    $fileName = basename($_FILES['file']['name']);
    $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
    $newFileName = uniqid('chatfile_', true) . '.' . $fileExt;
    $destPath = $uploadDir . $newFileName;

    if (move_uploaded_file($fileTmpPath, $destPath)) {
        $filePath = '../uploads/chatfiles/' . $newFileName;
    } else {
        echo json_encode(['error' => 'File upload failed']);
        exit;
    }
}

$query = "INSERT INTO tblchatmessages (ClassId, SenderId, SenderRole, MessageText, FilePath, CreatedAt)
          VALUES (?, ?, ?, ?, ?, NOW())";
$stmt = $conn->prepare($query);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param("iisss", $classId, $senderId, $senderRole, $messageText, $filePath);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'senderRoleStored' => $senderRole]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Database execute failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
