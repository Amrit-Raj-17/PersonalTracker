<?php
require '../includes/auth.php';
require '../includes/db.php';

if ($_SESSION['role'] !== 'admin') {
    die('Access denied');
}

$stmt = $pdo->query(
    "SELECT users.name, users.email,
            COUNT(tasks.id) AS total_tasks,
            COUNT(CASE WHEN tasks.completed = true THEN 1 END) AS completed_tasks
     FROM users
     LEFT JOIN tasks ON users.id = tasks.user_id
     GROUP BY users.id
     ORDER BY users.name"
);

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>Admin Panel</h1>

    <table>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Total Tasks</th>
            <th>Completed</th>
        </tr>

        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= htmlspecialchars($user['name']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td><?= $user['total_tasks'] ?></td>
                <td><?= $user['completed_tasks'] ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>