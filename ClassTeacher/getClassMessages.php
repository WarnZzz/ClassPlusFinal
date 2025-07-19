<?php
session_start();
include '../Includes/dbcon.php';
date_default_timezone_set('Asia/Kathmandu');

header('Content-Type: application/json');

// Validate classId parameter
if (!isset($_GET['classId'])) {
    http_response_code(400);
    echo json_encode(['error' => 'classId missing']);
    exit;
}

$classId = intval($_GET['classId']);

// Verify class exists
$queryClass = "SELECT Id FROM tblclass WHERE Id = ?";
$stmt = $conn->prepare($queryClass);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database prepare error']);
    exit;
}
$stmt->bind_param("i", $classId);
$stmt->execute();
$class = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$class) {
    echo json_encode([]);
    exit;
}

// Fetch chat messages with sender names
$queryMessages = "
    SELECT 
        m.SenderId,
        m.SenderRole,
        m.MessageText,
        m.FilePath,
        m.CreatedAt,
        CASE 
            WHEN m.SenderRole = 'teacher' THEN t.firstName
            WHEN m.SenderRole = 'student' THEN s.firstName
            ELSE 'Unknown'
        END AS SenderName
    FROM tblchatmessages m
    LEFT JOIN tblclassteacher t ON m.SenderId = t.Id AND m.SenderRole = 'teacher'
    LEFT JOIN tblstudents s ON m.SenderId = s.SymbolNo AND m.SenderRole = 'student'
    WHERE m.ClassId = ?
    ORDER BY m.CreatedAt ASC
";

$stmt = $conn->prepare($queryMessages);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database prepare error']);
    exit;
}

$stmt->bind_param("i", $classId);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

echo json_encode($messages);

$stmt->close();
$conn->close();
