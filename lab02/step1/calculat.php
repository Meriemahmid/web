<?php
echo "PHP is working";
// التحقق من وجود البيانات القادمة من الفورم
if (isset($_POST['course'], $_POST['credits'], $_POST['grade'])) {

    $courses = $_POST['course'];
    $credits = $_POST['credits'];
    $grades = $_POST['grade'];

    $totalPoints = 0;
    $totalCredits = 0;

    echo "<table border='1'>";
    echo "<tr>
            <th>Course</th>
            <th>Credits</th>
            <th>Grade</th>
            <th>Grade Points</th>
          </tr>";

    // التأكد من أن جميع المصفوفات بنفس الطول
    $count = min(count($courses), count($credits), count($grades));

    for ($i = 0; $i < $count; $i++) {

        // حماية من XSS
        $course = htmlspecialchars($courses[$i]);

        // تحويل القيم إلى أرقام
        $cr = floatval($credits[$i]);
        $g = floatval($grades[$i]);

        // تجاهل القيم غير الصحيحة
        if ($cr <= 0 || $g < 0) continue;

        $pts = $cr * $g;

        $totalPoints += $pts;
        $totalCredits += $cr;

        echo "<tr>
                <td>$course</td>
                <td>$cr</td>
                <td>$g</td>
                <td>$pts</td>
              </tr>";
    }

    echo "</table>";

    if ($totalCredits > 0) {

        $gpa = $totalPoints / $totalCredits;

        if ($gpa >= 3.7) {
            $interpretation = "Distinction";
        } elseif ($gpa >= 3.0) {
            $interpretation = "Merit";
        } elseif ($gpa >= 2.0) {
            $interpretation = "Pass";
        } else {
            $interpretation = "Fail";
        }

        echo "<p>Your GPA is <strong>" . number_format($gpa, 2) .
             "</strong> ($interpretation).</p>";

    } else {
        echo "<p>No valid courses entered.</p>";
    }

} else {
    echo "Data not received.";
}
?>
