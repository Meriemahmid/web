<?php
require_once '../config.php';

// 
$_SESSION['user_id'] = 3;
$_SESSION['role'] = 'student';
$_SESSION['last_activity'] = time();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SESSION['role'] !== 'student') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$action    = $_GET['action'] ?? null;
$studentId = (int) $_SESSION['user_id'];

if ($action !== 'export') {
    header('Content-Type: application/json');
}

switch ($action) {

    case 'current':
        $stmt = $pdo->query('SELECT id, label, academic_year FROM semesters WHERE is_active = 1 LIMIT 1');
        $semester = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$semester) {
            echo json_encode(['error' => 'No active semester']);
            break;
        }

        $stmt = $pdo->prepare('SELECT 1 FROM enrollments WHERE student_id = ? AND semester_id = ?');
        $stmt->execute([$studentId, $semester['id']]);

        if (!$stmt->fetch()) {
            echo json_encode(['error' => 'Not enrolled in active semester']);
            break;
        }

        $stmt = $pdo->prepare('
            SELECT c.id, c.name, c.credits, g.grade
            FROM courses c
            LEFT JOIN grades g
                ON g.course_id = c.id
                AND g.student_id = ?
                AND g.semester_id = ?
            WHERE c.semester_id = ?
            ORDER BY c.name
        ');
        $stmt->execute([$studentId, $semester['id'], $semester['id']]);
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare('SELECT gpa FROM gpa_records WHERE student_id = ? AND semester_id = ?');
        $stmt->execute([$studentId, $semester['id']]);
        $gpaRow = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'semester' => $semester,
            'courses'  => $courses,
            'gpa'      => $gpaRow ? (float) $gpaRow['gpa'] : null
        ]);
        break;

    case 'history':
        $stmt = $pdo->prepare('
            SELECT s.id, s.label, s.academic_year
            FROM semesters s
            JOIN enrollments e ON e.semester_id = s.id
            WHERE e.student_id = ?
            ORDER BY s.id ASC
        ');
        $stmt->execute([$studentId]);
        $semesters = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($semesters as &$sem) {
            $stmt = $pdo->prepare('
                SELECT c.name, c.credits, g.grade
                FROM courses c
                LEFT JOIN grades g
                    ON g.course_id = c.id
                    AND g.student_id = ?
                    AND g.semester_id = ?
                WHERE c.semester_id = ?
                ORDER BY c.name
            ');
            $stmt->execute([$studentId, $sem['id'], $sem['id']]);
            $sem['courses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare('SELECT gpa FROM gpa_records WHERE student_id = ? AND semester_id = ?');
            $stmt->execute([$studentId, $sem['id']]);
            $gpaRow = $stmt->fetch(PDO::FETCH_ASSOC);
            $sem['gpa'] = $gpaRow ? (float) $gpaRow['gpa'] : null;
        }

        echo json_encode($semesters);
        break;

    case 'export':
        $stmt = $pdo->prepare('
            SELECT
                s.label        AS semester_label,
                s.academic_year,
                c.name         AS course_name,
                c.credits,
                g.grade
            FROM grades g
            JOIN courses   c ON c.id = g.course_id
            JOIN semesters s ON s.id = g.semester_id
            WHERE g.student_id = ?
            ORDER BY s.id ASC, c.name ASC
        ');
        $stmt->execute([$studentId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="gpa_history.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Semester', 'Academic Year', 'Course', 'Credits', 'Grade', 'Grade Points']);

        foreach ($rows as $row) {
            fputcsv($out, [
                $row['semester_label'],
                $row['academic_year'],
                $row['course_name'],
                $row['credits'],
                $row['grade'] ?? 'Pending',
                $row['grade'] ? round($row['grade'] * $row['credits'], 2) : 0
            ]);
        }

        fclose($out);
        exit;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}
