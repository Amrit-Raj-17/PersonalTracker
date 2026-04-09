<?php
require_once __DIR__ . '/db.php';

function logVisit($pageName)
{
    global $pdo;

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $userId = null;

    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
    }

    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $stmt = $pdo->prepare("
        INSERT INTO visits (
            user_id,
            page_name,
            ip_address,
            user_agent,
            visited_at
        )
        VALUES (?, ?, ?, ?, NOW())
    ");

    $stmt->execute([
        $userId,
        $pageName,
        $ipAddress,
        $userAgent
    ]);
}
?>