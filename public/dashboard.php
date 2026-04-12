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
$today  = date('Y-m-d');

// Admin: Add todo item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_todo']) && $role === 'admin') {
    $todoTitle = trim($_POST['todo_title']);
    if (!empty($todoTitle)) {
        $stmt = $pdo->prepare("INSERT INTO todos (title, created_by) VALUES (?, ?)");
        $stmt->execute([$todoTitle, $userId]);
    }
    header("Location: dashboard.php");
    exit;
}

// Admin: Delete todo item
if (isset($_GET['delete_todo']) && $role === 'admin') {
    $stmt = $pdo->prepare("DELETE FROM todos WHERE id = ?");
    $stmt->execute([(int)$_GET['delete_todo']]);
    header("Location: dashboard.php");
    exit;
}

// Toggle todo completion (any user, today only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_todo'])) {
    $todoId = (int)$_POST['todo_id'];
    $check = $pdo->prepare("SELECT id FROM todo_completions WHERE todo_id=? AND user_id=? AND date=?");
    $check->execute([$todoId, $userId, $today]);
    if ($check->fetch()) {
        $pdo->prepare("DELETE FROM todo_completions WHERE todo_id=? AND user_id=? AND date=?")
            ->execute([$todoId, $userId, $today]);
    } else {
        $pdo->prepare("INSERT INTO todo_completions (todo_id, user_id, date) VALUES (?,?,?)
                       ON CONFLICT (todo_id, user_id, date) DO NOTHING")
            ->execute([$todoId, $userId, $today]);
    }
    header("Location: dashboard.php");
    exit;
}

// Admin: view completions for a chosen date
$viewDate = $today;
if ($role === 'admin' && isset($_GET['view_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['view_date'])) {
    $viewDate = $_GET['view_date'];
}

// Fetch todos with MY completion status for today
$todosStmt = $pdo->prepare("
    SELECT todos.*,
           CASE WHEN tc.id IS NOT NULL THEN true ELSE false END AS done_by_me
    FROM todos
    LEFT JOIN todo_completions tc
        ON tc.todo_id = todos.id AND tc.user_id = ? AND tc.date = ?
    ORDER BY todos.created_at ASC
");
$todosStmt->execute([$userId, $today]);
$todos = $todosStmt->fetchAll(PDO::FETCH_ASSOC);

// Admin: fetch all users and completion grid for viewDate
$completionGrid = [];
$allUsers = [];
if ($role === 'admin') {
    $allUsers = $pdo->query("SELECT id, name FROM users ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    $compStmt = $pdo->prepare("SELECT todo_id, user_id FROM todo_completions WHERE date = ?");
    $compStmt->execute([$viewDate]);
    foreach ($compStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $completionGrid[$row['todo_id']][$row['user_id']] = true;
    }
}

// Stats
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

// Notes
if ($role === 'admin') {
    $noteStmt = $pdo->query("
        SELECT notes.*, users.name AS user_name FROM notes
        JOIN users ON notes.user_id = users.id
        ORDER BY notes.pinned DESC, notes.created_at DESC LIMIT 6
    ");
} else {
    $noteStmt = $pdo->prepare("SELECT * FROM notes WHERE user_id = ? ORDER BY pinned DESC, created_at DESC LIMIT 6");
    $noteStmt->execute([$userId]);
}
$notes = $noteStmt->fetchAll(PDO::FETCH_ASSOC);

// Active Tasks
if ($role === 'admin') {
    $taskStmt = $pdo->query("
        SELECT tasks.*, users.name AS user_name FROM tasks
        JOIN users ON tasks.user_id = users.id
        WHERE tasks.completed = false ORDER BY tasks.created_at DESC
    ");
} else {
    $taskStmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id = ? AND completed = false ORDER BY created_at DESC");
    $taskStmt->execute([$userId]);
}
$tasks = $taskStmt->fetchAll(PDO::FETCH_ASSOC);

$activePage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Tracker</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/app.js" defer></script>
    <style>
        .todo-section {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--card-r);
            padding: 22px 24px;
            margin-bottom: 28px;
        }
        .todo-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .todo-header h3 {
            font-family: 'DM Serif Display', serif;
            font-size: 18px;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .todo-date-badge {
            font-size: 12px;
            font-family: 'DM Sans', sans-serif;
            background: var(--surface2);
            color: var(--muted);
            padding: 4px 10px;
            border-radius: 999px;
            border: 1px solid var(--border);
            font-weight: 400;
        }
        .todo-items { display: flex; flex-direction: column; gap: 8px; }
        .todo-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 14px;
            border-radius: 10px;
            background: var(--surface2);
            border: 1px solid var(--border);
            transition: border-color 0.2s, background 0.2s;
        }
        .todo-item.done { background: #0d1f10; border-color: #1a4025; }
        .todo-item.done .todo-text { text-decoration: line-through; color: var(--muted); }
        .todo-checkbox { width: 18px; height: 18px; accent-color: var(--success); cursor: pointer; flex-shrink: 0; }
        .todo-text { flex: 1; font-size: 14px; color: var(--text); }
        .todo-delete {
            background: none; border: none; color: var(--muted); cursor: pointer;
            font-size: 15px; padding: 0; width: auto; margin: 0;
            transition: color 0.2s; line-height: 1;
        }
        .todo-delete:hover { color: var(--danger); background: none; }
        .todo-add-form {
            display: flex; gap: 8px; margin-top: 16px;
            padding-top: 14px; border-top: 1px solid var(--border);
        }
        .todo-add-form input { flex: 1; margin: 0; padding: 9px 13px; font-size: 13px; }
        .todo-add-form button { width: auto; margin: 0; padding: 9px 16px; white-space: nowrap; }
        .todo-empty { text-align: center; color: var(--muted); font-size: 13px; padding: 16px 0 8px; }
        .completion-grid { margin-top: 20px; padding-top: 18px; border-top: 1px solid var(--border); }
        .completion-grid h4 {
            font-size: 13px; color: var(--muted); text-transform: uppercase;
            letter-spacing: 0.5px; margin-bottom: 14px;
            display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
        }
        .completion-date-form { display: inline-flex; align-items: center; gap: 6px; }
        .completion-date-form input[type="date"] {
            padding: 4px 10px; font-size: 12px; background: var(--surface2);
            border: 1px solid var(--border); border-radius: 6px;
            color: var(--text); margin: 0;
        }
        .completion-date-form button { padding: 4px 10px; font-size: 12px; width: auto; margin: 0; border-radius: 6px; }
        .comp-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .comp-table th {
            padding: 8px 12px; text-align: left; font-size: 11px; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.4px;
            color: var(--muted); border-bottom: 1px solid var(--border);
        }
        .comp-table td { padding: 9px 12px; border-bottom: 1px solid var(--border); }
        .comp-table tr:last-child td { border-bottom: none; }
        .comp-tick { color: var(--success); font-size: 15px; }
        .comp-cross { color: var(--border); font-size: 15px; }
    </style>
</head>
<body>
<div class="shell">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="main">
        <div class="page-header">
            <h2>Good <?= date('H') < 12 ? 'morning' : (date('H') < 18 ? 'afternoon' : 'evening') ?>,
                <?= htmlspecialchars(explode(' ', $name)[0]) ?>
                <?php if ($role === 'admin'): ?>
                    <span style="font-size:16px;color:var(--accent);font-family:'DM Sans',sans-serif;font-weight:400;">(Admin)</span>
                <?php endif; ?>
            </h2>
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

        <!-- Daily Todo / Checklist -->
        <div class="todo-section">
            <div class="todo-header">
                <h3>
                    Daily Checklist
                    <span class="todo-date-badge"><?= date('d M Y') ?> · resets daily</span>
                </h3>
            </div>

            <?php if (empty($todos)): ?>
                <div class="todo-empty">
                    <?= $role === 'admin'
                        ? 'No checklist items yet. Add one below.'
                        : 'No checklist items have been added by admin yet.' ?>
                </div>
            <?php else: ?>
                <div class="todo-items">
                    <?php foreach ($todos as $todo): ?>
                        <div class="todo-item <?= $todo['done_by_me'] ? 'done' : '' ?>">
                            <form method="POST" style="display:contents;">
                                <input type="hidden" name="toggle_todo" value="1">
                                <input type="hidden" name="todo_id" value="<?= $todo['id'] ?>">
                                <input
                                    type="checkbox"
                                    class="todo-checkbox"
                                    <?= $todo['done_by_me'] ? 'checked' : '' ?>
                                    onchange="this.form.submit()"
                                >
                            </form>
                            <span class="todo-text"><?= htmlspecialchars($todo['title']) ?></span>
                            <?php if ($role === 'admin'): ?>
                                <button
                                    class="todo-delete"
                                    onclick="if(confirm('Remove this checklist item?'))window.location='dashboard.php?delete_todo=<?= $todo['id'] ?>'">
                                    ✕
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- User: today's progress bar -->
            <?php if ($role !== 'admin' && !empty($todos)):
                $doneCount  = count(array_filter($todos, fn($t) => $t['done_by_me']));
                $totalCount = count($todos);
                $pct = $totalCount > 0 ? round($doneCount / $totalCount * 100) : 0;
            ?>
                <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--border);">
                    <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                        <small style="color:var(--muted);font-size:12px;">Today's progress</small>
                        <small style="color:var(--accent);font-size:12px;font-weight:600;"><?= $doneCount ?>/<?= $totalCount ?> done</small>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width:<?= $pct ?>%;background:<?= $pct === 100 ? 'var(--success)' : 'linear-gradient(90deg,var(--accent),var(--accent2))' ?>;"></div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Admin: add item form -->
            <?php if ($role === 'admin'): ?>
                <form method="POST" class="todo-add-form">
                    <input type="hidden" name="add_todo" value="1">
                    <input type="text" name="todo_title" placeholder="Add checklist item…" required>
                    <button type="submit" class="btn btn-primary btn-sm">+ Add</button>
                </form>
            <?php endif; ?>

            <!-- Admin: historical completion grid -->
            <?php if ($role === 'admin'): ?>
                <div class="completion-grid">
                    <h4>
                        Completion History
                        <form method="GET" class="completion-date-form">
                            <input
                                type="date"
                                name="view_date"
                                value="<?= htmlspecialchars($viewDate) ?>"
                                max="<?= $today ?>"
                            >
                            <button type="submit" class="btn btn-ghost btn-sm">View</button>
                        </form>
                        <span style="font-size:11px;color:var(--muted);font-weight:400;">
                            Showing: <?= date('d M Y', strtotime($viewDate)) ?>
                            <?= $viewDate === $today ? '(Today)' : '' ?>
                        </span>
                    </h4>

                    <?php if (empty($todos)): ?>
                        <p style="color:var(--muted);font-size:13px;">No items to show.</p>
                    <?php else: ?>
                        <div style="overflow-x:auto;">
                            <table class="comp-table">
                                <thead>
                                    <tr>
                                        <th>Checklist Item</th>
                                        <?php foreach ($allUsers as $u): ?>
                                            <th><?= htmlspecialchars($u['name']) ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($todos as $todo): ?>
                                        <tr>
                                            <td style="color:var(--text);font-weight:500;">
                                                <?= htmlspecialchars($todo['title']) ?>
                                            </td>
                                            <?php foreach ($allUsers as $u): ?>
                                                <td>
                                                    <?php if (!empty($completionGrid[$todo['id']][$u['id']])): ?>
                                                        <span class="comp-tick">✓</span>
                                                    <?php else: ?>
                                                        <span class="comp-cross">—</span>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
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
                            <p style="color:var(--muted);font-size:13px;margin:6px 0 10px;">
                                <?= nl2br(htmlspecialchars(mb_substr($task['description'], 0, 120))) ?><?= strlen($task['description']) > 120 ? '…' : '' ?>
                            </p>
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
