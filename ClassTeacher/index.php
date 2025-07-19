<?php 
include '../Includes/dbcon.php';
include '../Includes/session.php';

$teacherId = $_SESSION['userId'];

// Delete past events only (events have dates)
mysqli_query($conn, "DELETE FROM tblevents WHERE EndDateTime < NOW()");

// Fetch teacher info (if needed)
$query = "SELECT * FROM tblclassteacher WHERE Id = '$teacherId'";
$rs = $conn->query($query);
$teacher = $rs->fetch_assoc();

// Check default password redirect
$query = "SELECT password FROM tblclassteacher WHERE Id = '$teacherId'";
$result = mysqli_query($conn, $query);
$sample1 = md5("pass123");

if ($result) {
    $row = mysqli_fetch_assoc($result);
    if ($row['password'] === $sample1) {
        header("Location: change_password.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <meta name="description" content="" />
  <meta name="author" content="" />
  <link href="img/logo/attnlg.jpg" rel="icon" />
  <title>Dashboard</title>
  <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css" />
  <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
  <link href="css/ruang-admin.min.css" rel="stylesheet" />
</head>

<body id="page-top">
  <div id="wrapper">
    <!-- Sidebar -->
    <?php include "Includes/sidebar.php"; ?>
    <!-- Sidebar -->                       

    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">
        <!-- TopBar -->
        <?php include "Includes/topbar.php"; ?>
        <!-- Topbar -->

        <!-- Container Fluid-->
        <div class="container-fluid" id="container-wrapper">
          <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Teacher Dashboard</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
            </ol>
          </div>

          <div class="row mb-3">
            <!-- Students Card -->
            <?php 
            $query1 = mysqli_query($conn, "SELECT * FROM tblstudents s 
              JOIN tblclassarms ca ON s.ClassId = ca.ClassId
              JOIN tblclassteacher t ON ca.AssignedTo = t.Id
              JOIN tblclass c ON ca.ClassId = c.Id
              WHERE t.Id = '$teacherId'");
            $students = mysqli_num_rows($query1);
            ?>
            <div class="col-xl-3 col-md-6 mb-4">
              <div class="card h-100">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-uppercase mb-1">Students</div>
                      <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800"><?php echo $students; ?></div>
                    </div>
                    <div class="col-auto">
                      <i class="fas fa-users fa-2x text-info"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Courses Card -->
            <?php 
            $query2 = mysqli_query($conn, "SELECT * FROM tblclassarms ca
              JOIN tblclassteacher t ON ca.AssignedTo = t.Id
              WHERE t.Id = '$teacherId'");
            $classCount = mysqli_num_rows($query2);
            ?>
            <div class="col-xl-3 col-md-6 mb-4">
              <div class="card h-100">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-uppercase mb-1">Courses</div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $classCount; ?></div>
                    </div>
                    <div class="col-auto">
                      <i class="fas fa-chalkboard fa-2x text-primary"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Upcoming Routines Section -->
          <div class="row mb-3">
            <div class="col-lg-12">
              <div class="card">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">Upcoming Routines</h6>
                </div>
                <div class="table-responsive">
                  <table class="table align-items-center table-flush table-hover">
                    <thead class="thead-light">
                      <tr>
                        <th>Day</th>
                        <th>Course</th>
                        <th>Time Slot</th>
                        <th>Section</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      // Get subjects assigned to this teacher
                      $subjectArr = [];
                      $resSubjects = mysqli_query($conn, "SELECT CourseName FROM tblclassarms WHERE AssignedTo = '$teacherId'");
                      while ($subj = mysqli_fetch_assoc($resSubjects)) {
                          $subjectArr[] = "'" . mysqli_real_escape_string($conn, $subj['CourseName']) . "'";
                      }

                      if (!empty($subjectArr)) {
                          $subjectList = implode(',', $subjectArr);

                          // Get class Ids assigned to this teacher
                          $classIds = [];
                          $classRes = mysqli_query($conn, "SELECT ClassId FROM tblclassarms WHERE AssignedTo = '$teacherId'");
                          while ($row = mysqli_fetch_assoc($classRes)) {
                              $classIds[] = $row['ClassId'];
                          }

                          if (!empty($classIds)) {
                              $classIdsStr = implode(',', $classIds);

                              // Show all routines assigned to this teacher (no deletion)
                              $routineSql = "
                              SELECT r.Day, r.Subject, r.TimeSlot, r.Section
                              FROM tblroutine r
                              JOIN tblclass c ON r.Program = c.Program AND r.Year_Batch = c.`Year(Batch)` AND r.Section = c.section
                              WHERE r.Subject IN ($subjectList)
                              AND c.Id IN ($classIdsStr)
                              ORDER BY 
                                CASE r.Day
                                  WHEN 'Monday' THEN 1
                                  WHEN 'Tuesday' THEN 2
                                  WHEN 'Wednesday' THEN 3
                                  WHEN 'Thursday' THEN 4
                                  WHEN 'Friday' THEN 5
                                  WHEN 'Saturday' THEN 6
                                  WHEN 'Sunday' THEN 7
                                END,
                                r.TimeSlot ASC
                              ";

                              $routineResult = mysqli_query($conn, $routineSql);

                              if (mysqli_num_rows($routineResult) > 0) {
                                  while ($routine = mysqli_fetch_assoc($routineResult)) {
                                      echo "<tr>";
                                      echo "<td>" . htmlspecialchars($routine['Day']) . "</td>";
                                      echo "<td>" . htmlspecialchars($routine['Subject']) . "</td>";
                                      echo "<td>" . htmlspecialchars($routine['TimeSlot']) . "</td>";
                                      echo "<td>" . htmlspecialchars($routine['Section']) . "</td>";
                                      echo "</tr>";
                                  }
                              } else {
                                  echo '<tr><td colspan="4" class="text-center">No upcoming routines found.</td></tr>';
                              }
                          } else {
                              echo '<tr><td colspan="4" class="text-center">No upcoming routines found.</td></tr>';
                          }
                      } else {
                          echo '<tr><td colspan="4" class="text-center">No upcoming routines found.</td></tr>';
                      }
                      ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <!-- Upcoming Events Section -->
          <div class="row mb-3">
            <div class="col-lg-12">
              <div class="card">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">Upcoming Events</h6>
                </div>
                <div class="table-responsive">
                  <table class="table align-items-center table-flush table-hover">
                    <thead class="thead-light">
                      <tr>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Event Date</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      // Set timezone to your server timezone
                      date_default_timezone_set('Asia/Kathmandu');
                      $currentDateTime = date('Y-m-d H:i:s');

                      $eventsSql = "SELECT * FROM tblevents WHERE EndDateTime >= '$currentDateTime' ORDER BY EventDate, StartDateTime";
                      $eventsResult = mysqli_query($conn, $eventsSql);

                      if ($eventsResult && mysqli_num_rows($eventsResult) > 0) {
                          while ($event = mysqli_fetch_assoc($eventsResult)) {
                              echo "<tr>";
                              echo "<td>" . htmlspecialchars($event['Title']) . "</td>";
                              echo "<td>" . nl2br(htmlspecialchars($event['Description'])) . "</td>";
                              echo "<td>" . htmlspecialchars($event['EventDate']) . "</td>";
                              echo "<td>" . date('H:i', strtotime($event['StartDateTime'])) . "</td>";
                              echo "<td>" . date('H:i', strtotime($event['EndDateTime'])) . "</td>";
                              echo "</tr>";
                          }
                      } else {
                          echo '<tr><td colspan="5" class="text-center">No upcoming events found.</td></tr>';
                      }
                      ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

        </div>
        <!---Container Fluid-->
      </div>

      <!-- Footer -->
      <?php include 'Includes/footer.php'; ?>
      <!-- Footer -->
    </div>
  </div>

  <!-- Scroll to top -->
  <a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
  </a>

  <script src="../vendor/jquery/jquery.min.js"></script>
  <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
  <script src="js/ruang-admin.min.js"></script>
  <script src="../vendor/chart.js/Chart.min.js"></script>
  <script src="js/demo/chart-area-demo.js"></script>
</body>

</html>