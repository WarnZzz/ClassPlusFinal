<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
include '../Includes/dbcon.php';
include '../Includes/session.php';
require '../vendor/autoload.php'; 

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

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload'])) {
    $courseId = $_POST['course'];
    $noteTitle = $_POST['noteTitle'];

    if (isset($_FILES['noteFile']) && $_FILES['noteFile']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['noteFile']['tmp_name'];
        $fileName = basename($_FILES['noteFile']['name']);
        $fileSize = $_FILES['noteFile']['size'];
        $fileType = $_FILES['noteFile']['type'];

        $uploadFileDir = realpath(__DIR__ . '/../notes') . DIRECTORY_SEPARATOR;
        $destPath = $uploadFileDir . $fileName;

        if (!file_exists($uploadFileDir)) {
            mkdir($uploadFileDir, 0755, true);
        }

        if (move_uploaded_file($fileTmpPath, $destPath)) {
    $query = "INSERT INTO tblnotes (courseId, title, filePath, uploadedBy, uploadDate)
              VALUES ('$courseId', '$noteTitle', '$fileName', '$teacherId', NOW())";

    if (mysqli_query($conn, $query)) {
        // Step 1: Get ClassId and CourseName from tblclassarms
        $classQuery = "SELECT ClassId, CourseName FROM tblclassarms WHERE Id = '$courseId'";
        $classResult = mysqli_query($conn, $classQuery);
        $classData = mysqli_fetch_assoc($classResult);
        $classId = $classData['ClassId'];
        $courseName = $classData['CourseName'];

        // Step 2: Get student emails
        $studentQuery = "SELECT emailAddress, firstName FROM tblstudents WHERE ClassId = '$classId'";
        $studentResult = mysqli_query($conn, $studentQuery);

        $emailAddresses = [];
        $studentNames = [];

        while ($student = mysqli_fetch_assoc($studentResult)) {
            $emailAddresses[] = $student['emailAddress'];
            $studentNames[] = $student['firstName']; // optional if you want to personalize
        }

        if (!empty($emailAddresses)) {
            $mail = new PHPMailer(true);
            try {
                // SMTP Configuration
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'paudelranjan14@gmail.com';
                $mail->Password = 'mxxpxoivbkdauvlc'; // App Password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Sender Info
                $mail->setFrom('paudelranjan14@gmail.com', 'PokharaEngineeringCollege');

                // Recipients
                foreach ($emailAddresses as $email) {
                    $mail->addAddress($email);
                }

                // Email Content
                $mail->isHTML(true);
                $mail->Subject = 'New Notes Uploaded: ' . $courseName;
                $mail->Body = "Dear Student,<br><br>
                New notes titled <strong>\"$noteTitle\"</strong> have been uploaded for the course <strong>\"$courseName\"</strong>.<br>
                Please log in to your student portal to download the notes.<br><br>
                Regards,<br>Your Teacher";

                $mail->send();
                $message = "Note uploaded and email sent to students successfully.";
            } catch (Exception $e) {
                $message = "Note uploaded, but email failed to send. Error: {$mail->ErrorInfo}";
            }
        } else {
            $message = "Note uploaded. No student emails found for this course.";
        }
    } else {
        $message = "Note upload failed (DB insert error).";
    }
} else {
    $message = "File upload failed.";
}
    } else {
        $message = "No file uploaded or upload error.";
    }
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
    <title>Upload Notes</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
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
                        <h1 class="h3 mb-0 text-gray-800">Upload Notes</h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Upload Notes</li>
                        </ol>
                    </div>

                    <?php if (isset($message)) : ?>
                        <div class="alert alert-info"> <?= $message ?> </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Upload Notes</h6>
                                </div>
                                <div class="card-body">
                                    <form method="post" enctype="multipart/form-data">
                                        <div class="form-group">
                                            <label>Select Course<span class="text-danger ml-2">*</span></label>
                                            <select class="form-control" name="course" required>
                                                <option value="">Select a course</option>
                                                <?php foreach ($courses as $course): ?>
                                                    <option value="<?= $course['Id'] ?>">
                                                        <?= $course['CourseName'] . '-' . $course['Program'] . '-' . $course['Year(Batch)'] . '-' . $course['section'] ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Note Title<span class="text-danger ml-2">*</span></label>
                                            <input type="text" class="form-control" name="noteTitle" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Upload File<span class="text-danger ml-2">*</span></label>
                                            <input type="file" class="form-control" name="noteFile" required>
                                        </div>
                                        <button type="submit" name="upload" class="btn btn-primary">Upload Note</button>
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