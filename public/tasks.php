<?php
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
            <option>Health</option>
        </select>

        <select name="priority">
            <option>Low</option>
            <option>Medium</option>
            <option>High</option>
            <option>Urgent</option>
        </select>

        <input type="range" name="progress" min="0" max="100" value="0">

        <button type="submit">Add Task</button>
    </form>

    <h2>Active Tasks</h2>

    <?php foreach ($activeTasks as $task): ?>
        <div class="task-card">
            <h3><?= htmlspecialchars($task['title']) ?></h3>
            <p><?= htmlspecialchars($task['description']) ?></p>
            <p><?= $task['progress'] ?>% Complete</p>
        </div>
    <?php endforeach; ?>

    <h2>Completed Tasks</h2>

    <?php foreach ($completedTasks as $task): ?>
        <div class="task-card completed">
            <h3><?= htmlspecialchars($task['title']) ?></h3>
            <p><?= htmlspecialchars($task['description']) ?></p>
            <p>Completed on <?= $task['completed_at'] ?></p>
        </div>
    <?php endforeach; ?>
</body>
</html>