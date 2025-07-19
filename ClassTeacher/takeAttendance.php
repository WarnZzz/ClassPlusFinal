<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';
date_default_timezone_set('Asia/Kathmandu');

require_once '../vendor/autoload.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

function generateUniqueCode($length = 8) {
    return substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
}

// Auto-close expired sessions
$conn->query("UPDATE tblattendance_sessions SET Status = 'closed' WHERE ExpiresAt < NOW() AND Status = 'active'");

// Fetch courses for logged-in teacher
$query = "SELECT tblclassarms.Id, tblclassarms.CourseName, tblclass.Program, tblclass.`Year(Batch)`, tblclass.section
          FROM tblclassteacher
          INNER JOIN tblclassarms ON tblclassteacher.Id = tblclassarms.AssignedTo
          INNER JOIN tblclass ON tblclassarms.ClassId = tblclass.Id
          WHERE tblclassteacher.Id = '$_SESSION[userId]'";
$rs = $conn->query($query);
$courses = [];
while ($row = $rs->fetch_assoc()) {
    $courses[] = $row;
}

$attendanceSession = null;
$students = [];

if (isset($_POST['start_session']) && isset($_POST['courseId'])) {
    $courseId = $_POST['courseId'];
    $attendanceCode = generateUniqueCode(8);
    $createdAt = date('Y-m-d H:i:s');
    $expiresAt = date('Y-m-d H:i:s', strtotime($createdAt . ' +5 minutes'));
    $teacherIp = $_SERVER['REMOTE_ADDR'];

    $stmt = $conn->prepare("INSERT INTO tblattendance_sessions (CourseId, UniqueCode, TeacherIP, CreatedAt, ExpiresAt) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $courseId, $attendanceCode, $teacherIp, $createdAt, $expiresAt);
    if ($stmt->execute()) {
        $sessionId = $stmt->insert_id;

        $qrFilename = "temp_qr_$attendanceCode.png";
        $qrFilePath = __DIR__ . "/temp/" . $qrFilename;

        $options = new QROptions([
            'version' => 5,
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_M,
            'scale' => 5,
            'imageBase64' => false,
        ]);

        $qrcode = new QRCode($options);
        $imageData = $qrcode->render($attendanceCode);
        file_put_contents($qrFilePath, $imageData);

        $attendanceSession = [
            'id' => $sessionId,
            'code' => $attendanceCode,
            'file' => "temp/" . $qrFilename,
            'courseId' => $courseId
        ];

        // Fetch students for selected course
        $studentQuery = "SELECT SymbolNo, firstName, lastName FROM tblstudents WHERE ClassId = (
            SELECT ClassId FROM tblclassarms WHERE Id = ?
        )";
        $stmt2 = $conn->prepare($studentQuery);
        $stmt2->bind_param("i", $courseId);
        $stmt2->execute();
        $result = $stmt2->get_result();
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
    }
}

// Save final attendance
if (isset($_POST['save_attendance']) && isset($_POST['session_id'])) {
    $sessionId = $_POST['session_id'];
    $courseId = $_POST['course_id'];

    $studentQuery = "SELECT SymbolNo FROM tblstudents WHERE ClassId = (
        SELECT ClassId FROM tblclassarms WHERE Id = ?
    )";
    $stmt = $conn->prepare($studentQuery);
    $stmt->bind_param("i", $courseId);
    $stmt->execute();
    $result = $stmt->get_result();

    $now = date('Y-m-d H:i:s');
    while ($row = $result->fetch_assoc()) {
        $symbolNo = $row['SymbolNo'];

        // Check if student marked present in temp table
        $check = $conn->prepare("SELECT 1 FROM tblattendance_temp WHERE SessionId = ? AND SymbolNo = ?");
        $check->bind_param("is", $sessionId, $symbolNo);
        $check->execute();
        $checkResult = $check->get_result();

        $status = ($checkResult->num_rows > 0) ? 1 : 0;

        $stmtInsert = $conn->prepare("INSERT INTO tblattendance (SymbolNo, CourseId, status, dateTimeTaken) VALUES (?, ?, ?, ?)");
        $stmtInsert->bind_param("siss", $symbolNo, $courseId, $status, $now);
        $stmtInsert->execute();
    }

        // Clean up temp table for session
        $clean=$conn->prepare("DELETE FROM tblattendance_temp WHERE SessionId = ?");
        $clean->bind_param("i", $sessionId);
        $clean->execute();

    // Close the session
    $conn->query("UPDATE tblattendance_sessions SET Status = 'closed' WHERE Id = $sessionId");

    header("Location: takeAttendance.php?success=1");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Take Attendance</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="img/logo/attnlg.jpg" rel="icon">
  <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" />
  <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" />
  <link href="css/ruang-admin.min.css" rel="stylesheet" />
</head>

<body id="page-top">
  <div id="wrapper">
    <?php include "Includes/sidebar.php"; ?>
    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">
        <?php include "Includes/topbar.php"; ?>
        <div class="container-fluid" id="container-wrapper">
          <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Take Attendance (<?= date("m-d-Y") ?>)</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active">Take Attendance</li>
            </ol>
          </div>

          <div class="row">
            <div class="col-lg-12">
              <div class="card mb-4">
                <div class="card-header">
                  <h6>Select Course</h6>
                </div>
                <div class="card-body">
                  <form method="post">
                    <div class="form-group">
                      <label>Course</label>
                      <select class="form-control" name="courseId" required>
                        <option value="">Select course</option>
                        <?php foreach ($courses as $c): ?>
                          <option value="<?= $c['Id'] ?>">
                            <?= $c['CourseName'] . ' - ' . $c['Program'] . ' - ' . $c['Year(Batch)'] . ' - ' . $c['section'] ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <button type="submit" name="start_session" class="btn btn-primary">Start Session</button>
                  </form>
                </div>
              </div>

              <?php if ($attendanceSession): ?>
                <div class="card mb-4">
                  <div class="card-header">
                    <h6>Session Code: <?= $attendanceSession['code'] ?> (Valid 5 min)</h6>
                  </div>
                  <div class="card-body text-center">
                    <img src="<?= $attendanceSession['file'] ?>" alt="QR Code" />
                  </div>
                </div>

                <form method="post">
                  <input type="hidden" name="session_id" value="<?= $attendanceSession['id'] ?>">
                  <input type="hidden" name="course_id" value="<?= $attendanceSession['courseId'] ?>">

                  <div class="card mb-4">
                    <div class="card-header"><h6>Student Checklist</h6></div>
                    <div class="card-body">
                      <?php if (count($students) > 0): ?>
                        <table class="table table-bordered">
                          <thead>
                            <tr><th>Mark</th><th>Symbol No</th><th>Name</th></tr>
                          </thead>
                          <tbody id="student-checklist">
                            <?php foreach ($students as $s): ?>
                              <tr id="row-<?= $s['SymbolNo'] ?>">
                                <td><input type="checkbox" name="present[]" value="<?= $s['SymbolNo'] ?>" id="chk-<?= $s['SymbolNo'] ?>"></td>
                                <td><?= $s['SymbolNo'] ?></td>
                                <td><?= $s['firstName'] . ' ' . $s['lastName'] ?></td>
                              </tr>
                            <?php endforeach; ?>
                          </tbody>
                        </table>
                        <button type="submit" name="save_attendance" class="btn btn-success">Save Attendance</button>
                      <?php else: ?>
                        <p>No students found.</p>
                      <?php endif; ?>
                    </div>
                  </div>
                </form>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <?php include "Includes/footer.php"; ?>
    </div>
  </div>

  <script src="../vendor/jquery/jquery.min.js"></script>
  <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
  <script src="js/ruang-admin.min.js"></script>
  <script>
  <?php if ($attendanceSession): ?>
  // Poll every 5 seconds to update attendance checklist
  function fetchMarkedStudents() {
    fetch('fetchMarkedStudent.php?session_id=<?= $attendanceSession['id'] ?>')
      .then(response => response.json())
      .then(data => {
        if (data.success && Array.isArray(data.markedStudents)) {
          // Uncheck all first (optional)
          document.querySelectorAll('#student-checklist input[type=checkbox]').forEach(cb => {
            cb.checked = false;
            cb.closest('tr').style.background = '';
          });
          // Check those present
          data.markedStudents.forEach(symbolNo => {
            const checkbox = document.getElementById('chk-' + symbolNo);
            if (checkbox) {
              checkbox.checked = true;
              checkbox.closest('tr').style.background = '#d4edda'; // green background
            }
          });
        }
      })
      .catch(err => console.error('Error fetching marked students:', err));
  }

  // Initial fetch and then every 5 seconds
  fetchMarkedStudents();
  setInterval(fetchMarkedStudents, 5000);
  <?php endif; ?>

  // Optional: highlight rows when manually toggling checkbox
  document.querySelectorAll('#student-checklist input[type=checkbox]').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
      if (this.checked) {
        this.closest('tr').style.background = '#d4edda';
      } else {
        this.closest('tr').style.background = '';
      }
    });
  });
</script>

</body>
</html>
