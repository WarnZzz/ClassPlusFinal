<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacherId = $_SESSION['userId'];
    $submissionId = intval($_POST['submissionId']);
    $remarks = isset($_POST['remarks']) ? trim($_POST['remarks']) : null;
    $isChecked = isset($_POST['isChecked']) ? intval($_POST['isChecked']) : null;

    if (!$submissionId) {
        echo "Invalid submission ID.";
        exit;
    }

    // Build dynamic update query
    $fields = [];
    $params = [];
    $types = '';

    if ($remarks !== null) {
        $fields[] = 's.Remarks = ?';
        $params[] = $remarks;
        $types .= 's';
    }
    if ($isChecked !== null) {
        $fields[] = 's.IsChecked = ?';
        $params[] = $isChecked;
        $types .= 'i';
    }

    if (empty($fields)) {
        echo "No changes specified.";
        exit;
    }

    $query = "UPDATE tblsubmissions s JOIN tblassignments a ON s.AssignmentId = a.Id
              SET " . implode(', ', $fields) . "
              WHERE s.Id = ? AND a.UploadedBy = ?";

    $params[] = $submissionId;
    $params[] = $teacherId;
    $types .= 'ii';

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "Failed to update: " . $conn->error;
    }

    $stmt->close();
} else {
    echo "Invalid request.";
}
?>
