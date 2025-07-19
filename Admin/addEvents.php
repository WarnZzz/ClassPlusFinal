<?php
ob_start(); // To prevent "headers already sent" errors
include '../Includes/dbcon.php';
include '../Includes/session.php';

$statusMsg = "";

// Handle Delete Event
if (isset($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);
    $stmtDel = $conn->prepare("DELETE FROM tblevents WHERE Id = ?");
    $stmtDel->bind_param("i", $deleteId);
    $stmtDel->execute();
    $stmtDel->close();
    header("Location: addEvents.php");
    exit();
}

// Handle Add or Update Event
if (isset($_POST['saveEvent'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $eventDate = $_POST['eventDate'];
    $startDateTime = $_POST['startDateTime'];
    $endDateTime = $_POST['endDateTime'];

    if (!empty($_POST['eventId'])) {
        // Update existing event
        $eventId = intval($_POST['eventId']);
        $stmt = $conn->prepare("UPDATE tblevents SET Title = ?, Description = ?, EventDate = ?, StartDateTime = ?, EndDateTime = ? WHERE Id = ?");
        $stmt->bind_param("sssssi", $title, $description, $eventDate, $startDateTime, $endDateTime, $eventId);
        if ($stmt->execute()) {
            $stmt->close();
            header("Location: addEvents.php");
            exit();
        } else {
            $statusMsg = "<div class='alert alert-danger'>Error updating event. Please try again.</div>";
        }
    } else {
        // Insert new event
        $stmt = $conn->prepare("INSERT INTO tblevents (Title, Description, EventDate, StartDateTime, EndDateTime) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $title, $description, $eventDate, $startDateTime, $endDateTime);
        if ($stmt->execute()) {
            $stmt->close();
            header("Location: addEvents.php");
            exit();
        } else {
            $statusMsg = "<div class='alert alert-danger'>Error adding event. Please try again.</div>";
        }
    }
}

// If editing, fetch existing event data to populate the form
$editEvent = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmtEdit = $conn->prepare("SELECT * FROM tblevents WHERE Id = ?");
    $stmtEdit->bind_param("i", $editId);
    $stmtEdit->execute();
    $resultEdit = $stmtEdit->get_result();
    if ($resultEdit->num_rows > 0) {
        $editEvent = $resultEdit->fetch_assoc();
    }
    $stmtEdit->close();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Add Events</title>
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
                        <h1 class="h3 mb-0 text-gray-800"><?php echo $editEvent ? "Edit Event" : "Add Event"; ?></h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><?php echo $editEvent ? "Edit Event" : "Add Events"; ?></li>
                        </ol>
                    </div>

                    <div class="row">
                        <div class="col-lg-10 offset-lg-1">
                            <div class="card mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary"><?php echo $editEvent ? "Edit Event Form" : "New Event Form"; ?></h6>
                                    <?php echo $statusMsg; ?>
                                </div>
                                <div class="card-body">
                                    <form method="post" action="addEvents.php">
                                        <input type="hidden" name="eventId" value="<?php echo $editEvent ? $editEvent['Id'] : ''; ?>" />
                                        <div class="form-group">
                                            <label>Event Title <span class="text-danger">*</span></label>
                                            <input type="text" name="title" class="form-control" required
                                                   value="<?php echo $editEvent ? htmlspecialchars($editEvent['Title']) : ''; ?>" />
                                        </div>

                                        <div class="form-group">
                                            <label>Description</label>
                                            <textarea name="description" class="form-control" rows="4"><?php echo $editEvent ? htmlspecialchars($editEvent['Description']) : ''; ?></textarea>
                                        </div>

                                        <div class="form-group">
                                            <label>Event Date <span class="text-danger">*</span></label>
                                            <input type="date" name="eventDate" class="form-control" required
                                                   value="<?php echo $editEvent ? $editEvent['EventDate'] : ''; ?>" />
                                        </div>

                                        <div class="form-group">
                                            <label>Start Date & Time <span class="text-danger">*</span></label>
                                            <input type="datetime-local" name="startDateTime" class="form-control" required
                                                   value="<?php echo $editEvent ? date('Y-m-d\TH:i', strtotime($editEvent['StartDateTime'])) : ''; ?>" />
                                        </div>

                                        <div class="form-group">
                                            <label>End Date & Time <span class="text-danger">*</span></label>
                                            <input type="datetime-local" name="endDateTime" class="form-control" required
                                                   value="<?php echo $editEvent ? date('Y-m-d\TH:i', strtotime($editEvent['EndDateTime'])) : ''; ?>" />
                                        </div>

                                        <button type="submit" name="saveEvent" class="btn btn-primary">
                                            <?php echo $editEvent ? "Update Event" : "Add Event"; ?>
                                        </button>
                                        <?php if ($editEvent): ?>
                                            <a href="addEvents.php" class="btn btn-secondary ml-2">Cancel</a>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>

                            <!-- Existing Events List -->
                            <div class="card">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Existing Events</h6>
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
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $eventsSql = "SELECT * FROM tblevents ORDER BY EventDate DESC, StartDateTime DESC";
                                            $eventsResult = mysqli_query($conn, $eventsSql);
                                            if (mysqli_num_rows($eventsResult) > 0) {
                                                while ($event = mysqli_fetch_assoc($eventsResult)) {
                                                    echo "<tr>";
                                                    echo "<td>" . htmlspecialchars($event['Title']) . "</td>";
                                                    echo "<td>" . nl2br(htmlspecialchars($event['Description'])) . "</td>";
                                                    echo "<td>" . htmlspecialchars($event['EventDate']) . "</td>";
                                                    echo "<td>" . date('H:i', strtotime($event['StartDateTime'])) . "</td>";
                                                    echo "<td>" . date('H:i', strtotime($event['EndDateTime'])) . "</td>";
                                                    echo "<td>";
                                                    echo "<a href='addEvents.php?edit=" . $event['Id'] . "' class='btn btn-sm btn-warning mr-1'>Edit</a>";
                                                    echo "<a href='addEvents.php?delete=" . $event['Id'] . "' class='btn btn-sm btn-danger' onclick=\"return confirm('Are you sure you want to delete this event?');\">Delete</a>";
                                                    echo "</td>";
                                                    echo "</tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='6' class='text-center'>No events found.</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
                <?php include "Includes/footer.php"; ?>
            </div>
        </div>
    </div>
    <script src="../vendor/jquery/jquery.min.js"></script>
  <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
  <script src="js/ruang-admin.min.js"></script>
</body>
</html>