<?php
require '../includes/auth.php';
require '../includes/db.php';

$userId = $_SESSION['user_id'] ?? null;

// Fetch all visits
$stmt = $pdo->prepare("SELECT * FROM visits");
$stmt->execute();
$visitData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all users
$stmt = $pdo->prepare("SELECT * FROM users");
$stmt->execute();
$usersData = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Visits</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<h1>Visits Dashboard</h1>

</body>

<script>
    const visitData = <?php echo json_encode($visitData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const userData = <?php echo json_encode($usersData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

    console.log("Visits:", visitData);
    console.log("Users:", userData);

</script>

</html>