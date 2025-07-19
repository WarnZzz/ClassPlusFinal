<?php 
session_start();

if (isset($_SESSION['userId']) && isset($_SESSION['userRole'])) {
    // Session is active, redirect based on user role
    switch ($_SESSION['userRole']) {
        case 'Administrator':
            header("Location: Admin/index.php");
            exit;
        case 'ClassTeacher':
            header("Location: ClassTeacher/index.php");
            exit;
        case 'Student':
            header("Location: Student/index.php");
            exit;
    }
}

include 'Includes/dbcon.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>ClassPlus</title>
    <link href="img/logo/attnlg.jpg" rel="icon">
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="css/ruang-admin.min.css" rel="stylesheet">
    <style>
        .hidden { display: none; }
    </style>
</head>
<body class="bg-gradient-login" style="background-image: url('img/loral1.png');">
<div class="container-login">
    <div class="row justify-content-center">
        <div class="col-xl-10 col-lg-12 col-md-9">
            <div class="card shadow-sm my-5">
                <div class="card-body p-0">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="login-form">
                                <h5 align="center">ClassPlus</h5>
                                <div class="text-center">
                                    <img src="img/logo/attnlg.jpg" style="width:100px;height:100px">
                                    <br><br>
                                    <h1 class="h4 text-gray-900 mb-4">Login Panel</h1>
                                </div>
                                <form class="user" method="POST" action="">
                                    <div class="form-group">
                                        <select required name="userType" id="userType" class="form-control mb-3" onchange="toggleFields()">
                                            <option value="">--Select User Roles--</option>
                                            <option value="Administrator">Administrator</option>
                                            <option value="ClassTeacher">Teacher</option>
                                            <option value="Student">Student</option>
                                        </select>
                                    </div>
                                    <div class="form-group" id="emailField">
                                        <input type="text" class="form-control" required name="username" id="exampleInputEmail" placeholder="Enter Email Address">
                                    </div>
                                    <div class="form-group hidden" id="symbolNoField">
                                        <input type="text" class="form-control" name="symbolNo" placeholder="Enter Symbol No.">
                                    </div>
                                    <div class="form-group" id="passwordField">
                                        <input type="password" name="password" required class="form-control" id="exampleInputPassword" placeholder="Enter Password">
                                    </div>
                                    <div class="form-group">
                                        <input type="submit" class="btn btn-success btn-block" value="Login" name="login" />
                                    </div>
                                </form>

                                <?php
                                if (isset($_POST['login'])) {
                                    $userType = $_POST['userType'];
                                    $username = $_POST['username'] ?? '';
                                    $password = md5($_POST['password'] ?? '');

                                    if ($userType == "Administrator") {
                                        $query = "SELECT * FROM tbladmin WHERE emailAddress = '$username' AND password = '$password'";
                                        $rs = $conn->query($query);
                                        $rows = $rs->fetch_assoc();
                                        if ($rs->num_rows > 0) {
                                            $_SESSION['userId'] = $rows['Id'];
                                            $_SESSION['firstName'] = $rows['firstName'];
                                            $_SESSION['lastName'] = $rows['lastName'];
                                            $_SESSION['emailAddress'] = $rows['emailAddress'];
                                            $_SESSION['userRole'] = 'Administrator';
                                            header("Location: emailOTP.php");
                                            exit;
                                        } else {
                                            echo "<div class='alert alert-danger'>Invalid Username/Password!</div>";
                                        }
                                    } else if ($userType == "ClassTeacher") {
                                        $query = "SELECT * FROM tblclassteacher WHERE emailAddress = '$username' AND password = '$password'";
                                        $rs = $conn->query($query);
                                        $rows = $rs->fetch_assoc();
                                        if ($rs->num_rows > 0) {
                                            $_SESSION['userId'] = $rows['Id'];
                                            $_SESSION['firstName'] = $rows['firstName'];
                                            $_SESSION['lastName'] = $rows['lastName'];
                                            $_SESSION['emailAddress'] = $rows['emailAddress'];
                                            $_SESSION['userRole'] = 'ClassTeacher';
                                            header("Location: emailOTP.php");
                                            exit;
                                        } else {
                                            echo "<div class='alert alert-danger'>Invalid Username/Password!</div>";
                                        }
                                    } else if ($userType == "Student") {
                                        $symbolNo = $_POST['symbolNo'];
                                        $query = "SELECT * FROM tblstudents WHERE SymbolNo = '$symbolNo' AND password = '$password'";
                                        $rs = $conn->query($query);
                                        $rows = $rs->fetch_assoc();
                                        if ($rs->num_rows > 0) {
                                            $_SESSION['userId'] = $rows['SymbolNo'];
                                            $_SESSION['firstName'] = $rows['firstName'];
                                            $_SESSION['lastName'] = $rows['lastName'];
                                            $_SESSION['emailAddress'] = $rows['emailAddress'];
                                            $_SESSION['userRole'] = 'Student';
                                            $redirect = $_GET['redirect'] ?? '';
                                            header("Location: emailOTP.php" . ($redirect ? "?redirect=" . urlencode($redirect) : ''));
                                            exit();
                                        } else {
                                            echo "<div class='alert alert-danger'>Invalid Symbol Number or Password!</div>";
                                        }
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="js/ruang-admin.min.js"></script>

<script>
function toggleFields() {
    var userType = document.getElementById('userType').value;
    var emailField = document.getElementById('emailField');
    var passwordField = document.getElementById('passwordField');
    var symbolNoField = document.getElementById('symbolNoField');

    if (userType === 'Student') {
        emailField.classList.add('hidden');
        emailField.querySelector('input').disabled = true;
        symbolNoField.classList.remove('hidden');
        symbolNoField.querySelector('input').disabled = false;
        passwordField.classList.remove('hidden');
        passwordField.querySelector('input').disabled = false;
    } else {
        symbolNoField.classList.add('hidden');
        symbolNoField.querySelector('input').disabled = true;
        emailField.classList.remove('hidden');
        emailField.querySelector('input').disabled = false;
        passwordField.classList.remove('hidden');
        passwordField.querySelector('input').disabled = false;
    }
}

document.addEventListener('DOMContentLoaded', toggleFields);
</script>
</body>
</html>
