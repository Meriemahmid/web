<?php
 $result ="";
 $tableHtml ="";

 if ($_SERVER['REQUEST_METHOD']=='POST') {
 $courses = $_POST['course'] ?? [];
 $credits = $_POST['credits'] ?? [];
 $grades = $_POST['grade'] ?? [];
 $totalPoints = 0;
 $totalCredits = 0;

 $tableHtml = "<table>";
 $tableHtml .= "<tr>
 <th> Course </th> 
 <th> Credits </th>
 <th>Grade </th> 
 <th> Grade Points </th>
 </tr>";

 
 for ($i=0; $i < count($courses) ; $i++) {
 $course = htmlspecialchars($courses[$i]);
 $cr = floatval($credits[$i]);
 $g = floatval($grades[$i]);

 if ($cr<=0) continue;
 $pts = $cr * $g;

 $totalPoints += $pts;
 $totalCredits += $cr;

 $tableHtml .= "<tr>
 <td> $course </td> <td>$cr</td>
 <td>$g</td> <td>$pts</td>
 </tr>";
  }
 $tableHtml .="</table>";
if ( $totalCredits > 0) {
 $gpa = $totalPoints/$totalCredits;
 if ( $gpa >= 3.7) {
 $interpretation = "Distinction";
 } elseif ( $gpa >= 3.0) {
 $interpretation = "Merit";
  } elseif ( $gpa >= 2.0) {
 $interpretation = "Pass";
} else {
 $interpretation = "Fail";
 }
 $result = " Your GPA is " .number_format( $gpa, 2)
 ."( $interpretation ).";
 } else {
 $result = "No valid courses entered .";
 }
 }
 ?>
<!DOCTYPE html>
 <html lang ="en">
 <head>
 <meta charset ="UTF-8">
 <title > GPA Calculator </title>
 <link rel =" stylesheet " href=" style.css">
 <script src ="script.js"></script>
 </head>
 <body>
 <h1> GPA Calculator </h1>
 <?php if ( $result != "") : ?>
 <?php echo $tableHtml ; ?>
 <p> <strong> <?=$result ?> </strong> </p>
 <?php endif ; ?>
 <form action ="" method ="post" onsubmit ="return validateForm ();">
 <div id ="courses">
 <div class ="course-row">
 <label> Course : </label>
 <input type ="text" name ="cours[]"
 placeholder ="e.g.Mathematics " required>
 <label> Credits : </label>
 <input type ="number" name ="credits[]"
 placeholder ="e.g.3" min ="1" required>
 <label> Grade : </label>
 <select name ="grade[]">
 <option value ="4.0">A</option>
 <option value ="3.0">B</option>
 <option value ="2.0">C</option>
 <option value ="1.0">D</option>
 <option value ="0.0">F</option>
 </select>
 </div>
 </div>
 <button type ="button" onclick ="addCourse ()">
 + Add Course
 </button> <br> </br>
 <input type ="submit" value ="Calculate GPA">
 </form>
 </body>
 </html>
