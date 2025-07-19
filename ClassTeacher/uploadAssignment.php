<?php
require '../vendor/autoload.php'; // PHPMailer autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
include '../Includes/dbcon.php';
include '../Includes/session.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $courseId = intval($_POST['courseId']);
    $deadline = $_POST['deadline'];
    $uploadDate = date("Y-m-d H:i:s");

    $filePath = '';
    if (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
        $fileExt = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        $safeName = uniqid('assignment_', true) . '.' . $fileExt;
        $targetDir = '../uploads/assignments/';
        $targetPath = $targetDir . $safeName;

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
            $filePath = $safeName;
        } else {
            $message = "File upload failed.";
        }
    }

    if (!$message) {
        $teacherId = $_SESSION['userId'];
        $sql = "INSERT INTO tblassignments (Title, Description, FilePath, ClassArmId, Deadline, UploadedBy, UploadDate)
                VALUES ('$title', '$description', '$filePath', '$courseId', '$deadline', '$teacherId', '$uploadDate')";

        if (mysqli_query($conn, $sql)) {
    // ✅ Assignment saved, now fetch student emails for this course
    $studentQuery = "
        SELECT s.emailAddress
        FROM tblstudents s
        INNER JOIN tblclassarms ca ON s.ClassId = ca.ClassId
        WHERE ca.Id = '$courseId'
    ";
    $studentResult = mysqli_query($conn, $studentQuery);
    $emailAddresses = [];
    while ($row = mysqli_fetch_assoc($studentResult)) {
        if (!empty($row['emailAddress'])) {
            $emailAddresses[] = $row['emailAddress'];
        }
    }

    if (!empty($emailAddresses)) {
        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'paudelranjan14@gmail.com';
            $mail->Password = 'mxxpxoivbkdauvlc';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Sender
            $mail->setFrom('paudelranjan14@gmail.com', 'PokharaEngineeringCollege');

            // Recipients
            foreach ($emailAddresses as $email) {
                $mail->addAddress($email);
            }

            // Email content
            $mail->isHTML(false);
            $mail->Subject = 'New Assignment Uploaded';
            $mail->Body = "Dear Student,\n\nA new assignment titled \"$title\" has been uploaded for your course.\n\nPlease check it and submit within the deadline: $deadline.\n\nBest Regards,\nYour Teacher";

            $mail->send();
            $message = "✅ Assignment uploaded successfully and emails sent!";
        } catch (Exception $e) {
            $message = "✅ Assignment uploaded, but email failed: {$mail->ErrorInfo}";
        }
    } else {
        $message = "✅ Assignment uploaded. No student emails found to notify.";
    }
} else {
    $message = "❌ Error: " . mysqli_error($conn);
}

    }
}

$teacherId = $_SESSION['userId'];
$query = "SELECT Id, CourseName FROM tblclassarms WHERE AssignedTo = '$teacherId'";
$result = mysqli_query($conn, $query);
$courses = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="img/logo/attnlg.jpg" rel="icon">
    <title>Upload Assignment</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/ruang-admin.min.css" rel="stylesheet">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include "Includes/sidebar.php"; ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include "Includes/topbar.php"; ?>

                <div class="container-fluid" id="container-wrapper">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Upload Assignment</h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Upload Assignment</li>
                        </ol>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert alert-info"> <?= htmlspecialchars($message) ?> </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">New Assignment</h6>
                                </div>
                                <div class="card-body">
                                    <form method="post" enctype="multipart/form-data">
                                        <div class="form-group">
                                            <label for="title">Assignment Title<span class="text-danger ml-2">*</span></label>
                                            <input type="text" name="title" id="title" class="form-control" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="description">Description (optional)</label>
                                            <textarea name="description" id="description" class="form-control"></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="courseId">Select Course<span class="text-danger ml-2">*</span></label>
                                            <select name="courseId" id="courseId" class="form-control" required>
                                                <option value="" disabled selected>-- Select Course --</option>
                                                <?php foreach ($courses as $course): ?>
                                                    <option value="<?= $course['Id'] ?>"> <?= htmlspecialchars($course['CourseName']) ?> </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="deadline">Deadline<span class="text-danger ml-2">*</span></label>
                                            <input type="datetime-local" name="deadline" id="deadline" class="form-control" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="file">Attach File (PDF/Doc)</label>
                                            <input type="file" name="file" id="file" class="form-control-file" accept=".pdf,.doc,.docx">
                                        </div>
                                        <button type="submit" class="btn btn-primary">Upload Assignment</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <?php include "Includes/footer.php"; ?>
        </div>
    </div>

    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/ruang-admin.min.js"></script>
</body>

</html>
