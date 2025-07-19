<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';

// Student info from session
$student_name = $_SESSION['firstName'] . ' ' . $_SESSION['lastName'];
$student_id = $_SESSION['userId']; // assuming SymbolNo or userId used consistently

// Delete past events (if needed)
mysqli_query($conn, "DELETE FROM tblevents WHERE EndDateTime < NOW()");

// Function to calculate overall attendance percentage for a student
function calculateOverallAttendance($student_id, $conn) {
    $query = "SELECT COUNT(*) AS total_attendance, SUM(status) AS attended, COUNT(CASE WHEN status = 1 THEN 1 END) AS present_days FROM tblattendance WHERE SymbolNo = $student_id";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);

    $total_attendance = $row['total_attendance'];
    $attended = $row['attended'];
    $present_days = $row['present_days'];
    $absent_days = $total_attendance - $present_days;

    $attendance_data = [
        'attendance_percentage' => ($total_attendance > 0) ? round(($attended / $total_attendance) * 100, 2) : 0,
        'total_class_days' => $total_attendance,
        'present_days' => $present_days,
        'absent_days' => $absent_days,
    ];

    return $attendance_data;
}

// Fetch the student class details (program, year, section) for routine filtering
$studentClassQuery = "
    SELECT c.Program, c.`Year(Batch)`, c.section
    FROM tblstudents s
    JOIN tblclass c ON s.ClassId = c.Id
    WHERE s.SymbolNo = $student_id
    LIMIT 1";
$studentClassResult = mysqli_query($conn, $studentClassQuery);
$studentClass = mysqli_fetch_assoc($studentClassResult);

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
    <title>Student Dashboard</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css" />
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="css/ruang-admin.min.css" rel="stylesheet" />
    <style>
        .chart-container {
            position: relative;
            margin: auto;
            height: 300px;
            width: 80%;
        }
    </style>
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
                        <h1 class="h3 mb-0 text-gray-800">Welcome, <?php echo htmlspecialchars($student_name); ?></h1>
                    </div>

                    <!-- Overall Attendance -->
                    <div class="row mb-3">
                        <div class="col-lg-6">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Overall Attendance</h6>
                                </div>
                                <div class="card-body">
                                    <?php
                                    $attendance_data = calculateOverallAttendance($student_id, $conn);
                                    ?>
                                    <div class="text-center">
                                        <h2 class="text-primary"><?php echo $attendance_data['attendance_percentage']; ?>%</h2>
                                        <p class="text-muted">Overall Attendance</p>
                                    </div>
                                    <div class="chart-container">
                                        <canvas id="overallAttendanceChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Attendance Details</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p>Total Classes: <strong><?php echo $attendance_data['total_class_days']; ?></strong></p>
                                            <p>Present : <strong><?php echo $attendance_data['present_days']; ?></strong></p>
                                            <p>Absent : <strong><?php echo $attendance_data['absent_days']; ?></strong></p>
                                        </div>
                                        <div class="col-md-6">
                                            <!-- Additional details if needed -->
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
                                            if ($studentClass) {
                                                $program = mysqli_real_escape_string($conn, $studentClass['Program']);
                                                $year_batch = $studentClass['Year(Batch)'];
                                                $section = mysqli_real_escape_string($conn, $studentClass['section']);

                                                // Fetch routines matching student's program, year, and section
                                                $routineSql = "
                                                    SELECT Day, Subject, TimeSlot, Section
                                                    FROM tblroutine
                                                    WHERE Program = '$program' AND Year_Batch = '$year_batch' AND Section = '$section'
                                                    ORDER BY 
                                                        CASE Day
                                                            WHEN 'Monday' THEN 1
                                                            WHEN 'Tuesday' THEN 2
                                                            WHEN 'Wednesday' THEN 3
                                                            WHEN 'Thursday' THEN 4
                                                            WHEN 'Friday' THEN 5
                                                            WHEN 'Saturday' THEN 6
                                                            WHEN 'Sunday' THEN 7
                                                        END,
                                                        TimeSlot ASC
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
                                                echo '<tr><td colspan="4" class="text-center">Student class info not found.</td></tr>';
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
                                            $eventsSql = "SELECT * FROM tblevents WHERE EndDateTime >= NOW() ORDER BY EventDate, StartDateTime";
                                            $eventsResult = mysqli_query($conn, $eventsSql);

                                            if (mysqli_num_rows($eventsResult) > 0) {
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // Function to initialize and render the pie chart
        function renderPieChart(attendancePercentage) {
            var overallAttendanceChart = document.getElementById('overallAttendanceChart').getContext('2d');
            var myOverallAttendanceChart = new Chart(overallAttendanceChart, {
                type: 'pie',
                data: {
                    datasets: [{
                        data: [attendancePercentage, 100 - attendancePercentage],
                        backgroundColor: ['#4e73df', '#e74a3b'],
                        hoverBackgroundColor: ['#2e59d9', '#e74a3b'],
                        borderWidth: 2,
                        hoverBorderColor: 'rgba(234, 236, 244, 1)',
                    }],
                    labels: ['Attended', 'Absent'],
                },
                options: {
                    maintainAspectRatio: false,
                    tooltips: {
                        backgroundColor: 'rgb(255,255,255)',
                        bodyFontColor: '#858796',
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        xPadding: 15,
                        yPadding: 15,
                        displayColors: false,
                        caretPadding: 10,
                    },
                    legend: {
                        display: false,
                    },
                    cutoutPercentage: 80,
                },
            });
        }

        // Call the function with attendance percentage data
        renderPieChart(<?php echo $attendance_data['attendance_percentage']; ?>);
    </script>

</body>

</html>