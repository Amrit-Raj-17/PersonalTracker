<?php
session_start();
require '../includes/db.php';
require '../includes/logVisit.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

logVisit('tasks');

$userId = $_SESSION['user_id'];
$role   = $_SESSION['role'];
$name   = $_SESSION['name'];

// ── Add task ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_task'])) {
    $targetUserId = ($role === 'admin' && !empty($_POST['target_user_id']))
        ? (int)$_POST['target_user_id']
        : $userId;

    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category    = trim($_POST['category']);
    $priority    = $_POST['priority'];
    $dueDate     = !empty($_POST['due_date']) ? $_POST['due_date'] : null;

    if (!empty($title)) {
        $stmt = $pdo->prepare("
            INSERT INTO tasks (user_id, title, description, category, priority, due_date)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$targetUserId, $title, $description, $category, $priority, $dueDate]);
    }

    header("Location: tasks.php");
    exit;
}

// ── Update task ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_task'])) {
    $taskId      = (int)$_POST['task_id'];
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category    = trim($_POST['category']);
    $priority    = $_POST['priority'];
    $status      = $_POST['status'];
    $progress    = (int)$_POST['progress'];
    $dueDate     = !empty($_POST['due_date']) ? $_POST['due_date'] : null;

    if ($status === 'Completed') $progress = 100;
    $completed   = $progress >= 100 ? 1 : 0;
    $completedAt = $completed ? date("Y-m-d H:i:s") : null;

    $whereExtra = $role === 'admin' ? '' : ' AND user_id = ?';
    $params = [
        $title, $description, $category, $priority, $status,
        $progress, $dueDate, $completed, $completedAt, $taskId
    ];
    if ($role !== 'admin') $params[] = $userId;

    $stmt = $pdo->prepare("
        UPDATE tasks
        SET title=?, description=?, category=?, priority=?, status=?,
            progress=?, due_date=?, completed=?, completed_at=?, updated_at=NOW()
        WHERE id=? $whereExtra
    ");
    $stmt->execute($params);

    header("Location: tasks.php");
    exit;
}

// ── Delete task ────────────────────────────────────────────────────
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
    exit;
}

// ── Fetch users for admin dropdown ────────────────────────────────
$allUsers = [];
if ($role === 'admin') {
    $allUsers = $pdo->query("SELECT id, name FROM users ORDER BY name")
                    ->fetchAll(PDO::FETCH_ASSOC);
}

// ── Fetch tasks ────────────────────────────────────────────────────
if ($role === 'admin') {
    $stmt = $pdo->query("
        SELECT tasks.*, users.name AS user_name
        FROM tasks
        JOIN users ON tasks.user_id = users.id
        ORDER BY completed ASC, created_at DESC
    ");
} else {
    $stmt = $pdo->prepare("
        SELECT * FROM tasks WHERE user_id = ?
        ORDER BY completed ASC, created_at DESC
    ");
    $stmt->execute([$userId]);
}
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$activePage = 'tasks';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks — Tracker</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/app.js" defer></script>
</head>
<body>
<div class="shell">
    <?php include '../includes/sidebar.php'; ?>

    <main class="main">
        <div class="page-header">
            <h2>Tasks</h2>
            <p><?= $role === 'admin' ? 'Managing all users\' tasks' : 'Your task board' ?></p>
        </div>

        <!-- Add Task Form -->
        <div class="form-card">
            <h3>Create New Task</h3>
            <form method="POST">
                <input type="hidden" name="add_task" value="1">

                <?php if ($role === 'admin'): ?>
                    <div class="form-group" style="margin-bottom:14px;">
                        <label>Assign to User</label>
                        <select name="target_user_id">
                            <?php foreach ($allUsers as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= $u['id'] == $userId ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['name']) ?><?= $u['id'] == $userId ? ' (you)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="form-group" style="margin-bottom:14px;">
                    <label>Title</label>
                    <input type="text" name="title" placeholder="Task title" required>
                </div>

                <div class="form-group" style="margin-bottom:14px;">
                    <label>Description</label>
                    <textarea name="description" placeholder="Optional description…"></textarea>
                </div>

                <div class="form-grid" style="margin-bottom:16px;">
                    <div class="form-group">
                        <label>Category</label>
                        <input type="text" name="category" placeholder="e.g. Work, Personal">
                    </div>
                    <div class="form-group">
                        <label>Priority</label>
                        <select name="priority">
                            <option value="Low">Low</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="High">High</option>
                            <option value="Urgent">Urgent</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Due Date</label>
                        <input type="date" name="due_date">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Add Task</button>
            </form>
        </div>

        <!-- Active Tasks -->
        <div class="section-heading">
            Active Tasks
            <button id="toggleCompleted" class="btn btn-ghost btn-sm">Show Completed</button>
        </div>

        <div class="task-list">
            <?php $hasActive = false; ?>
            <?php foreach ($tasks as $task): ?>
                <?php if ($task['completed']) continue; $hasActive = true; ?>
                <div class="task-card">
                    <form method="POST">
                        <input type="hidden" name="update_task" value="1">
                        <input type="hidden" name="task_id" value="<?= $task['id'] ?>">

                        <div class="task-header">
                            <input type="text" name="title"
                                   value="<?= htmlspecialchars($task['title']) ?>"
                                   class="task-title-input">
                            <?php if ($role === 'admin'): ?>
                                <span class="task-owner"><?= htmlspecialchars($task['user_name']) ?></span>
                            <?php endif; ?>
                        </div>

                        <textarea name="description"
                                  class="task-description"><?= htmlspecialchars($task['description'] ?? '') ?></textarea>

                        <div class="task-meta-grid">
                            <div class="form-group">
                                <label>Category</label>
                                <input type="text" name="category"
                                       value="<?= htmlspecialchars($task['category'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Priority</label>
                                <select name="priority">
                                    <?php foreach (['Low','Medium','High','Urgent'] as $p): ?>
                                        <option value="<?= $p ?>" <?= $task['priority'] === $p ? 'selected' : '' ?>><?= $p ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status">
                                    <?php foreach (['Not Started','In Progress','Completed'] as $s): ?>
                                        <option value="<?= $s ?>" <?= $task['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Due Date</label>
                                <input type="date" name="due_date"
                                       value="<?= $task['due_date'] ?? '' ?>">
                            </div>
                        </div>

                        <div class="progress-section">
                            <label>Progress <span class="range-value-label"><?= $task['progress'] ?>%</span></label>
                            <input type="range" min="0" max="100" step="5"
                                   name="progress" value="<?= $task['progress'] ?>"
                                   oninput="this.previousElementSibling.querySelector('.range-value-label').textContent=this.value+'%'">
                            <div class="progress-bar">
                                <div class="progress-fill" data-progress="<?= $task['progress'] ?>"></div>
                            </div>
                        </div>

                        <div class="task-actions">
                            <button type="submit" class="btn btn-primary btn-sm">Save</button>
                            <a href="tasks.php?delete=<?= $task['id'] ?>"
                               class="btn btn-danger btn-sm btn-delete">Delete</a>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>

            <?php if (!$hasActive): ?>
                <div class="empty-state">
                    <h3>No active tasks</h3>
                    <p>Use the form above to create one.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Completed Tasks (hidden by default) -->
        <div id="completedTasks" style="display:none;">
            <h2 class="completed-heading">Completed Tasks</h2>
            <div class="task-list">
                <?php foreach ($tasks as $task): ?>
                    <?php if (!$task['completed']) continue; ?>
                    <div class="task-card completed-card">
                        <div class="task-header">
                            <span style="font-size:15px;font-weight:600;flex:1;text-decoration:line-through;color:var(--muted);">
                                <?= htmlspecialchars($task['title']) ?>
                            </span>
                            <?php if ($role === 'admin'): ?>
                                <span class="task-owner"><?= htmlspecialchars($task['user_name']) ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($task['description'])): ?>
                            <p style="color:var(--muted);font-size:13px;margin:6px 0 10px;">
                                <?= nl2br(htmlspecialchars($task['description'])) ?>
                            </p>
                        <?php endif; ?>

                        <div class="task-summary">
                            <span><?= htmlspecialchars($task['category'] ?? 'General') ?></span>
                            <span><?= htmlspecialchars($task['priority']) ?></span>
                            <span>100% Complete</span>
                        </div>

                        <?php if (!empty($task['completed_at'])): ?>
                            <small style="color:var(--muted);font-size:11px;display:block;margin-top:10px;">
                                Completed <?= date('d M Y, h:i A', strtotime($task['completed_at'])) ?>
                            </small>
                        <?php endif; ?>

                        <div class="task-actions" style="margin-top:12px;">
                            <a href="tasks.php?delete=<?= $task['id'] ?>"
                               class="btn btn-danger btn-sm btn-delete">Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</div>
</body>
</html>
