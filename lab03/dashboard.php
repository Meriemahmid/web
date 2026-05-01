<?php require_once '../../config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container mt-5">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>🎓 My Grades</h2>
        <a href="?page=student.history" class="btn btn-outline-primary">📋 History</a>
    </div>

    <!-- GPA Badge -->
    <div id="gpa-box" class="mb-4"></div>

    <!-- Grades Table -->
    <div class="card">
        <div class="card-header">
            <strong id="semester-title">Current Semester</strong>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Course</th>
                        <th>Credits</th>
                        <th>Grade</th>
                        <th>Points</th>
                    </tr>
                </thead>
                <tbody id="grades-body">
                    <tr><td colspan="4" class="text-center">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Export Button -->
    <div class="mt-3">
        <a href="../../api/gpa.php?action=export" class="btn btn-success">
            ⬇️ Export CSV
        </a>
    </div>

</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="../../public/js/student.js"></script>
</body>
</html>