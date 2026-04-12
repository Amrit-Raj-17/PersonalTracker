<?php
session_start();
require '../includes/db.php';
require '../includes/logVisit.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

logVisit('visits');

if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

$userId = $_SESSION['user_id'];
$role   = $_SESSION['role'];
$name   = $_SESSION['name'];

$stmt = $pdo->query("
    SELECT
        visits.id,
        visits.page_name,
        visits.ip_address,
        visits.user_agent,
        visits.visited_at,
        users.name  AS user_name,
        users.email AS user_email,
        users.role  AS user_role
    FROM visits
    LEFT JOIN users ON visits.user_id = users.id
    ORDER BY visits.visited_at DESC
    LIMIT 100
");
$visits = $stmt->fetchAll(PDO::FETCH_ASSOC);

$activePage = 'visits';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visits — Admin</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="shell">
    <?php include '../includes/sidebar.php'; ?>

    <main class="main">
        <div class="page-header">
            <h2>Visit Log</h2>
            <p>Last 100 page visits across the app.</p>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Page</th>
                        <th>Visited At</th>
                        <th>IP</th>
                        <th>User Agent</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($visits)): ?>
                        <tr>
                            <td colspan="8" style="text-align:center;padding:32px;color:var(--muted);">
                                No visits recorded yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($visits as $v): ?>
                            <tr>
                                <td style="color:var(--muted);font-size:12px;"><?= $v['id'] ?></td>
                                <td style="font-weight:500;">
                                    <?= $v['user_name']
                                        ? htmlspecialchars($v['user_name'])
                                        : '<span class="muted-text">Guest</span>' ?>
                                </td>
                                <td style="color:var(--muted);font-size:12px;">
                                    <?= $v['user_email']
                                        ? htmlspecialchars($v['user_email'])
                                        : '<span class="muted-text">—</span>' ?>
                                </td>
                                <td>
                                    <?php if ($v['user_role'] === 'admin'): ?>
                                        <span class="role-admin">Admin</span>
                                    <?php elseif ($v['user_role'] === 'user'): ?>
                                        <span class="role-user">User</span>
                                    <?php else: ?>
                                        <span class="muted-text">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="page-badge"><?= htmlspecialchars($v['page_name']) ?></span></td>
                                <td style="font-size:12px;color:var(--muted);">
                                    <?= date('d M Y, h:i:s A', strtotime($v['visited_at'])) ?>
                                </td>
                                <td style="font-size:12px;color:var(--muted);"><?= htmlspecialchars($v['ip_address']) ?></td>
                                <td class="agent"><?= htmlspecialchars($v['user_agent']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>
</html>
