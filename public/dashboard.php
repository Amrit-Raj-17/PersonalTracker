<?php
require '../includes/auth.php';
require '../includes/db.php';
require '../includes/logVisit.php';

logVisit('dashboardPage');

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = ?");
$stmt->execute([$userId]);
$total = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = ? AND completed = true");
$stmt->execute([$userId]);
$completed = $stmt->fetchColumn();

$pending = $total - $completed;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav>
        <h2>Hello, <?= htmlspecialchars($_SESSION['name']) ?></h2>

        <div>
            <a href="dashboard.php">Dashboard</a>
            <a href="tasks.php">My Tasks</a>

            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="admin.php">Admin Panel</a>
            <?php endif; ?>

            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="cards">
        <div class="card">
            <h3>Total Tasks</h3>
            <p><?= $total ?></p>
        </div>

        <div class="card">
            <h3>Completed</h3>
            <p><?= $completed ?></p>
        </div>

        <div class="card">
            <h3>Pending</h3>
            <p><?= $pending ?></p>
        </div>
    </div>
</body>
</html>