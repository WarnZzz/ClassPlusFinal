<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Scan QR to Mark Attendance</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="../img/logo/attnlg.jpg" rel="icon" />
  <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" />
  <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" />
  <link href="../css/ruang-admin.min.css" rel="stylesheet" />
  <style>
    #qr-reader { max-width: 500px; margin: auto; }
    .status-message { text-align: center; margin-bottom: 15px; }
    #camera-toggle-btn {
      border: none;
      background: transparent;
      font-size: 1.2rem;
      cursor: pointer;
      float: right;
    }
  </style>
</head>
<body id="page-top">
<div id="wrapper">
  <?php include "Includes/sidebar.php"; ?>
  <div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
      <?php include "Includes/topbar.php"; ?>
      <div class="container-fluid" id="container-wrapper">
        <h1 class="h3 mb-4 text-gray-800">QR Attendance</h1>
        <div class="card">
          <div class="card-header">
            📸 Scan Attendance QR or Enter Code
            <button id="camera-toggle-btn" title="Switch Camera">
              <i class="fas fa-sync-alt"></i>
            </button>
          </div>
          <div class="card-body">
            <div id="status-msg" class="status-message text-success font-weight-bold"></div>
            <ul class="nav nav-tabs mb-3">
              <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#scan">Scan QR</a></li>
              <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#manual">Enter Code</a></li>
            </ul>
            <div class="tab-content">
              <div class="tab-pane container active" id="scan">
                <div id="qr-reader"></div>
              </div>
              <div class="tab-pane container fade" id="manual">
                <form id="manualAttendanceForm">
                  <input type="text" class="form-control" name="code" id="manual-code" placeholder="Enter Code" required />
                  <button type="submit" class="btn btn-primary mt-2">Submit</button>
                </form>
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
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script>
  let html5QrCode;
  let currentCamera = 0;
  let cameras = [];
  const qrRegionId = "qr-reader";

  function submitCode(code) {
    if (!code) return;
    $.post("markStudentPresent.php", { code: code }, function (response) {
      if (response.success) {
        $("#status-msg").text("✅ " + response.message);
        $("#qr-reader, #manualAttendanceForm").hide();
        if (html5QrCode) html5QrCode.stop();
      } else {
        $("#status-msg").text("❌ " + response.message).removeClass("text-success").addClass("text-danger");
      }
    }, 'json');
  }

  function onScanSuccess(decodedText) {
    submitCode(decodedText);
  }

  function startScanner(cameraId) {
    if (html5QrCode) {
      html5QrCode.stop().then(() => {
        html5QrCode.clear();
        html5QrCode.start(cameraId, { fps: 10, qrbox: 250 }, onScanSuccess);
      });
    } else {
      html5QrCode = new Html5Qrcode(qrRegionId);
      html5QrCode.start(cameraId, { fps: 10, qrbox: 250 }, onScanSuccess);
    }
  }

  Html5Qrcode.getCameras().then(devices => {
    cameras = devices;
    if (cameras.length > 0) {
      startScanner(cameras[currentCamera].id);
    }
  });

  $("#camera-toggle-btn").on("click", function () {
    if (cameras.length < 2) return;
    currentCamera = (currentCamera + 1) % cameras.length;
    startScanner(cameras[currentCamera].id);
  });

  $("#manualAttendanceForm").on("submit", function(e) {
    e.preventDefault();
    const code = $("#manual-code").val().trim();
    submitCode(code);
  });
</script>
</body>
</html>
