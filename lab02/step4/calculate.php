<?php
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($pdo)) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed. Please ensure setup.php has been executed.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['course'], $_POST['credits'], $_POST['grade'], $_POST['student_name'], $_POST['semester'])) {
        $studentName = trim($_POST['student_name']);
        $semester = trim($_POST['semester']);
        $courses = $_POST['course'];
        $credits = $_POST['credits'];
        $grades = $_POST['grade'];

        // Basic Validation
        if (empty($studentName) || empty($semester)) {
            echo json_encode(['success' => false, 'message' => 'Student Name and Semester are strictly required.']);
            exit;
        }

        $totalPoints = 0;
        $totalCredits = 0;
        $validCourses = [];
        $uniqueCheck = [];

        for ($i = 0; $i < count($courses); $i++) {
            $cName = trim(htmlspecialchars($courses[$i]));
            $cr = floatval($credits[$i]);
            $g = floatval($grades[$i]);
            
            if ($cName === '' || $cr <= 0) continue;
            
            // Server side duplication check
            if (in_array(strtolower($cName), $uniqueCheck)) {
                 echo json_encode(['success' => false, 'message' => "Duplicate course detected at server side: $cName"]);
                 exit;
            }
            $uniqueCheck[] = strtolower($cName);
            
            if ($cr > 10) {
                 echo json_encode(['success' => false, 'message' => "Maximum allowed credits per single course is 10. Found: $cr"]);
                 exit;
            }

            $pts = $cr * $g;
            $totalPoints += $pts;
            $totalCredits += $cr;
            
            $validCourses[] = [
                'name' => $cName,
                'credits' => $cr,
                'grade' => $g,
                'pts' => $pts
            ];
        }

        if ($totalCredits > 0) {
            $gpa = $totalPoints / $totalCredits;
            
            // Interpret GPA
            if ($gpa >= 3.7) $interpretation = "Distinction";
            elseif ($gpa >= 3.0) $interpretation = "Merit";
            elseif ($gpa >= 2.0) $interpretation = "Pass";
            else $interpretation = "Fail";

            // Save to Database
            try {
                $pdo->beginTransaction();
                
                $stmt = $pdo->prepare("INSERT INTO calculations (student_name, semester, gpa, interpretation) VALUES (?, ?, ?, ?)");
                $stmt->execute([$studentName, $semester, $gpa, $interpretation]);
                $calcId = $pdo->lastInsertId();
                
                $stmtCourse = $pdo->prepare("INSERT INTO courses (calculation_id, course_name, credits, grade) VALUES (?, ?, ?, ?)");
                foreach ($validCourses as $c) {
                    $stmtCourse->execute([$calcId, $c['name'], $c['credits'], $c['grade']]);
                }
                
                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
                exit;
            }

            // Generate HTML Output
            $tableHtml = '<table class="table table-hover mt-4 bg-white border rounded shadow-sm">
                            <thead class="bg-light">
                            <tr>
                                <th>Course</th>
                                <th>Credits</th>
                                <th>Grade Points Logged</th>
                                <th>Weighted Points</th>
                            </tr>
                            </thead><tbody>';
            foreach ($validCourses as $c) {
                $tableHtml .= "<tr><td>{$c['name']}</td><td>{$c['credits']}</td><td>" . number_format($c['grade'], 1) . "</td><td>" . number_format($c['pts'], 2) . "</td></tr>";
            }
            $tableHtml .= "<tr class='table-active font-weight-bold tracking-wide'>
                                <td>Cumulative</td>
                                <td>{$totalCredits} Credits</td>
                                <td>-</td>
                                <td>" . number_format($totalPoints, 2) . " Pts</td>
                           </tr>";
            $tableHtml .= '</tbody></table>';

            echo json_encode([
                'success' => true,
                'gpa' => $gpa,
                'interpretation' => $interpretation,
                'message' => 'Calculation complete',
                'tableHtml' => $tableHtml,
                'calc_id' => $calcId
            ]);
            
        } else {
            echo json_encode(['success' => false, 'message' => 'No valid courses entered. Please check your inputs.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Incomplete data received.']);
    }
}
?>
