<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
include 'Includes/dbcon.php';
include 'Includes/session.php';

function generateOTP() {
    return rand(100000, 999999);
}

function sendOTP($email, $otp) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'paudelranjan14@gmail.com';
        $mail->Password   = 'mxxpxoivbkdauvlc';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('your@example.com', 'School Admin');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP Code';
        $mail->Body    = "Your OTP code is <b>$otp</b>. It will expire in 2 minutes.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

if (!isset($_SESSION['emailAddress']) || !isset($_SESSION['userRole'])) {
    header("Location: index.php");
    exit();
}

$canResend = true;
$currentTime = time();

if (!isset($_SESSION['otp']) || $currentTime > $_SESSION['otp_expiry']) {
    $otp = generateOTP();
    $_SESSION['otp'] = $otp;
    $_SESSION['otp_expiry'] = $currentTime + 120;
    $_SESSION['otp_last_sent'] = $currentTime;
    sendOTP($_SESSION['emailAddress'], $otp);
}

if (isset($_POST['verify'])) {
    $enteredOTP = implode('', $_POST['otp'] ?? []);
    if ($enteredOTP == $_SESSION['otp'] && time() <= $_SESSION['otp_expiry']) {
        $userType = $_SESSION['userRole'];
        if ($userType === 'Administrator') {
            header("Location: Admin/index.php");
        } elseif ($userType === 'ClassTeacher') {
            header("Location: ClassTeacher/index.php");
        } elseif ($userType === 'Student') {
            header("Location: Student/index.php");
        }
        exit();
    } else {
        $error = "Invalid or expired OTP.";
    }
}

if (isset($_POST['resend']) && ($currentTime - $_SESSION['otp_last_sent'] >= 60)) {
    $otp = generateOTP();
    $_SESSION['otp'] = $otp;
    $_SESSION['otp_expiry'] = $currentTime + 120;
    $_SESSION['otp_last_sent'] = $currentTime;
    sendOTP($_SESSION['emailAddress'], $otp);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Email OTP Verification</title>
    <link href="img/logo/attnlg.jpg" rel="icon">
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="css/ruang-admin.min.css" rel="stylesheet">
    <style>
        .otp-input {
            width: 40px;
            height: 40px;
            text-align: center;
            font-size: 24px;
            margin: 0 5px;
        }
        .otp-box {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        @media (max-width: 576px) {
            .otp-input {
                width: 36px;
                height: 36px;
                margin: 3px;
                font-size: 20px;
            }
            .card-body {
                padding: 1.5rem !important;
            }
        }
    </style>
</head>
<body class="bg-gradient-login" style="background-image: url('img/logo/loral1.jpg');">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-xl-6 col-lg-8 col-md-9">
            <div class="card shadow-sm my-5">
                <div class="card-body p-4">
                    <div class="text-center">
                        <img src="img/logo/attnlg.jpg" style="width:100px;height:100px">
                        <h4 class="text-gray-900 mt-3">OTP Verification</h4>
                        <p>Enter the 6-digit code sent to <?php echo $_SESSION['emailAddress']; ?></p>
                    </div>
                    <?php if (!empty($error)) echo "<div class='alert alert-danger text-center'>$error</div>"; ?>
                    <form method="post" id="otpForm">
                        <input type="hidden" name="verify" value="1">
                        <div class="otp-box">
                            <?php for ($i = 0; $i < 6; $i++): ?>
                                <input type="text"
                                       maxlength="1"
                                       class="otp-input form-control"
                                       name="otp[]"
                                       oninput="moveNext(this, <?php echo $i; ?>)"
                                       required
                                       inputmode="numeric"
                                       pattern="\d*"
                                       autocomplete="one-time-code">
                            <?php endfor; ?>
                        </div>
                        <div class="form-group">
                            <input type="submit" name="verify" value="Verify OTP" class="btn btn-primary btn-block">
                        </div>
                    </form>

                    <form method="post" class="text-center">
                        <p id="resendTimerText" class="text-muted">
                            <?php
                            $remaining = max(0, 60 - ($currentTime - $_SESSION['otp_last_sent']));
                            echo "You can resend OTP in <span id='resendTimer'>$remaining</span> seconds.";
                            ?>
                        </p>
                        <button type="submit" name="resend" id="resendBtn" class="btn btn-link" <?php echo ($remaining > 0) ? 'disabled' : ''; ?>>
                            Resend OTP
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
function moveNext(input, index) {
    const inputs = document.querySelectorAll('.otp-input');
    if (input.value.length === 1 && index < inputs.length - 1) {
        inputs[index + 1].focus();
    }
    if (input.value.length === 0 && index > 0) {
        inputs[index - 1].focus();
    }

    const otpValues = Array.from(inputs).map(inp => inp.value.trim());
    if (otpValues.every(val => val.length === 1)) {
        document.getElementById('otpForm').submit();
    }
}

const otpInputs = document.querySelectorAll('.otp-input');
otpInputs[0].addEventListener('paste', function (e) {
    e.preventDefault();
    const pasteData = (e.clipboardData || window.clipboardData).getData('text');
    if (pasteData.length === 6 && /^\d{6}$/.test(pasteData)) {
        otpInputs.forEach((input, i) => input.value = pasteData[i]);
        document.getElementById('otpForm').submit();
    }
});

let timer = parseInt(document.getElementById('resendTimer').innerText);
const resendBtn = document.getElementById('resendBtn');
const timerText = document.getElementById('resendTimerText');
const timerSpan = document.getElementById('resendTimer');

if (timer > 0) {
    const countdown = setInterval(() => {
        timer--;
        timerSpan.innerText = timer;
        if (timer <= 0) {
            clearInterval(countdown);
            resendBtn.disabled = false;
            timerText.innerHTML = '<span class="text-success">You can now resend the OTP.</span>';
        }
    }, 1000);
}
</script>

<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="js/ruang-admin.min.js"></script>
</body>
</html>
