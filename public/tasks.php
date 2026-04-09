<?php
require '../includes/auth.php';
require '../includes/db.php';

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $priority = $_POST['priority'];
    $progress = (int) $_POST['progress'];

    $completed = $progress >= 100;

    $stmt = $pdo->prepare(
        "INSERT INTO tasks(user_id, title, description, category, priority, progress, completed, completed_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt->execute([
        $userId,
        $title,
        $description,
        $category,
        $priority,
        $progress,
        $completed,
        $completed ? date('Y-m-d H:i:s') : null
    ]);
}

$stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id = ? AND completed = false ORDER BY created_at DESC");
$stmt->execute([$userId]);
$activeTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id = ? AND completed = true ORDER BY completed_at DESC");
$stmt->execute([$userId]);
$completedTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Tasks</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>My Tasks</h1>

    <form method="POST" class="task-form">
        <input type="text" name="title" placeholder="Task Title" required>
        <textarea name="description" placeholder="Description"></textarea>

        <select name="category">
            <option>Study</option>
            <option>Home</option>
            <option>Shopping</option>
            <option>Office</option>
</html>