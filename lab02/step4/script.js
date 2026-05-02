$(document).ready(function () {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Add Course Row
    $('#addCourse').click(function () {
        var firstRow = $('.course-row').first();
        var newRow = firstRow.clone();
        
        // Reset values
        newRow.find('input[type="text"]').val('');
        newRow.find('input[type="number"]').val('');
        newRow.find('select').prop('selectedIndex', 0);
        
        // Make the remove button active and visible in the cloned row
        newRow.find('.col-auto').html(
            '<button type="button" class="btn btn-danger remove-row" data-toggle="tooltip" title="Remove Course"><i class="fas fa-times"></i></button>'
        );
        
        $('#courses').append(newRow);
        
        // Re-init tooltips
        $('[data-toggle="tooltip"]').tooltip();
    });

    // Remove Course Row
    $(document).on('click', '.remove-row', function () {
        if ($('.course-row').length > 1) {
            var row = $(this).closest('.course-row');
            row.fadeOut(300, function() {
                $(this).tooltip('dispose');
                $(this).remove();
            });
        }
    });

    // Form Submission
    $('#gpaForm').submit(function (e) {
        e.preventDefault();
        
        $('#formAlert').empty();
        $('#calcBtn').html('<i class="fas fa-spinner fa-spin"></i> Processing...').prop('disabled', true);

        // Client Validate Duplicate Courses
        var courses = [];
        var duplicates = false;
        var limitExceeded = false;
        
        $('input[name="course[]"]').each(function() {
            var cName = $(this).val().trim().toLowerCase();
            if(cName !== '') {
                if(courses.includes(cName)) duplicates = true;
                courses.push(cName);
            }
        });

        $('input[name="credits[]"]').each(function() {
            if(parseInt($(this).val()) > 10) {
                limitExceeded = true;
            }
        });

        if (duplicates) {
            $('#formAlert').html('<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Duplicate course names found. Please merge or rename them.</div>');
            resetBtn();
            return;
        }

        if (limitExceeded) {
             $('#formAlert').html('<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Maximum allowed credits per single course is 10.</div>');
             resetBtn();
             return;
        }

        // AJAX POST
        $.ajax({
            url: 'calculate.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (response) {
                resetBtn();
                if (response.success) {
                    $('#resultSection').slideDown();
                    
                    // Display Data
                    $('#gpaScore').html('GPA: ' + parseFloat(response.gpa).toFixed(2));
                    $('#gpaInterpretation').html('Classification: <strong class="text-dark">' + response.interpretation + '</strong>');
                    $('#resultTableContainer').html(response.tableHtml);
                    
                    // Inject Export Button
                    $('#exportContainer').html(
                        '<a href="export_csv.php?id=' + response.calc_id + '" class="btn btn-outline-success"><i class="fas fa-file-csv"></i> Download CSV Report</a>'
                    );

                    // Update Progress Bar
                    var percent = (parseFloat(response.gpa) / 4.0) * 100;
                    var bar = $('#gpaProgressBar');
                    bar.css('width', '0%'); // reset
                    
                    // Assign Color Class Based on Interpretation
                    bar.removeClass('gpa-distinction gpa-merit gpa-pass gpa-fail');
                    if(response.gpa >= 3.7) bar.addClass('gpa-distinction');
                    else if (response.gpa >= 3.0) bar.addClass('gpa-merit');
                    else if (response.gpa >= 2.0) bar.addClass('gpa-pass');
                    else bar.addClass('gpa-fail');

                    setTimeout(function() {
                        bar.css('width', percent + '%').text(parseFloat(response.gpa).toFixed(2));
                    }, 100);

                    // Scroll to result
                    $('html, body').animate({
                        scrollTop: $("#resultSection").offset().top - 50
                    }, 500);

                } else {
                    $('#formAlert').html('<div class="alert alert-danger"><i class="fas fa-times-circle"></i> ' + response.message + '</div>');
                }
            },
            error: function () {
                resetBtn();
                $('#formAlert').html('<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Server connection failed. Did you import setup_db.sql and check db.php?</div>');
            }
        });
    });

    function resetBtn() {
        $('#calcBtn').html('Calculate & Save GPA <i class="fas fa-arrow-right ml-2"></i>').prop('disabled', false);
    }
});
