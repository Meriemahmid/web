<?php
require_once 'db.php';

if (!isset($pdo)) die('Database connection failed.');
if (!isset($_GET['id'])) die('No calculation ID specified.');

$calcId = intval($_GET['id']);

try {
    $stmt1 = $pdo->prepare("SELECT * FROM calculations WHERE id = ?");
    $stmt1->execute([$calcId]);
    $calc = $stmt1->fetch(PDO::FETCH_ASSOC);

    if (!$calc) die('Calculation record not found.');

    $stmt2 = $pdo->prepare("SELECT * FROM courses WHERE calculation_id = ?");
    $stmt2->execute([$calcId]);
    $courses = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=GPA_Report_' . preg_replace('/[^A-Za-z0-9_]/', '', $calc['student_name']) . "_".time().".csv");
    
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel support
    fputs($output, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF)));

    fputcsv($output, ['GPA CALCULATOR PRO - OFFICIAL REPORT']);
    fputcsv($output, ['']);
    fputcsv($output, ['Student Name', $calc['student_name']]);
    fputcsv($output, ['Semester', $calc['semester']]);
    fputcsv($output, ['Date Calculated', date('M d, Y H:i:s', strtotime($calc['created_at']))]);
    fputcsv($output, ['']);
    fputcsv($output, ['FINAL GPA', number_format($calc['gpa'], 2)]);
    fputcsv($output, ['CLASSIFICATION', $calc['interpretation']]);
    fputcsv($output, ['']);
    fputcsv($output, ['--- COURSE BREAKDOWN ---']);
    fputcsv($output, ['Course Content ID', 'Course Name', 'Credits Registered', 'Grade Value Achieved', 'Weighted Points']);
    
    foreach ($courses as $c) {
        $weighted = $c['credits'] * $c['grade'];
        fputcsv($output, [$c['id'], $c['course_name'], $c['credits'], number_format($c['grade'], 1), number_format($weighted, 2)]);
    }
    
    fclose($output);
    exit;
} catch (PDOException $e) {
    die("Database access error.");
}
?>
