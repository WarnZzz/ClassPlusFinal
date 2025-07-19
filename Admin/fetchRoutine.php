<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';

if (!isset($_GET['classId']) || empty($_GET['classId'])) {
    echo json_encode([]);
    exit;
}

$classId = (int)$_GET['classId'];

// Get class info to filter routine
$classQry = $conn->prepare("SELECT Program, `Year(Batch)`, section FROM tblclass WHERE Id = ?");
$classQry->bind_param("i", $classId);
$classQry->execute();
$result = $classQry->get_result();

if ($result->num_rows === 0) {
    echo json_encode([]);
    exit;
}

$class = $result->fetch_assoc();
$program = $class['Program'];
$year = $class['Year(Batch)'];
$section = $class['section'];

// Fetch existing routine entries
$routineQry = $conn->prepare("SELECT * FROM tblroutine WHERE Program=? AND Year_Batch=? AND Section=? ORDER BY FIELD(Day, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), TimeSlot ASC");
$routineQry->bind_param("sss", $program, $year, $section);
$routineQry->execute();
$routineRes = $routineQry->get_result();

$routines = [];
while ($row = $routineRes->fetch_assoc()) {
    $routines[] = $row;
}

header('Content-Type: application/json');
echo json_encode($routines);