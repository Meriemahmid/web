$(document).ready(function () {

    // جلب بيانات الفصل الحالي
    $.get('../../api/gpa.php', { action: 'current' }, function (data) {

        if (data.error) {
            $('#grades-body').html(
                '<tr><td colspan="4" class="text-center text-danger">' + data.error + '</td></tr>'
            );
            return;
        }

        // عنوان الفصل
        $('#semester-title').text(data.semester.label + ' — ' + data.semester.academic_year);

        // تلوين الـ GPA
        var gpa = data.gpa;
        var cls = 'secondary';
        var label = 'No GPA yet';

        if (gpa !== null) {
            label = 'GPA: ' + gpa;
            if      (gpa >= 3.7) cls = 'success';
            else if (gpa >= 3.0) cls = 'info';
            else if (gpa >= 2.0) cls = 'warning';
            else                 cls = 'danger';
        }

        $('#gpa-box').html(
            '<span class="badge bg-' + cls + ' fs-5 p-3">' + label + '</span>'
        );

        // صفوف الجدول
        var rows = '';
        $.each(data.courses, function (i, c) {
            var grade  = c.grade !== null ? c.grade : '<span class="text-muted">Pending</span>';
            var points = c.grade !== null ? (c.grade * c.credits).toFixed(1) : '—';
            rows += '<tr>'
                  + '<td>' + c.name    + '</td>'
                  + '<td>' + c.credits + '</td>'
                  + '<td>' + grade     + '</td>'
                  + '<td>' + points    + '</td>'
                  + '</tr>';
        });

        $('#grades-body').html(rows);

    }, 'json');

});