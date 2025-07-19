<?php
include '../Includes/dbcon.php';

header('Content-Type: application/json');

if (!isset($_GET['session_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session ID missing']);
    exit;
}

$sessionId = intval($_GET['session_id']);

$stmt = $conn->prepare("SELECT SymbolNo FROM tblattendance_temp WHERE SessionId = ?");
$stmt->bind_param("i", $sessionId);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row['SymbolNo'];
}

echo json_encode(['success' => true, 'markedStudents' => $students]);
