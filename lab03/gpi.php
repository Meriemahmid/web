<?php
      
    require_once '../config.php';

    requireRole('student');

    $studentId = $_SESSION['user_id'];

    $action = $_GET['action'] ?? '';

    if ($action !== 'export') {
        header('Content-Type: application/json');
    }

    match($action) {
        'current' => getCurrent($pdo, $studentId),
        'history' => getHistory($pdo, $studentId),
        'export'  => exportCSV($pdo, $studentId),
        default   => http_response_code(400)
    };
    function getCurrent($pdo, $studentId) {

    $stmt = $pdo->prepare("
        SELECT * FROM semesters 
        WHERE is_active = 1 
        LIMIT 1
    ");
    $stmt->execute();
    $semester = $stmt->fetch(PDO::FETCH_ASSOC);

    // لو ما في فصل نشط
    if (!$semester) {
        echo json_encode(['error' => 'No active semester']);
        return;
    }

    // نتحقق أن الطالب مسجل في هذا الفصل
    $stmt = $pdo->prepare("
        SELECT * FROM enrollments 
        WHERE student_id = ? AND semester_id = ?
    ");
    $stmt->execute([$studentId, $semester['id']]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['error' => 'Not enrolled']);
        return;
    }

   // نجيب المواد مع درجة كل مادة
      $stmt = $pdo->prepare("
          SELECT 
              c.id,
              c.name,
              c.credits,
              g.grade
          FROM courses c
          LEFT JOIN grades g 
              ON g.course_id = c.id 
              AND g.student_id = ?
              AND g.semester_id = ?
          WHERE c.semester_id = ?
      ");
      $stmt->execute([$studentId, $semester['id'], $semester['id']]);
      $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

      // نجيب الـ GPA من جدول gpa_records
      $stmt = $pdo->prepare("
          SELECT gpa FROM gpa_records 
          WHERE student_id = ? AND semester_id = ?
      ");
      $stmt->execute([$studentId, $semester['id']]);
      $gpaRow = $stmt->fetch(PDO::FETCH_ASSOC);

      // نرد بـ JSON
      echo json_encode([
          'semester' => $semester,
          'courses'  => $courses,
          'gpa'      => $gpaRow ? $gpaRow['gpa'] : null
      ]);
  }
  
  function getHistory($pdo, $studentId) {

    // نجيب كل الفصول التي الطالب مسجل فيها
    $stmt = $pdo->prepare("
        SELECT s.* 
        FROM semesters s
        JOIN enrollments e ON e.semester_id = s.id
        WHERE e.student_id = ?
        ORDER BY s.id ASC
    ");
    $stmt->execute([$studentId]);
    $semesters = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // لكل فصل نجيب مواده ودرجاته و GPA
    foreach ($semesters as &$sem) {
        
        $stmt = $pdo->prepare("
            SELECT 
                c.name,
                c.credits,
                g.grade
            FROM courses c
            LEFT JOIN grades g 
                ON g.course_id = c.id 
                AND g.student_id = ?
                AND g.semester_id = ?
            WHERE c.semester_id = ?
        ");
        $stmt->execute([$studentId, $sem['id'], $sem['id']]);
        $sem['courses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("
            SELECT gpa FROM gpa_records 
            WHERE student_id = ? AND semester_id = ?
        ");
        $stmt->execute([$studentId, $sem['id']]);
        $gpaRow = $stmt->fetch(PDO::FETCH_ASSOC);
        $sem['gpa'] = $gpaRow ? $gpaRow['gpa'] : null;
    }

    echo json_encode($semesters);
  }
  function exportCSV($pdo, $studentId) {

    // نجيب كل الدرجات مع تفاصيلها
    $stmt = $pdo->prepare("
        SELECT 
            s.label       AS semester_label,
            s.academic_year,
            c.name        AS course_name,
            c.credits,
            g.grade
        FROM grades g
        JOIN courses c   ON c.id = g.course_id
        JOIN semesters s ON s.id = g.semester_id
        WHERE g.student_id = ?
        ORDER BY s.id ASC
    ");
    $stmt->execute([$studentId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // نقول للمتصفح: هذا ملف CSV للتحميل
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="gpa_history.csv"');

    $out = fopen('php://output', 'w');
    
    // السطر الأول: عناوين الأعمدة
    fputcsv($out, ['Semester', 'Academic Year', 'Course', 'Credits', 'Grade', 'Grade Points']);

    // باقي الصفوف
    foreach ($rows as $row) {
        fputcsv($out, [
            $row['semester_label'],
            $row['academic_year'],
            $row['course_name'],
            $row['credits'],
            $row['grade'],
            $row['grade'] * $row['credits']  // grade points
        ]);
    }

    fclose($out);
    exit;
  }
?>
