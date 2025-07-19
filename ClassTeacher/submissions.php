<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';

$teacherId = $_SESSION['userId'];

// 1. Get assignments uploaded by this teacher
$assignmentsQuery = "SELECT Id, Title FROM tblassignments WHERE UploadedBy = '$teacherId' ORDER BY Title";
$assignmentsResult = mysqli_query($conn, $assignmentsQuery);
$assignments = [];
while ($row = mysqli_fetch_assoc($assignmentsResult)) {
    $assignments[] = $row;
}

// 2. Get all students
$studentsQuery = "SELECT SymbolNo, firstName, lastName FROM tblstudents ORDER BY firstName";
$studentsResult = mysqli_query($conn, $studentsQuery);
$students = [];
while ($row = mysqli_fetch_assoc($studentsResult)) {
    $students[] = $row;
}

// 3. Get all submissions
$assignmentIds = array_column($assignments, 'Id');
$assignmentIdsStr = implode(",", $assignmentIds);
$submissions = [];

if (!empty($assignmentIds)) {
    $submissionsQuery = "
        SELECT s.Id as SubmissionId, s.AssignmentId, s.StudentId, s.SubmittedFile, s.Remarks, s.IsChecked
        FROM tblsubmissions s
        WHERE s.AssignmentId IN ($assignmentIdsStr)
    ";
    $submissionsResult = mysqli_query($conn, $submissionsQuery);
    while ($row = mysqli_fetch_assoc($submissionsResult)) {
        $submissions[$row['StudentId']][$row['AssignmentId']] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Submissions</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/ruang-admin.min.css" rel="stylesheet">
    <style>
        .submission-cell {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 8px;
            text-align: center;
            min-width: 180px;
        }
        .submission-actions {
            margin-top: 5px;
            display: flex;
            justify-content: center;
            gap: 5px;
            flex-wrap: wrap;
        }
        .remarks-box {
            width: 100%;
            font-size: 0.85rem;
            margin-top: 5px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            padding: 4px;
        }
        .remarks-box[readonly] {
            background-color: #e9f6e9;
        }
        .save-remark, .edit-remark {
            margin-top: 5px;
            width: 100%;
            font-size: 0.8rem;
        }
        .checked-icon {
            cursor: pointer;
            font-size: 20px;
            margin-top: 5px;
            display: block;
        }
        .checked-icon.checked {
            color: green;
        }
        .checked-icon.unchecked {
            color: grey;
        }
        .submission-cell.checked-cell {
            background: #e6f7e6;
            border: 1px solid #b2d8b2;
        }
        .missing {
            color: red;
            font-weight: bold;
        }
        table.table {
            border-collapse: separate;
            border-spacing: 0 8px;
        }
        table.table thead th {
            background: #004085;
            color: #fff;
            text-align: center;
        }
        table.table tbody td {
            vertical-align: top;
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
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Assignment Submission Status</h1>
                </div>
                <div class="card mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Submission Matrix</h6>
                    </div>
                    <div class="card-body table-responsive">
                        <?php if (!empty($assignments) && !empty($students)): ?>
                            <table class="table table-bordered table-striped">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Symbol No</th>
                                        <th>Student Name</th>
                                        <?php foreach ($assignments as $assignment): ?>
                                            <th><?= htmlspecialchars($assignment['Title']) ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($student['SymbolNo']) ?></td>
                                            <td><?= htmlspecialchars($student['firstName'] . ' ' . $student['lastName']) ?></td>
                                            <?php foreach ($assignments as $assignment): ?>
                                                <?php $submission = $submissions[$student['SymbolNo']][$assignment['Id']] ?? null; ?>
                                                <td>
                                                    <?php if ($submission): ?>
                                                        <div class="submission-cell <?= $submission['IsChecked'] ? 'checked-cell' : '' ?>" data-cell="<?= $submission['SubmissionId'] ?>">
                                                            <div class="submission-actions">
                                                                <button class="btn btn-sm btn-info preview-btn"
                                                                        data-submission="<?= $submission['SubmissionId'] ?>"
                                                                        data-file="<?= htmlspecialchars($submission['SubmittedFile']) ?>">
                                                                    Preview
                                                                </button>
                                                                <a href="../uploads/submissions/<?= htmlspecialchars($submission['SubmittedFile']) ?>" class="btn btn-sm btn-secondary" target="_blank">
                                                                    <i class="fas fa-download"></i>
                                                                </a>
                                                            </div>
                                                            <textarea class="remarks-box" data-submission="<?= $submission['SubmissionId'] ?>" placeholder="Enter remarks" <?= $submission['IsChecked'] ? 'readonly' : '' ?>><?= htmlspecialchars($submission['Remarks']) ?></textarea>
                                                            <?php if ($submission['IsChecked']): ?>
                                                                <button class="btn btn-warning btn-sm edit-remark" data-submission="<?= $submission['SubmissionId'] ?>">Edit</button>
                                                            <?php else: ?>
                                                                <button class="btn btn-primary btn-sm save-remark" data-submission="<?= $submission['SubmissionId'] ?>">Save</button>
                                                            <?php endif; ?>
                                                            <i class="fas fa-check-circle checked-icon <?= $submission['IsChecked'] ? 'checked' : 'unchecked' ?>" data-submission="<?= $submission['SubmissionId'] ?>"></i>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="missing">Missing</span>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="alert alert-info">No data available.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php include "Includes/footer.php"; ?>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" role="dialog" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Submission Preview</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="previewContent">Loading preview...</div>
        </div>
    </div>
</div>

<script src="../vendor/jquery/jquery.min.js"></script>
<script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="js/ruang-admin.min.js"></script>
<script>
$(document).ready(function() {
    // Preview logic
    $('.preview-btn').click(function() {
        const fileName = $(this).data('file');
        const submissionId = $(this).data('submission');
        const filePath = '../uploads/submissions/' + fileName;
        const ext = fileName.split('.').pop().toLowerCase();
        let html = '';

        switch (ext) {
            case 'pdf':
                html = `<embed src="${filePath}" type="application/pdf" width="100%" height="500px">`; break;
            case 'jpg': case 'jpeg': case 'png': case 'gif': case 'webp':
                html = `<img src="${filePath}" class="img-fluid" alt="Preview Image">`; break;
            case 'doc': case 'docx': case 'ppt': case 'pptx': case 'xls': case 'xlsx':
                const fullUrl = encodeURIComponent(window.location.origin + '/E-attendance/uploads/submissions/' + fileName);
                html = `<iframe src="https://docs.google.com/gview?url=${fullUrl}&embedded=true" width="100%" height="500px" frameborder="0"></iframe>`; break;
            case 'mp4': case 'webm':
                html = `<video width="100%" controls><source src="${filePath}" type="video/${ext}"></video>`; break;
            case 'mp3': case 'ogg': case 'wav':
                html = `<audio controls><source src="${filePath}" type="audio/${ext}"></audio>`; break;
            case 'txt': case 'csv':
                $.get(filePath, function(data) {
                    $('#previewContent').html(`<pre>${$('<div>').text(data).html()}</pre>`);
                });
                $('#previewModal').modal('show');
                return;
            default:
                html = `<p>Preview not available for .${ext} files. <a href="${filePath}" target="_blank">Download</a></p>`;
        }

        $('#previewContent').html(html);
        $('#previewModal').modal('show');

        // Auto-check when previewed
        $.post('saveRemarks.php', { submissionId: submissionId, isChecked: 1 }, function(response) {
            if (response.includes("success")) {
                const icon = $(`.checked-icon[data-submission="${submissionId}"]`);
                icon.removeClass('unchecked').addClass('checked');
                icon.closest('.submission-cell').addClass('checked-cell');
                // Disable textarea & switch buttons on preview auto-check
                const textarea = icon.closest('.submission-cell').find('.remarks-box');
                textarea.prop('readonly', true);
                const saveBtn = icon.closest('.submission-cell').find('.save-remark');
                if (saveBtn.length) {
                    saveBtn.replaceWith(`<button class="btn btn-warning btn-sm edit-remark" data-submission="${submissionId}">Edit</button>`);
                }
            }
        });
    });

    // Save remarks via AJAX
    $(document).on('click', '.save-remark', function() {
        const btn = $(this);
        const submissionId = btn.data('submission');
        const textarea = btn.siblings('.remarks-box');
        const remarks = textarea.val();

        $.post('saveRemarks.php', { submissionId: submissionId, remarks: remarks, isChecked: 1 }, function(response) {
            if (response.includes("success")) {
                // Mark checked visually and disable textarea
                textarea.prop('readonly', true);
                btn.replaceWith(`<button class="btn btn-warning btn-sm edit-remark" data-submission="${submissionId}">Edit</button>`);
                const icon = $(`.checked-icon[data-submission="${submissionId}"]`);
                icon.removeClass('unchecked').addClass('checked');
                icon.closest('.submission-cell').addClass('checked-cell');
            }
        });
    });

    // Edit remarks button click
    $(document).on('click', '.edit-remark', function() {
        const btn = $(this);
        const submissionId = btn.data('submission');
        const textarea = btn.siblings('.remarks-box');

        // Enable textarea for editing
        textarea.prop('readonly', false);
        btn.replaceWith(`<button class="btn btn-primary btn-sm save-remark" data-submission="${submissionId}">Save</button>`);

        // Remove checked status visually
        const icon = $(`.checked-icon[data-submission="${submissionId}"]`);
        icon.removeClass('checked').addClass('unchecked');
        icon.closest('.submission-cell').removeClass('checked-cell');

        // Update backend: set IsChecked = 0
        $.post('saveRemarks.php', { submissionId: submissionId, isChecked: 0 });
    });

    // Toggle checked status manually (optional)
    $('.checked-icon').click(function() {
        const icon = $(this);
        const submissionId = icon.data('submission');
        const newStatus = icon.hasClass('checked') ? 0 : 1;

        $.post('saveRemarks.php', { submissionId: submissionId, isChecked: newStatus }, function(response) {
            if (response.includes("success")) {
                icon.toggleClass('checked unchecked');
                icon.closest('.submission-cell').toggleClass('checked-cell', newStatus === 1);
            }
        });
    });
});
</script>
</body>
</html>
