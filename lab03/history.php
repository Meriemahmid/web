<?php require_once '../../config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>GPA History</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container mt-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>📋 GPA History</h2>
        <a href="?page=student.dashboard" class="btn btn-outline-secondary">← Back</a>
    </div>

    <div id="history-container">
        <p class="text-center">Loading...</p>
    </div>

    <div class="mt-3">
        <a href="../../api/gpa.php?action=export" class="btn btn-success">
            ⬇️ Export CSV
        </a>
    </div>

</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
$(document).ready(function () {

    $.get('../../api/gpa.php', { action: 'history' }, function (semesters) {

        if (!semesters.length) {
            $('#history-container').html('<p class="text-muted">No history found.</p>');
            return;
        }

        var html = '';

        $.each(semesters, function (i, sem) {

            // تلوين GPA
            var gpa = sem.gpa;
            var cls = 'secondary';
            if      (gpa >= 3.7) cls = 'success';
            else if (gpa >= 3.0) cls = 'info';
            else if (gpa >= 2.0) cls = 'warning';
            else if (gpa !== null) cls = 'danger';

            html += '<div class="card mb-4">'
                  + '<div class="card-header d-flex justify-content-between">'
                  + '<strong>' + sem.label + ' — ' + sem.academic_year + '</strong>'
                  + '<span class="badge bg-' + cls + '">'
                  + (gpa !== null ? 'GPA: ' + gpa : 'No GPA') + '</span>'
                  + '</div>'
                  + '<div class="card-body p-0">'
                  + '<table class="table table-striped mb-0">'
                  + '<thead class="table-dark"><tr>'
                  + '<th>Course</th><th>Credits</th><th>Grade</th><th>Points</th>'
                  + '</tr></thead><tbody>';

            $.each(sem.courses, function (j, c) {
                var grade  = c.grade !== null ? c.grade : '<span class="text-muted">Pending</span>';
                var points = c.grade !== null ? (c.grade * c.credits).toFixed(1) : '—';
                html += '<tr>'
                      + '<td>' + c.name    + '</td>'
                      + '<td>' + c.credits + '</td>'
                      + '<td>' + grade     + '</td>'
                      + '<td>' + points    + '</td>'
                      + '</tr>';
            });

            html += '</tbody></table></div></div>';
        });

        $('#history-container').html(html);

    }, 'json');

});
</script>
</body>
</html>