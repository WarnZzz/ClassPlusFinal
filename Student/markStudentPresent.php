<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';
header('Content-Type: application/json');
date_default_timezone_set('Asia/Kathmandu');

$response = ['success' => false, 'message' => ''];

// 1. Ensure student is logged in
if (!isset($_SESSION['userId'])) {
    $response['message'] = 'Student not logged in.';
    echo json_encode($response);
    exit;
}

$symbolNo = $_SESSION['userId'];
$code = isset($_POST['code']) ? trim($_POST['code']) : '';

if (empty($code)) {
    $response['message'] = 'Code is required.';
    echo json_encode($response);
    exit;
}

// 2. Fetch active session matching the code
$stmt = $conn->prepare("SELECT Id, CourseId FROM tblattendance_sessions 
                        WHERE UniqueCode = ? AND Status = 'active' AND ExpiresAt > NOW()");
$stmt->bind_param("s", $code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $response['message'] = 'Invalid or expired session code.';
    echo json_encode($response);
    exit;
}

$session = $result->fetch_assoc();
$sessionId = $session['Id'];
$courseId = $session['CourseId'];

// 3. Verify student is enrolled in this course
$stmt = $conn->prepare("SELECT 1 FROM tblstudents 
                        WHERE SymbolNo = ? AND ClassId = (SELECT ClassId FROM tblclassarms WHERE Id = ?)");
$stmt->bind_param("si", $symbolNo, $courseId);
$stmt->execute();
$verifyResult = $stmt->get_result();

if ($verifyResult->num_rows === 0) {
    $response['message'] = 'You are not enrolled in this course.';
    echo json_encode($response);
    exit;
}

// 4. Check if already marked in temp table
$stmt = $conn->prepare("SELECT 1 FROM tblattendance_temp WHERE SessionId = ? AND SymbolNo = ?");
$stmt->bind_param("is", $sessionId, $symbolNo);
$stmt->execute();
$exists = $stmt->get_result();

if ($exists->num_rows > 0) {
    // Already scanned: success true or false?
    // To differentiate, we keep success = false, message indicates already scanned.
    $response['success'] = false;
    $response['message'] = 'You have already scanned the code.';
    echo json_encode($response);
    exit;
}

// 5. Insert attendance into temp table
$stmt = $conn->prepare("INSERT INTO tblattendance_temp (SessionId, SymbolNo) VALUES (?, ?)");
$stmt->bind_param("is", $sessionId, $symbolNo);

if ($stmt->execute()) {
    $response['success'] = true;
    $response['message'] = '✅ Attendance marked successfully.';
} else {
    $response['message'] = 'Database error. Please try again.';
}

echo json_encode($response);
