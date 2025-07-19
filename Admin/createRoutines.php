<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';

$statusMsg = "";

// Fetch all classes and their subjects
$classData = [];
$classQry = $conn->query("SELECT * FROM tblclass ORDER BY Id ASC");
while ($classRow = $classQry->fetch_assoc()) {
    $classId = $classRow['Id'];
    $classData[$classId] = [
        'Program' => $classRow['Program'],
        'Year' => $classRow['Year(Batch)'],
        'Section' => $classRow['section'],
        'Subjects' => []
    ];

    $subjectQry = $conn->prepare("SELECT CourseName FROM tblclassarms WHERE ClassId=?");
    $subjectQry->bind_param("i", $classId);
    $subjectQry->execute();
    $res = $subjectQry->get_result();
    while ($sub = $res->fetch_assoc()) {
        $classData[$classId]['Subjects'][] = $sub['CourseName'];
    }
}

// Handle form submit to save or update routine
if (isset($_POST['saveRoutine'])) {
    $program = $_POST['Program'];
    $year = $_POST['Year'];
    $section = $_POST['Section'];

    $days = $_POST['day'];
    $start_times = $_POST['start_time'];
    $end_times = $_POST['end_time'];
    $subjects = $_POST['subject'];

    // New hidden input for routine IDs (empty if new)
    $routine_ids = isset($_POST['routine_id']) ? $_POST['routine_id'] : [];

    for ($i = 0; $i < count($days); $i++) {
        $timeSlot = $start_times[$i] . " - " . $end_times[$i];

        if (!empty($routine_ids[$i])) {
            // Update existing routine
            $stmt = $conn->prepare("UPDATE tblroutine SET Program=?, Year_Batch=?, Section=?, Day=?, TimeSlot=?, Subject=? WHERE Id=?");
            $stmt->bind_param("ssssssi", $program, $year, $section, $days[$i], $timeSlot, $subjects[$i], $routine_ids[$i]);
        } else {
            // Insert new routine
            $stmt = $conn->prepare("INSERT INTO tblroutine (Program, Year_Batch, Section, Day, TimeSlot, Subject) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $program, $year, $section, $days[$i], $timeSlot, $subjects[$i]);
        }
        $stmt->execute();
        $stmt->close();
    }

    $statusMsg = "<div class='alert alert-success'>Routine saved successfully!</div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Add Routines</title>
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
                        <h1 class="h3 mb-0 text-gray-800">Add Weekly Routine</h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Add Routine</li>
                        </ol>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Select Class</h6>
                                    <?php echo $statusMsg; ?>
                                </div>
                                <div class="card-body">
                                    <form method="post" id="routineForm">
                                        <div class="form-group">
                                            <label>Select Class <span class="text-danger">*</span></label>
                                            <select name="classId" id="classId" class="form-control mb-3" required>
                                                <option value="">--Select Class--</option>
                                                <?php foreach ($classData as $id => $data) {
                                                    echo '<option value="'.$id.'">'.$data['Program'].'-'.$data['Year'].'-'.$data['Section'].'</option>';
                                                } ?>
                                            </select>
                                        </div>

                                        <input type="hidden" name="Program" id="Program">
                                        <input type="hidden" name="Year" id="Year">
                                        <input type="hidden" name="Section" id="Section">

                                        <div id="routineFields" style="display:none;">
                                            <hr>
                                            <h6>Add Routine Entries</h6>

                                            <div id="routineContainer"></div>

                                            <button type="button" class="btn btn-info mb-3" id="addRoutineRow">Add More</button>
                                            <br>

                                            <button type="submit" name="saveRoutine" class="btn btn-primary">Save Routine</button>
                                        </div>
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
<script>
const classData = <?php echo json_encode($classData); ?>;

document.addEventListener('DOMContentLoaded', function(){
    let dayOptions = `<option value='Monday'>Monday</option>
                      <option value='Tuesday'>Tuesday</option>
                      <option value='Wednesday'>Wednesday</option>
                      <option value='Thursday'>Thursday</option>
                      <option value='Friday'>Friday</option>
                      <option value='Saturday'>Saturday</option>`;

    let selectedSubjects = [];

    // Fetch existing routine entries for the selected class via AJAX
    function fetchRoutine(classId) {
        if (!classId) return;

        // Fetch data via AJAX from a new PHP endpoint (defined below)
        fetch('fetchRoutine.php?classId=' + classId)
            .then(response => response.json())
            .then(data => {
                // Clear and show routine fields
                document.getElementById('routineContainer').innerHTML = '';
                if (data.length === 0) {
                    // No existing routines, add empty row
                    addRoutineRow();
                } else {
                    data.forEach(entry => {
                        addRoutineRow(entry);
                    });
                }
                document.getElementById('routineFields').style.display = 'block';
            })
            .catch(err => {
                console.error('Error fetching routine:', err);
                document.getElementById('routineContainer').innerHTML = '';
                addRoutineRow();
                document.getElementById('routineFields').style.display = 'block';
            });
    }

    document.getElementById('classId').addEventListener('change', function(){
        let classId = this.value;

        if(classId){
            let data = classData[classId];

            document.getElementById('Program').value = data.Program;
            document.getElementById('Year').value = data.Year;
            document.getElementById('Section').value = data.Section;

            selectedSubjects = data.Subjects;

            fetchRoutine(classId);

        }else{
            document.getElementById('routineFields').style.display = 'none';
            document.getElementById('routineContainer').innerHTML = '';
            document.getElementById('Program').value = '';
            document.getElementById('Year').value = '';
            document.getElementById('Section').value = '';
        }
    });

    document.getElementById('addRoutineRow').addEventListener('click', function(){
        addRoutineRow();
    });

    function addRoutineRow(existing = null){
        let subjectsOptions = "";
        for(let i=0; i<selectedSubjects.length; i++){
            let selectedAttr = existing && existing.Subject === selectedSubjects[i] ? "selected" : "";
            subjectsOptions += "<option value='"+selectedSubjects[i]+"' "+selectedAttr+">"+selectedSubjects[i]+"</option>";
        }

        let container = document.getElementById('routineContainer');

        let div = document.createElement('div');
        div.className = 'form-row routine-row mb-3';

        div.innerHTML = `
            <input type="hidden" name="routine_id[]" value="${existing ? existing.Id : ''}">
            <div class="col-md-2">
                <label>Day</label>
                <select name="day[]" class="form-control" required>
                    ${dayOptions.replace(
                      /<option value='([^']+)'>([^<]+)<\/option>/g,
                      (match, val, text) => `<option value='${val}' ${existing && existing.Day === val ? 'selected' : ''}>${text}</option>`
                    )}
                </select>
            </div>

            <div class="col-md-2">
                <label>Start Time</label>
                <input type="time" name="start_time[]" class="form-control" required value="${existing ? existing.TimeSlot.split(' - ')[0] : ''}">
            </div>

            <div class="col-md-2">
                <label>End Time</label>
                <input type="time" name="end_time[]" class="form-control" required value="${existing ? existing.TimeSlot.split(' - ')[1] : ''}">
            </div>

            <div class="col-md-4">
                <label>Subject</label>
                <select name="subject[]" class="form-control" required>
                    ${subjectsOptions}
                </select>
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-danger removeRoutineRow">Remove</button>
            </div>
        `;
        container.appendChild(div);

        div.querySelector('.removeRoutineRow').addEventListener('click', function(){
            div.remove();
        });
    }
});
</script>

</body>
</html>