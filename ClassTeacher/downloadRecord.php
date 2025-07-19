<?php 
include '../Includes/dbcon.php';
include '../Includes/session.php';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download'])) {
    $recordType = $_POST['recordType'];
    $courseId = $_POST['course'];
    $filename = "Attendance_list_";

    if ($recordType === 'daily') {
        $dateTaken = $_POST['dateTaken'];
        $filename .= $dateTaken;
        
        $query = "SELECT tblattendance.SymbolNo, tblstudents.firstName, tblstudents.lastName, tblattendance.status, tblattendance.dateTimeTaken
                  FROM tblattendance
                  INNER JOIN tblstudents ON tblstudents.SymbolNo = tblattendance.SymbolNo
                  WHERE DATE(tblattendance.dateTimeTaken) = '$dateTaken' 
                  AND tblattendance.courseId = '$courseId'";
    } else {
        $filename .= "overall";

        $query = "SELECT tblstudents.SymbolNo, tblstudents.firstName, tblstudents.lastName,
                  COUNT(tblattendance.SymbolNo) as totalClasses,
                  SUM(tblattendance.status = '1') as presentDays,
                  SUM(tblattendance.status = '0') as absentDays,
                  (SUM(tblattendance.status = '1') / COUNT(tblattendance.SymbolNo)) * 100 as attendancePercentage
                  FROM tblattendance
                  INNER JOIN tblstudents ON tblstudents.SymbolNo = tblattendance.SymbolNo
                  WHERE tblattendance.courseId = '$courseId'
                  GROUP BY tblstudents.SymbolNo, tblstudents.firstName, tblstudents.lastName";
    }

    $filename .= ".xls";

    $ret = mysqli_query($conn, $query);

    // Check if records exist
    if (mysqli_num_rows($ret) > 0) {
        // Set headers to download the file
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=$filename");
        header("Pragma: no-cache");
        header("Expires: 0");

        // Output table headers
        echo "
        <table border='1'>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Symbol Number</th>
                    <th>First Name</th>
                    <th>Last Name</th>";

        if ($recordType === 'daily') {
            echo "<th>Status</th><th>Date</th>";
        } else {
            echo "<th>Total Classes</th><th>Present Days</th><th>Absent Days</th><th>Attendance Percentage</th>";
        }

        echo "</tr>
            </thead>
            <tbody>";

        // Output data rows
        $cnt = 1;
        while ($row = mysqli_fetch_assoc($ret)) {
            echo "<tr>
                    <td>{$cnt}</td>
                    <td>{$row['SymbolNo']}</td>
                    <td>{$row['firstName']}</td>
                    <td>{$row['lastName']}</td>";

            if ($recordType === 'daily') {
                $status = ($row['status'] == '1') ? "Present" : "Absent";
                echo "<td>{$status}</td><td>{$row['dateTimeTaken']}</td>";
            } else {
                echo "<td>{$row['totalClasses']}</td>
                      <td>{$row['presentDays']}</td>
                      <td>{$row['absentDays']}</td>
                      <td>" . number_format($row['attendancePercentage'], 2) . "%</td>";
            }

            echo "</tr>";
            $cnt++;
        }

        echo "</tbody>
        </table>";
    } else {
        echo "No records found for the selected criteria.";
    }
    exit();
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
                        <h1 class="h3 mb-0 text-gray-800">Download Class Attendance</h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Download Class Attendance</li>
                        </ol>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <!-- Form Basic -->
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Download Class Attendance</h6>
                                </div>
                                <div class="card-body">
                                    <form method="post">
                                        <div class="form-group row mb-3">
                                            <div class="col-xl-6">
                                                <label class="form-control-label">Select Course<span class="text-danger ml-2">*</span></label>
                                                <select class="form-control" id="courseSelect" name="course" required>
                                                    <option value="">Select a course</option>
                                                    <?php foreach ($courses as $course): ?>
                                                        <option value="<?= $course['Id'] ?>">
                                                            <?= $course['CourseName'] . '-' . $course['Program'] . '-' . $course['Year(Batch)'] . '-' . $course['section'] ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-xl-6">
                                                <label class="form-control-label">Select Record Type<span class="text-danger ml-2">*</span></label>
                                                <select class="form-control" id="recordTypeSelect" name="recordType" required>
                                                    <option value="daily">Daily</option>
                                                    <option value="overall">Overall</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group row mb-3" id="dateGroup">
                                            <div class="col-xl-6">
                                                <label class="form-control-label">Select Date<span class="text-danger ml-2">*</span></label>
                                                <input type="date" class="form-control" name="dateTaken">
                                            </div>
                                        </div>
                                        <button type="submit" name="download" class="btn btn-primary">Download Attendance</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--Row-->
                </div>
                <!---Container Fluid-->
            </div>
            <!-- Footer -->
            <?php include "Includes/footer.php"; ?>
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
        $(document).ready(function() {
            $('#dataTable').DataTable(); // ID From dataTable 
            $('#dataTableHover').DataTable(); // ID From dataTable with Hover

            $('#recordTypeSelect').on('change', function() {
                if ($(this).val() === 'daily') {
                    $('#dateGroup').show();
                    $('#dateGroup input').attr('required', true);
                } else {
                    $('#dateGroup').hide();
                    $('#dateGroup input').removeAttr('required');
                }
            }).trigger('change');
        });
    </script>
</body>

</html>
