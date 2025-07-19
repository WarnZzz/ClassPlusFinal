<?php 
include '../Includes/dbcon.php';
include '../Includes/session.php';
$statusMsg = "";

$teacherId = $_SESSION['userId'];

// Fetch courses taught by the teacher
$query = "SELECT tblclassarms.Id, tblclassarms.CourseName, tblclass.Program, tblclass.`Year(Batch)`,tblclass.section
          FROM tblclassteacher
          INNER JOIN tblclassarms ON tblclassteacher.Id = tblclassarms.AssignedTo
          INNER JOIN tblclass ON tblclassarms.ClassId = tblclass.Id
          WHERE tblclassteacher.Id = '$teacherId'";
$result = $conn->query($query);
$courses = [];
while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="">
  <meta name="author" content="">
  <link href="img/logo/attnlg.jpg" rel="icon">
  <title>Dashboard</title>
  <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="css/ruang-admin.min.css" rel="stylesheet">
</head>

<body id="page-top">
  <div id="wrapper">
    <!-- Sidebar -->
      <?php include "Includes/sidebar.php";?>
    <!-- Sidebar -->
    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">
        <!-- TopBar -->
       <?php include "Includes/topbar.php";?>
        <!-- Topbar -->

        <!-- Container Fluid-->
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
              <!-- Form Basic -->
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">View Class Attendance</h6>
                    <?php echo $statusMsg; ?>
                </div>
                <div class="card-body">
                  <form method="post">
                    <div class="form-group row mb-3">
                        <div class="col-xl-6">
                            <label class="form-control-label">Select Date<span class="text-danger ml-2">*</span></label>
                            <input type="date" class="form-control" name="dateTaken" required>
                        </div>
                        <div class="col-xl-6">
                            <label class="form-control-label">Select Course<span class="text-danger ml-2">*</span></label>
                            <select class="form-control" id="courseSelect" name="course">
              <option value="">Select a course</option>
             <?php foreach ($courses as $course) 
                echo'<option value="'.$course['Id'].'" >'.$course['CourseName'].'-'.$course['Program'].'-'.$course['Year(Batch)'].'-'.$course['section'].'</option>';
                ?>
            </select>
                        </div>
                    </div>
                    <button type="submit" name="view" class="btn btn-primary">View Attendance</button>
                  </form>
                </div>
              </div>

              <?php
              if(isset($_POST['view'])) {
                  $dateTaken = $_POST['dateTaken'];
                  $courseId = $_POST['course'];

                  $query = "SELECT tblattendance.SymbolNo, tblstudents.firstName, tblstudents.lastName, 
                 tblattendance.status, tblattendance.dateTimeTaken
                    FROM tblattendance
                    INNER JOIN tblstudents ON tblstudents.SymbolNo = tblattendance.SymbolNo
                    WHERE DATE(tblattendance.dateTimeTaken) = '$dateTaken'
                    AND tblattendance.courseId = '$courseId'";
;

                  $rs = $conn->query($query);
                  $num = $rs->num_rows;
                  $sn = 0;
                  $status = "";

                  if ($num > 0) {
                      echo '
                      <div class="row">
                        <div class="col-lg-12">
                          <div class="card mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                              <h6 class="m-0 font-weight-bold text-primary">Class Attendance</h6>
                            </div>
                            <div class="table-responsive p-3">
                              <table class="table align-items-center table-flush table-hover" id="dataTableHover">
                                <thead class="thead-light">
                                  <tr>
                                    <th>#</th>
                                    <th>Symbol Number</th>
                                    <th>First Name</th>
                                    <th>Last Name</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                  </tr>
                                </thead>
                                <tbody>';
                      
                      while ($rows = $rs->fetch_assoc()) {
                          $status = ($rows['status'] == 1) ? "Present" : "Absent";
                          $colour = ($rows['status'] == 1) ? "#00FF00" : "#FF0000";

                          $sn++;
                          echo "
                              <tr>
                                <td>".$sn."</td>
                                <td>".$rows['SymbolNo']."</td>
                                <td>".$rows['firstName']."</td>
                                <td>".$rows['lastName']."</td>
                                <td style='background-color:".$colour."'>".$status."</td>
                                <td>".$rows['dateTimeTaken']."</td>
                              </tr>";
                      }
                      
                      echo '
                                </tbody>
                              </table>
                            </div>
                          </div>
                        </div>
                      </div>';
                  } else {
                      echo "<div class='alert alert-danger' role='alert'>No Record Found!</div>";
                  }
              }
              ?>

            </div>
          </div>
          <!--Row-->

        </div>
        <!---Container Fluid-->
      </div>
      <!-- Footer -->
       <?php include "Includes/footer.php";?>
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
  <!-- Page level plugins -->
  <script src="../vendor/datatables/jquery.dataTables.min.js"></script>
  <script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>

  <!-- Page level custom scripts -->
  <script>
    $(document).ready(function () {
      $('#dataTable').DataTable(); // ID From dataTable 
      $('#dataTableHover').DataTable(); // ID From dataTable with Hover
    });
  </script>
</body>

</html>
