<?php
require '../includes/auth.php';
require '../includes/db.php';

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM visits;");
$stmt->execute([$userId]);
$visitData = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT * FROM users;");
$stmt->execute([$userId]);
$usersData = $stmt->fetchColumn();
?>


<!DOCTYPE html>
<html>
<head>
    <title>Visits</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
</body>
    <script>
        const visitData = <?php echo json_encode($visitData)?>
        const userData = <?php echo json_encode($userData)?>
        console.log(visitData);
        console.log(userData);
    </script>
</html>