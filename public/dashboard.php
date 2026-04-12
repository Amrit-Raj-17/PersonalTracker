<?php
session_start();
require '../includes/db.php';
require '../includes/logVisit.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

logVisit('dashboard');

$userId = $_SESSION['user_id'];
$role   = $_SESSION['role'];
$name   = $_SESSION['name'];

// ── Stats ──────────────────────────────────────────────────────────
if ($role === 'admin') {
    $totalTasks     = $pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
    $activeTasks    = $pdo->query("SELECT COUNT(*) FROM tasks WHERE completed = false")->fetchColumn();
    $completedTasks = $pdo->query("SELECT COUNT(*) FROM tasks WHERE completed = true")->fetchColumn();
    $totalNotes     = $pdo->query("SELECT COUNT(*) FROM notes")->fetchColumn();
} else {
    $s = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = ?");
    $s->execute([$userId]); $totalTasks = $s->fetchColumn();

    $s = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = ? AND completed = false");
    $s->execute([$userId]); $activeTasks = $s->fetchColumn();

    $s = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = ? AND completed = true");
    $s->execute([$userId]); $completedTasks = $s->fetchColumn();

    $s = $pdo->prepare("SELECT COUNT(*) FROM notes WHERE user_id = ?");
    $s->execute([$userId]); $totalNotes = $s->fetchColumn();
}

// ── Notes (pinned first, max 6) ────────────────────────────────────
if ($role === 'admin') {
    $noteStmt = $pdo->query("
        SELECT notes.*, users.name AS user_name
        FROM notes
        JOIN users ON notes.user_id = users.id
        ORDER BY notes.pinned DESC, notes.created_at DESC
        LIMIT 6
    ");
} else {
    $noteStmt = $pdo->prepare("
        SELECT * FROM notes
        WHERE user_id = ?
        ORDER BY pinned DESC, created_at DESC
        LIMIT 6
    ");
    $noteStmt->execute([$userId]);
}
$notes = $noteStmt->fetchAll(PDO::FETCH_ASSOC);

// ── Active Tasks ───────────────────────────────────────────────────
if ($role === 'admin') {
    $taskStmt = $pdo->query("
        SELECT tasks.*, users.name AS user_name
        FROM tasks
        JOIN users ON tasks.user_id = users.id
        WHERE tasks.completed = false
        ORDER BY tasks.created_at DESC
    ");
} else {
    $taskStmt = $pdo->prepare("
        SELECT * FROM tasks
        WHERE user_id = ? AND completed = false
        ORDER BY created_at DESC
    ");
    $taskStmt->execute([$userId]);
}
$tasks = $taskStmt->fetchAll(PDO::FETCH_ASSOC);

$activePage = 'dashboard';
include '../includes/sidebar.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Tracker</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/app.js" defer></script>
</head>
<body>
<div class="shell">
    <?php include '../includes/sidebar.php'; ?>

    <main class="main">
        <div class="page-header">
            <h2>Good <?= date('H') < 12 ? 'morning' : (date('H') < 18 ? 'afternoon' : 'evening') ?>, <?= htmlspecialchars(explode(' ', $name)[0]) ?> <?= $role === 'admin' ? '<span style="font-size:16px;color:var(--accent);">(Admin)</span>' : '' ?></h2>
            <p><?= date('l, d F Y') ?></p>
        </div>

        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-card blue">
                <div class="stat-label">Total Tasks</div>
                <div class="stat-value"><?= $totalTasks ?></div>
            </div>
            <div class="stat-card yellow">
                <div class="stat-label">Active</div>
                <div class="stat-value"><?= $activeTasks ?></div>
            </div>
            <div class="stat-card green">
                <div class="stat-label">Completed</div>
                <div class="stat-value"><?= $completedTasks ?></div>
            </div>
            <div class="stat-card purple">
                <div class="stat-label">Notes</div>
                <div class="stat-value"><?= $totalNotes ?></div>
            </div>
        </div>

        <!-- Notes Preview -->
        <div class="section-heading">
            Notes
            <a href="notes.php" class="btn btn-ghost btn-sm">View all →</a>
        </div>

        <?php if (empty($notes)): ?>
            <div class="empty-state" style="margin-bottom:28px;">
                <h3>No notes yet</h3>
                <p>Head over to <a href="notes.php" style="color:var(--accent)">Notes</a> to jot something down.</p>
            </div>
        <?php else: ?>
            <div class="notes-grid">
                <?php foreach ($notes as $note):
                    $colors = ['yellow','blue','green','pink'];
                    $c = in_array($note['color'], $colors) ? $note['color'] : 'yellow';
                ?>
                    <div class="note-card <?= $c ?>">
                        <h4><?= htmlspecialchars($note['title']) ?></h4>
                        <p><?= nl2br(htmlspecialchars(mb_substr($note['content'] ?? '', 0, 150))) ?><?= strlen($note['content'] ?? '') > 150 ? '…' : '' ?></p>
                        <div class="note-meta">
                            <?php if ($role === 'admin' && isset($note['user_name'])): ?>
                                <span class="note-owner"><?= htmlspecialchars($note['user_name']) ?></span>
                            <?php else: ?>
                                <span><?= date('d M', strtotime($note['created_at'])) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Active Tasks -->
        <div class="section-heading">
            Active Tasks
            <a href="tasks.php" class="btn btn-ghost btn-sm">Manage →</a>
        </div>

        <?php if (empty($tasks)): ?>
            <div class="empty-state">
                <h3>No active tasks</h3>
                <p>Great job! Or <a href="tasks.php" style="color:var(--accent)">create a new task</a>.</p>
            </div>
        <?php else: ?>
            <div class="task-list">
                <?php foreach ($tasks as $task):
                    $pClass = 'priority-' . strtolower($task['priority']);
                    $sClass = 'status-' . strtolower(str_replace(' ', '-', $task['status']));
                ?>
                    <div class="task-card">
                        <div class="task-header">
                            <span style="font-size:15px;font-weight:600;flex:1;"><?= htmlspecialchars($task['title']) ?></span>
                            <?php if ($role === 'admin' && isset($task['user_name'])): ?>
                                <span class="task-owner"><?= htmlspecialchars($task['user_name']) ?></span>
                            <?php endif; ?>
                            <span class="badge <?= $pClass ?>"><?= htmlspecialchars($task['priority']) ?></span>
                            <span class="badge <?= $sClass ?>"><?= htmlspecialchars($task['status']) ?></span>
                        </div>

                        <?php if (!empty($task['description'])): ?>
                            <p style="color:var(--muted);font-size:13px;margin:6px 0 10px;"><?= nl2br(htmlspecialchars(mb_substr($task['description'], 0, 120))) ?><?= strlen($task['description']) > 120 ? '…' : '' ?></p>
                        <?php endif; ?>

                        <div class="progress-bar">
                            <div class="progress-fill" data-progress="<?= $task['progress'] ?>"></div>
                        </div>

                        <div style="display:flex;justify-content:space-between;margin-top:6px;">
                            <small style="color:var(--muted);font-size:11px;"><?= $task['progress'] ?>% complete</small>
                            <?php if (!empty($task['due_date'])): ?>
                                <small style="color:var(--muted);font-size:11px;">Due <?= date('d M Y', strtotime($task['due_date'])) ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
