<?php
session_start();
require '../includes/db.php';
require '../includes/logVisit.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

logVisit('admin');

if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

$userId = $_SESSION['user_id'];
$role   = $_SESSION['role'];
$name   = $_SESSION['name'];

$users = $pdo->query("
    SELECT
        users.id,
        users.name,
        users.email,
        users.role,
        users.created_at,
        COUNT(tasks.id) AS total_tasks,
        COUNT(CASE WHEN tasks.completed = true THEN 1 END) AS completed_tasks,
        COUNT(CASE WHEN tasks.completed = false THEN 1 END) AS active_tasks,
        (SELECT COUNT(*) FROM notes WHERE notes.user_id = users.id) AS total_notes
    FROM users
    LEFT JOIN tasks ON users.id = tasks.user_id
    GROUP BY users.id
    ORDER BY users.name
")->fetchAll(PDO::FETCH_ASSOC);

$activePage = 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users — Admin</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="shell">
    <?php include '../includes/sidebar.php'; ?>

    <main class="main">
        <div class="page-header">
            <h2>User Overview</h2>
            <p>All registered users and their activity.</p>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Total Tasks</th>
                        <th>Active</th>
                        <th>Completed</th>
                        <th>Notes</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td style="font-weight:600;"><?= htmlspecialchars($user['name']) ?></td>
                            <td style="color:var(--muted);"><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                <?php if ($user['role'] === 'admin'): ?>
                                    <span class="role-admin">Admin</span>
                                <?php else: ?>
                                    <span class="role-user">User</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $user['total_tasks'] ?></td>
                            <td>
                                <?php if ($user['active_tasks'] > 0): ?>
                                    <span style="color:var(--warn);font-weight:600;"><?= $user['active_tasks'] ?></span>
                                <?php else: ?>
                                    <span style="color:var(--muted);">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="color:var(--success);font-weight:600;"><?= $user['completed_tasks'] ?></span>
                            </td>
                            <td style="color:var(--accent);"><?= $user['total_notes'] ?></td>
                            <td style="color:var(--muted);font-size:12px;">
                                <?= date('d M Y', strtotime($user['created_at'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>
</html>
