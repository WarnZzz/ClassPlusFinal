<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer-master/src/Exception.php';
require '../PHPMailer-master/src/PHPMailer.php';
require '../PHPMailer-master/src/SMTP.php';

include '../Includes/dbcon.php';
include '../Includes/session.php';

$statusMsg = "";
$statusClass = ""; // for alert class

$teacherId = $_SESSION['userId'];

// Fetch courses taught by the teacher
$query = "SELECT tblclassarms.Id, tblclassarms.CourseName, tblclass.Program, tblclass.`Year(Batch)`, tblclass.section
          FROM tblclassteacher
          INNER JOIN tblclassarms ON tblclassteacher.Id = tblclassarms.AssignedTo
          INNER JOIN tblclass ON tblclassarms.ClassId = tblclass.Id
          WHERE tblclassteacher.Id = '$teacherId'";
$result = $conn->query($query);
$courses = [];
while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}

// Handle sending emails if form posted with students list
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_mail']) && isset($_POST['students'])) {
    $students = explode(',', $_POST['students']);
    $emailAddresses = [];

    foreach ($students as $symbolNo) {
        $symbolNo = $conn->real_escape_string($symbolNo);
        $query = "SELECT emailAddress FROM tblstudents WHERE SymbolNo = '$symbolNo'";
        $result = $conn->query($query);
        if ($row = $result->fetch_assoc()) {
            $emailAddresses[] = $row['emailAddress'];
        }
    }

    if (!empty($emailAddresses)) {
        $mail = new PHPMailer(true);
        try {
            //Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'paudelranjan14@gmail.com';
            $mail->Password = 'mxxpxoivbkdauvlc';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            //Recipients
            $mail->setFrom('paudelranjan14@gmail.com', 'PokharaEngineeringCollege');
            foreach ($emailAddresses as $email) {
                $mail->addAddress($email);
            }

            // Content
            $mail->isHTML(false);
            $mail->Subject = 'Attendance Alert';
            $mail->Body = "Dear Student,\n\nYour attendance percentage is below the required threshold. Please take necessary actions to improve your attendance.\n\nBest Regards,\nYour Teacher";

            $mail->send();
            $statusMsg = "Emails sent successfully to students below threshold!";
            $statusClass = "alert-success";
        } catch (Exception $e) {
            $statusMsg = "Failed to send emails. Mailer Error: {$mail->ErrorInfo}";
            $statusClass = "alert-danger";
        }
    } else {
        $statusMsg = "No students found with attendance below the threshold.";
        $statusClass = "alert-warning";
    }
}

// Variables to hold attendance display data
$studentsBelowThreshold = [];
$showTable = false;

if (isset($_POST['view']) && !isset($_POST['send_mail'])) {
    $courseId = $_POST['course'];
    $threshold = $_POST['threshold'];

    // Calculate attendance percentage for each student
    $query = "SELECT tblstudents.SymbolNo, tblstudents.firstName, tblstudents.lastName,
              SUM(CASE WHEN tblattendance.status = '1' THEN 1 ELSE 0 END) AS presentDays,
              COUNT(tblattendance.status) AS totalDays
              FROM tblattendance
              INNER JOIN tblstudents ON tblstudents.SymbolNo = tblattendance.SymbolNo
              WHERE tblattendance.courseId = '$courseId'
              GROUP BY tblstudents.SymbolNo, tblstudents.firstName, tblstudents.lastName";

    $rs = $conn->query($query);
    $num = $rs->num_rows;
    $sn = 0;

    if ($num > 0) {
        $showTable = true;
        while ($rows = $rs->fetch_assoc()) {
            $attendancePercentage = ($rows['totalDays'] > 0) ? ($rows['presentDays'] / $rows['totalDays']) * 100 : 0;
            if ($attendancePercentage < $threshold) {
                $studentsBelowThreshold[$rows['SymbolNo']] = [
                    'sn' => ++$sn,
                    'SymbolNo' => $rows['SymbolNo'],
                    'firstName' => $rows['firstName'],
                    'lastName' => $rows['lastName'],
                    'attendancePercentage' => round($attendancePercentage, 2)
                ];
            }
        }

        if (empty($studentsBelowThreshold)) {
            $statusMsg = "No students found below the attendance threshold.";
            $statusClass = "alert-info";
        }
    } else {
        $statusMsg = "No attendance records found for the selected course.";
        $statusClass = "alert-warning";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <title>View Class Attendance</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="img/logo/attnlg.jpg" rel="icon" />
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
            <h1 class="h3 mb-0 text-gray-800">View Class Attendance</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">View Class Attendance</li>
            </ol>
          </div>

          <div class="row">
            <div class="col-lg-12">
              <div class="card mb-4">
                <div
                  class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">View Class Attendance</h6>
                </div>
                <div class="card-body">
                  <?php if ($statusMsg): ?>
                    <div class="alert <?= $statusClass ?>" role="alert"><?= $statusMsg ?></div>
                  <?php endif; ?>
                  <form method="post">
                    <div class="form-group row mb-3">
                      <div class="col-xl-6">
                        <label class="form-control-label">Select Course<span
                            class="text-danger ml-2">*</span></label>
                        <select class="form-control" id="courseSelect" name="course" required>
                          <option value="">Select a course</option>
                          <?php foreach ($courses as $course) : ?>
                            <option value="<?= $course['Id'] ?>"
                              <?= (isset($_POST['course']) && $_POST['course'] == $course['Id']) ? 'selected' : '' ?>>
                              <?= htmlspecialchars($course['CourseName'] . '-' . $course['Program'] . '-' . $course['Year(Batch)'] . '-' . $course['section']) ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="col-xl-6">
                        <label class="form-control-label">Threshold Attendance Percentage<span
                            class="text-danger ml-2">*</span></label>
                        <input type="number" class="form-control" name="threshold" min="0" max="100" required
                          value="<?= isset($_POST['threshold']) ? (int)$_POST['threshold'] : '' ?>" />
                      </div>
                    </div>
                    <button type="submit" name="view" class="btn btn-primary">View Attendance</button>
                  </form>
                </div>
              </div>

              <?php if ($showTable && count($studentsBelowThreshold) > 0) : ?>
                <div class="row">
                  <div class="col-lg-12">
                    <div class="card mb-4">
                      <div
                        class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Students Below Threshold</h6>
                      </div>
                      <div class="table-responsive p-3">
                        <table
                          class="table align-items-center table-flush table-hover"
                          id="dataTableHover">
                          <thead class="thead-light">
                            <tr>
                              <th>#</th>
                              <th>Symbol Number</th>
                              <th>First Name</th>
                              <th>Last Name</th>
                              <th>Attendance Percentage</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php foreach ($studentsBelowThreshold as $student) : ?>
                              <tr>
                                <td><?= $student['sn'] ?></td>
                                <td><?= htmlspecialchars($student['SymbolNo']) ?></td>
                                <td><?= htmlspecialchars($student['firstName']) ?></td>
                                <td><?= htmlspecialchars($student['lastName']) ?></td>
                                <td><?= $student['attendancePercentage'] ?>%</td>
                              </tr>
                            <?php endforeach; ?>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>

                <form method="post" class="mb-4">
                  <input type="hidden" name="students" value="<?= implode(',', array_keys($studentsBelowThreshold)) ?>">
                  <button type="submit" name="send_mail" class="btn btn-warning">Send Mail to Students Below Threshold</button>
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
  <script src="../vendor/datatables/jquery.dataTables.min.js"></script>
  <script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>
  <script>
    $(document).ready(function() {
      $('#dataTableHover').DataTable();
    });
  </script>
</body>

</html>
