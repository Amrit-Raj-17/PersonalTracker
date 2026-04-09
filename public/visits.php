<?php
require '../includes/auth.php';
require '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$stmt = $pdo->prepare("
    SELECT
        visits.id,
        visits.page_name,
        visits.ip_address,
        visits.user_agent,
        visits.visited_at,
        users.name AS user_name,
        users.email AS user_email,
        users.role AS user_role
    FROM visits
    LEFT JOIN users
        ON visits.user_id = users.id
    ORDER BY visits.visited_at DESC
    LIMIT 100
");

$stmt->execute();
$visits = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visits Dashboard</title>
    <link rel="stylesheet" href="css/style.css">

    <style>
        body {
            background: #f5f7fb;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 30px;
        }

        .container {
            max-width: 1400px;
            margin: auto;
        }

        h1 {
            margin-bottom: 25px;
            color: #1f2937;
        }

        .table-wrapper {
            background: white;
            border-radius: 14px;
            overflow-x: auto;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        thead {
            background: #2563eb;
            color: white;
        }

        th, td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }

        tr:hover {
            background: #f9fafb;
        }

        .page-badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 600;
        }

        .role-admin {
            color: #dc2626;
            font-weight: bold;
        }

        .role-user {
            color: #059669;
            font-weight: bold;
        }

        .muted {
            color: #6b7280;
            font-style: italic;
        }

        .agent {
            max-width: 350px;
            word-break: break-word;
            font-size: 12px;
            color: #4b5563;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Latest 100 Visits</h1>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>User</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Page</th>
                    <th>Visited At</th>
                    <th>IP Address</th>
                    <th>User Agent</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($visits) > 0): ?>
                    <?php foreach ($visits as $visit): ?>
                        <tr>
                            <td><?= htmlspecialchars($visit['id']) ?></td>

                            <td>
                                <?= $visit['user_name']
                                    ? htmlspecialchars($visit['user_name'])
                                    : '<span class="muted">Guest</span>' ?>
                            </td>

                            <td>
                                <?= $visit['user_email']
                                    ? htmlspecialchars($visit['user_email'])
                                    : '<span class="muted">-</span>' ?>
                            </td>

                            <td>
                                <?php if ($visit['user_role'] === 'admin'): ?>
                                    <span class="role-admin">Admin</span>
                                <?php elseif ($visit['user_role'] === 'user'): ?>
                                    <span class="role-user">User</span>
                                <?php else: ?>
                                    <span class="muted">-</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="page-badge">
                                    <?= htmlspecialchars($visit['page_name']) ?>
                                </span>
                            </td>

                            <td>
                                <?= date(
                                    "d M Y, h:i:s A",
                                    strtotime($visit['visited_at'])
                                ) ?>
                            </td>

                            <td><?= htmlspecialchars($visit['ip_address']) ?></td>

                            <td class="agent">
                                <?= htmlspecialchars($visit['user_agent']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align:center; padding:30px;">
                            No visits found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>