<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$userId = $_SESSION['user_id'];
$role = $_SESSION['role'];
$name = $_SESSION['name'];

// Add task
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_task'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $priority = $_POST['priority'];
    $dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : null;

    if (!empty($title)) {
        $stmt = $pdo->prepare("
            INSERT INTO tasks (
                user_id, title, description, category, priority, due_date
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $userId,
            $title,
            $description,
            $category,
            $priority,
            $dueDate
        ]);
    }

    header("Location: tasks.php");
    exit();
}

// Update task progress / details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_task'])) {
    $taskId = $_POST['task_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $priority = $_POST['priority'];
    $status = $_POST['status'];
    $progress = (int)$_POST['progress'];
    $dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : null;

    if($status == 'Completed') {
        $progress = 100;
    }
    $completed = $progress >= 100 ? 1 : 0;
    $completedAt = $completed ? date("Y-m-d H:i:s") : null;

    if ($role === 'admin') {
        $stmt = $pdo->prepare("
            UPDATE tasks
            SET
                title = ?,
                description = ?,
                category = ?,
                priority = ?,
                status = ?,
                progress = ?,
                due_date = ?,
                completed = ?,
                completed_at = ?,
                updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->execute([
            $title,
            $description,
            $category,
            $priority,
            $status,
            $progress,
            $dueDate,
            $completed,
            $completedAt,
            $taskId
        ]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE tasks
            SET
                title = ?,
                description = ?,
                category = ?,
                priority = ?,
                status = ?,
                progress = ?,
                due_date = ?,
                completed = ?,
                completed_at = ?,
                updated_at = NOW()
            WHERE id = ? AND user_id = ?
        ");

        $stmt->execute([
            $title,
            $description,
            $category,
            $priority,
            $status,
            $progress,
            $dueDate,
            $completed,
            $completedAt,
            $taskId,
            $userId
        ]);
    }

    header("Location: tasks.php");
    exit();
}

// Delete task
if (isset($_GET['delete'])) {
    $taskId = (int)$_GET['delete'];

    if ($role === 'admin') {
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
        $stmt->execute([$taskId]);
    } else {
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
        $stmt->execute([$taskId, $userId]);
    }

    header("Location: tasks.php");
    exit();
}

// Fetch tasks
if ($role === 'admin') {
    $stmt = $pdo->query("
        SELECT tasks.*, users.name AS user_name
        FROM tasks
        JOIN users ON tasks.user_id = users.id
        ORDER BY completed ASC, created_at DESC
    ");
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare("
        SELECT *
        FROM tasks
        WHERE user_id = ?
        ORDER BY completed ASC, created_at DESC
    ");
    $stmt->execute([$userId]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks - Personal Tracker</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/app.js" defer></script>
</head>
<body>

<div class="dashboard-container">
    <div class="topbar">
        <h1>My Task Tracker</h1>
        <div class="topbar-right">
            <span>
                Welcome,
                <?= htmlspecialchars($name) ?>
                (<?= htmlspecialchars($role) ?>)
            </span>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>

    <div class="task-form-card">
        <h2>Create New Task</h2>

        <form method="POST" class="task-form">
            <input type="hidden" name="add_task" value="1">

            <input
                type="text"
                name="title"
                placeholder="Task Title"
                required
            >

            <textarea
                name="description"
                placeholder="Description"
            ></textarea>

            <div class="form-grid">
                <input
                    type="text"
                    name="category"
                    placeholder="Category"
                >

                <select name="priority">
                    <option value="Low">Low Priority</option>
                    <option value="Medium" selected>Medium Priority</option>
                    <option value="High">High Priority</option>
                </select>

                <input type="date" name="due_date">
            </div>

            <button type="submit" class="btn btn-primary">
                Add Task
            </button>
        </form>
    </div>

    <div class="task-section">
        <div class="section-header">
            <h2>Active Tasks</h2>
            <button id="toggleCompleted" class="btn btn-secondary">
                Show Completed Tasks
            </button>
        </div>

        <?php foreach ($tasks as $task): ?>
            <?php if (!$task['completed']): ?>
                <div class="task-card">
                    <form method="POST">
                        <input type="hidden" name="update_task" value="1">
                        <input type="hidden" name="task_id" value="<?= $task['id'] ?>">

                        <div class="task-header">
                            <input
                                type="text"
                                name="title"
                                value="<?= htmlspecialchars($task['title']) ?>"
                                class="task-title-input"
                            >

                            <?php if ($role === 'admin'): ?>
                                <span class="task-owner">
                                    <?= htmlspecialchars($task['user_name']) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <textarea
                            name="description"
                            class="task-description"
                        ><?= htmlspecialchars($task['description']) ?></textarea>

                        <div class="task-meta-grid">
                            <input
                                type="text"
                                name="category"
                                value="<?= htmlspecialchars($task['category']) ?>"
                            >

                            <select name="priority">
                                <option value="Low" <?= $task['priority'] === 'Low' ? 'selected' : '' ?>>Low</option>
                                <option value="Medium" <?= $task['priority'] === 'Medium' ? 'selected' : '' ?>>Medium</option>
                                <option value="High" <?= $task['priority'] === 'High' ? 'selected' : '' ?>>High</option>
                            </select>

                            <select name="status">
                                <option value="Not Started" <?= $task['status'] === 'Not Started' ? 'selected' : '' ?>>Not Started</option>
                                <option value="In Progress" <?= $task['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="Completed" <?= $task['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                            </select>

                            <input
                                type="date"
                                name="due_date"
                                value="<?= $task['due_date'] ?>"
                            >
                        </div>

                        <div class="progress-section">
                            <label>Progress: <?= $task['progress'] ?>%</label>

                            <input
                                type="range"
                                min="0"
                                max="100"
                                step="5"
                                name="progress"
                                value="<?= $task['progress'] ?>"
                            >

                            <div class="progress-bar">
                                <div
                                    class="progress-fill"
                                    data-progress="<?= $task['progress'] ?>"
                                ></div>
                            </div>
                        </div>

                        <div class="task-actions">
                            <button type="submit" class="btn btn-primary">
                                Save Changes
                            </button>

                            <a
                                href="tasks.php?delete=<?= $task['id'] ?>"
                                class="btn btn-danger btn-delete"
                            >
                                Delete
                            </a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>

        <div id="completedTasks" style="display:none;">
            <h2 class="completed-heading">Completed Tasks</h2>

            <?php foreach ($tasks as $task): ?>
                <?php if ($task['completed']): ?>
                    <div class="task-card completed-card">
                        <div class="task-header">
                            <h3><?= htmlspecialchars($task['title']) ?></h3>

                            <?php if ($role === 'admin'): ?>
                                <span class="task-owner">
                                    <?= htmlspecialchars($task['user_name']) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <p><?= nl2br(htmlspecialchars($task['description'])) ?></p>

                        <div class="task-summary">
                            <span><?= htmlspecialchars($task['category']) ?></span>
                            <span><?= htmlspecialchars($task['priority']) ?></span>
                            <span>100% Complete</span>
                        </div>

                        <?php if (!empty($task['completed_at'])): ?>
                            <small>
                                Completed on:
                                <?= date("d M Y, h:i A", strtotime($task['completed_at'])) ?>
                            </small>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>

</body>
</html>