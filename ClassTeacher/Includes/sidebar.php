<ul class="navbar-nav sidebar sidebar-light accordion" id="accordionSidebar">
  <!-- Brand -->
  <a class="sidebar-brand d-flex align-items-center bg-gradient-primary justify-content-center" href="index.php">
    <div class="sidebar-brand-icon">
      <img src="img/logo/attnlg.jpg">
    </div>
    <div class="sidebar-brand-text mx-3">E-Attendance</div>
  </a>

  <!-- Dashboard -->
  <hr class="sidebar-divider my-0">
  <li class="nav-item active">
    <a class="nav-link" href="index.php">
      <i class="fas fa-fw fa-tachometer-alt"></i>
      <span>Dashboard</span>
    </a>
  </li>

  <!-- Students Section -->
  <hr class="sidebar-divider">
  <div class="sidebar-heading">Students</div>
  <li class="nav-item">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseBootstrap2"
      aria-expanded="true" aria-controls="collapseBootstrap2">
      <i class="fas fa-user-graduate"></i>
      <span>Manage Students</span>
    </a>
    <div id="collapseBootstrap2" class="collapse" aria-labelledby="headingBootstrap" data-parent="#accordionSidebar">
      <div class="bg-white py-2 collapse-inner rounded">
        <h6 class="collapse-header">Manage Students</h6>
        <a class="collapse-item" href="viewStudents.php">View Students</a>
      </div>
    </div>
  </li>

  <!-- Attendance Section -->
  <hr class="sidebar-divider">
  <div class="sidebar-heading">Attendance</div>
  <li class="nav-item">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseBootstrapcon"
      aria-expanded="true" aria-controls="collapseBootstrapcon">
      <i class="fa fa-calendar-alt"></i>
      <span>Manage Attendance</span>
    </a>
    <div id="collapseBootstrapcon" class="collapse" aria-labelledby="headingBootstrap" data-parent="#accordionSidebar">
      <div class="bg-white py-2 collapse-inner rounded">
        <h6 class="collapse-header">Manage Attendance</h6>
        <a class="collapse-item" href="takeAttendance.php">Take Attendance</a>
        <a class="collapse-item" href="viewAttendance.php">View Class Attendance</a>
        <a class="collapse-item" href="viewStudentAttendance.php">View Student Attendance</a>
        <a class="collapse-item" href="downloadRecord.php">Today's Report (xls)</a>
      </div>
    </div>
  </li>

  <!-- Virtual Class -->
  <hr class="sidebar-divider">
  <div class="sidebar-heading">Virtual Class</div>
  <li class="nav-item">
    <a class="nav-link" href="createVirtualClass.php">
      <i class="fas fa-video"></i>
      <span>Create Virtual Class</span>
    </a>
  </li>

  <!-- Academic Materials -->
  <hr class="sidebar-divider">
  <div class="sidebar-heading">Academic Materials</div>

  <!-- Upload Notes -->
  <li class="nav-item">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseNotes"
      aria-expanded="true" aria-controls="collapseNotes">
      <i class="fas fa-upload"></i>
      <span>Upload Notes</span>
    </a>
    <div id="collapseNotes" class="collapse" aria-labelledby="headingNotes" data-parent="#accordionSidebar">
      <div class="bg-white py-2 collapse-inner rounded">
        <h6 class="collapse-header">Upload Notes</h6>
        <a class="collapse-item" href="uploadNotes.php">Upload Notes</a>
        <a class="collapse-item" href="viewNotes.php">View Notes</a>
      </div>
    </div>
  </li>

  <!-- Assignments Section -->
  <li class="nav-item">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseAssignments"
      aria-expanded="true" aria-controls="collapseAssignments">
      <i class="fas fa-tasks"></i>
      <span>Manage Assignments</span>
    </a>
    <div id="collapseAssignments" class="collapse" aria-labelledby="headingAssignments" data-parent="#accordionSidebar">
      <div class="bg-white py-2 collapse-inner rounded">
        <h6 class="collapse-header">Assignments</h6>
        <a class="collapse-item" href="uploadAssignment.php">Upload Assignment</a>
        <a class="collapse-item" href="viewAssignments.php">View Assignments</a>
        <a class="collapse-item" href="submissions.php">Submissions</a>
      </div>
    </div>
  </li>

  <!-- Class Chatrooms -->
<hr class="sidebar-divider">
<div class="sidebar-heading">Class Chatrooms</div>
<?php
$userId = $_SESSION['userId'];
$userRole = $_SESSION['userRole']; // 'student' or 'teacher'

if ($userRole === 'ClassTeacher') {
    $query = "SELECT DISTINCT c.Id,
                 CONCAT(c.Program, ' ', c.`Year(Batch)`, '-', c.section) AS ClassDisplay
          FROM tblclass c
          JOIN tblclassarms ca ON ca.ClassId = c.Id
          WHERE ca.AssignedTo = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $userId);
// AssignedTo is VARCHAR
} else {
    $query = "SELECT c.Id,
                     CONCAT(c.Program, ' ', c.`Year(Batch)`, '-', c.section) AS ClassDisplay
              FROM tblclass c
              INNER JOIN tblstudents s ON s.ClassId = c.Id
              WHERE s.SymbolNo = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo '<li class="nav-item">
                <a class="nav-link" href="classChat.php?classId=' . $row['Id'] . '">
                    <i class="fas fa-comments"></i>
                    <span>' . htmlspecialchars($row['ClassDisplay']) . ' Chatroom</span>
                </a>
              </li>';
    }
} else {
    echo '<li class="nav-item">
            <a class="nav-link disabled" href="#">
                <i class="fas fa-comments"></i>
                <span>No Classes Found</span>
            </a>
          </li>';
}
$stmt->close();
?>
<hr class="sidebar-divider d-none d-md-block">

  <!-- Logout -->
  <li class="nav-item">
    <a class="nav-link" href="../Includes/logout.php">
      <i class="fas fa-sign-out-alt"></i>
      <span>Logout</span>
    </a>
  </li>
</ul>
