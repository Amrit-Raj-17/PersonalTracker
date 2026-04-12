<?php
session_start();
require '../includes/db.php';
require '../includes/logVisit.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

logVisit('notes');

$userId = $_SESSION['user_id'];
$role   = $_SESSION['role'];
$name   = $_SESSION['name'];

// ── Add note ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_note'])) {
    $targetUserId = ($role === 'admin' && !empty($_POST['target_user_id']))
        ? (int)$_POST['target_user_id']
        : $userId;

    $title   = trim($_POST['title']);
    $content = trim($_POST['content']);
    $color   = in_array($_POST['color'], ['yellow','blue','green','pink'])
        ? $_POST['color'] : 'yellow';
    $pinned  = isset($_POST['pinned']) ? 1 : 0;

    if (!empty($title)) {
        $stmt = $pdo->prepare("
            INSERT INTO notes (user_id, title, content, color, pinned)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$targetUserId, $title, $content, $color, $pinned]);
    }

    header("Location: notes.php");
    exit;
}

// ── Delete note ────────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $noteId = (int)$_GET['delete'];
    if ($role === 'admin') {
        $stmt = $pdo->prepare("DELETE FROM notes WHERE id = ?");
        $stmt->execute([$noteId]);
    } else {
        $stmt = $pdo->prepare("DELETE FROM notes WHERE id = ? AND user_id = ?");
        $stmt->execute([$noteId, $userId]);
    }
    header("Location: notes.php");
    exit;
}

// ── Toggle pin ─────────────────────────────────────────────────────
if (isset($_GET['pin'])) {
    $noteId = (int)$_GET['pin'];
    if ($role === 'admin') {
        $stmt = $pdo->prepare("UPDATE notes SET pinned = NOT pinned WHERE id = ?");
        $stmt->execute([$noteId]);
    } else {
        $stmt = $pdo->prepare("UPDATE notes SET pinned = NOT pinned WHERE id = ? AND user_id = ?");
        $stmt->execute([$noteId, $userId]);
    }
    header("Location: notes.php");
    exit;
}

// ── Fetch users for admin dropdown ────────────────────────────────
$allUsers = [];
if ($role === 'admin') {
    $allUsers = $pdo->query("SELECT id, name FROM users ORDER BY name")
                    ->fetchAll(PDO::FETCH_ASSOC);
}

// ── Fetch notes ────────────────────────────────────────────────────
if ($role === 'admin') {
    $noteStmt = $pdo->query("
        SELECT notes.*, users.name AS user_name
        FROM notes
        JOIN users ON notes.user_id = users.id
        ORDER BY notes.pinned DESC, notes.created_at DESC
    ");
} else {
    $noteStmt = $pdo->prepare("
        SELECT * FROM notes
        WHERE user_id = ?
        ORDER BY pinned DESC, created_at DESC
    ");
    $noteStmt->execute([$userId]);
}
$notes = $noteStmt->fetchAll(PDO::FETCH_ASSOC);

$activePage = 'notes';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notes — Tracker</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="shell">
    <?php include '../includes/sidebar.php'; ?>

    <main class="main">
        <div class="page-header">
            <h2>Notes</h2>
            <p>Capture ideas, reminders, anything.</p>
        </div>

        <!-- Add Note Form -->
        <div class="form-card">
            <h3>New Note</h3>
            <form method="POST">
                <input type="hidden" name="add_note" value="1">

                <?php if ($role === 'admin'): ?>
                    <div class="form-group" style="margin-bottom:14px;">
                        <label>Add on behalf of</label>
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
                    <input type="text" name="title" placeholder="Note title" required>
                </div>

                <div class="form-group" style="margin-bottom:14px;">
                    <label>Content</label>
                    <textarea name="content" placeholder="Write your note…" style="min-height:110px;"></textarea>
                </div>

                <div class="form-grid" style="gap:12px;margin-bottom:16px;">
                    <div class="form-group">
                        <label>Color</label>
                        <select name="color">
                            <option value="yellow">🟡 Yellow</option>
                            <option value="blue">🔵 Blue</option>
                            <option value="green">🟢 Green</option>
                            <option value="pink">🩷 Pink</option>
                        </select>
                    </div>
                    <div class="form-group" style="justify-content:flex-end;">
                        <label>&nbsp;</label>
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:10px 0;">
                            <input type="checkbox" name="pinned" style="width:auto;margin:0;">
                            <span style="font-size:13px;color:var(--muted);">Pin this note</span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Add Note</button>
            </form>
        </div>

        <!-- Notes Grid -->
        <?php if (empty($notes)): ?>
            <div class="empty-state">
                <h3>No notes yet</h3>
                <p>Use the form above to add your first note.</p>
            </div>
        <?php else: ?>
            <div class="notes-grid">
                <?php foreach ($notes as $note):
                    $colors = ['yellow','blue','green','pink'];
                    $c = in_array($note['color'], $colors) ? $note['color'] : 'yellow';
                ?>
                    <div class="note-card <?= $c ?>">
                        <?php if ($note['pinned']): ?>
                            <div style="font-size:11px;color:var(--warn);margin-bottom:6px;font-weight:600;">📌 PINNED</div>
                        <?php endif; ?>

                        <h4><?= htmlspecialchars($note['title']) ?></h4>

                        <?php if (!empty($note['content'])): ?>
                            <p><?= nl2br(htmlspecialchars($note['content'])) ?></p>
                        <?php endif; ?>

                        <div class="note-meta">
                            <div>
                                <?php if ($role === 'admin' && isset($note['user_name'])): ?>
                                    <span class="note-owner"><?= htmlspecialchars($note['user_name']) ?></span>
                                <?php else: ?>
                                    <span><?= date('d M Y', strtotime($note['created_at'])) ?></span>
                                <?php endif; ?>
                            </div>
                            <div style="display:flex;gap:8px;align-items:center;">
                                <a href="notes.php?pin=<?= $note['id'] ?>"
                                   title="<?= $note['pinned'] ? 'Unpin' : 'Pin' ?>"
                                   style="color:var(--muted);font-size:14px;text-decoration:none;">
                                    <?= $note['pinned'] ? '📌' : '📍' ?>
                                </a>
                                <button class="note-delete btn-delete"
                                        onclick="if(confirm('Delete this note?'))window.location='notes.php?delete=<?= $note['id'] ?>'">
                                    ✕
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
