<?php
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($pdo)) {
    echo json_encode([]);
    exit;
}

$student = isset($_GET['student']) ? trim($_GET['student']) : '';

try {
    if ($student !== '') {
        $stmt = $pdo->prepare("SELECT * FROM calculations WHERE student_name LIKE ? ORDER BY created_at DESC LIMIT 30");
        $stmt->execute(["%$student%"]);
    } else {
        $stmt = $pdo->query("SELECT * FROM calculations ORDER BY created_at DESC LIMIT 30");
    }
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the date before sending
    foreach ($results as &$r) {
        $r['created_at'] = date('M d, Y h:i A', strtotime($r['created_at']));
    }

    echo json_encode($results);
} catch (PDOException $e) {
    echo json_encode([]);
}
?>
