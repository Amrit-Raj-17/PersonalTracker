<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/phpmailer/PHPMailer.php';
require __DIR__ . '/../includes/phpmailer/SMTP.php';
require __DIR__ . '/../includes/phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 🔐 Cron protection
if (!isset($_GET['key']) || $_GET['key'] !== $_ENV['CRON_SECRET']) {
    http_response_code(403);
    exit("Unauthorized");
}

// ✅ Get all users
$stmt = $pdo->query("SELECT id, name, email FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Loop users (FAST APPROACH)
foreach ($users as $user) {

    // 🔥 Per-user optimized query (very fast)
    $taskStmt = $pdo->prepare("
        SELECT 
            COUNT(*) AS pending_tasks,

            COUNT(CASE WHEN due_date < CURRENT_DATE THEN 1 END) AS overdue_tasks,

            COUNT(CASE WHEN due_date = CURRENT_DATE THEN 1 END) AS due_today_tasks

        FROM tasks
        WHERE user_id = ? AND completed = false
    ");

    $taskStmt->execute([$user['id']]);
    $taskData = $taskStmt->fetch(PDO::FETCH_ASSOC);

    $pending = $taskData['pending_tasks'] ?? 0;
    $overdue = $taskData['overdue_tasks'] ?? 0;
    $dueToday = $taskData['due_today_tasks'] ?? 0;

    // ⛔ Skip users with no tasks
    if ($pending == 0) continue;

    $mail = new PHPMailer(true);

    try {
        // ⚡ SMTP Config (with timeout)
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['MAIL_USER'];
        $mail->Password = $_ENV['MAIL_PASS'];
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->Timeout = 10;

        // 🔥 Dynamic subject
        if ($overdue > 0) {
            $mail->Subject = "⚠️ {$overdue} Overdue | {$dueToday} Due Today";
        } else {
            $mail->Subject = "📅 {$dueToday} Tasks Due Today";
        }

        // 🔥 Messages
        $overdueMsg = "";
        if ($overdue > 0) {
            $overdueMsg = "<p style='color:red;'><b>⚠️ {$overdue} task(s) are overdue!</b></p>";
        }

        $dueTodayMsg = "";
        if ($dueToday > 0) {
            $dueTodayMsg = "<p style='color:orange;'><b>📅 {$dueToday} task(s) are due today.</b></p>";
        }

        // ✅ Email Setup
        $mail->setFrom($_ENV['MAIL_USER'], 'Work Tracker');
        $mail->addAddress($user['email'], $user['name']);
        $mail->isHTML(true);

        // 🔥 Email Body
        $mail->Body = "
            <h3>Hello {$user['name']},</h3>

            <p>You have <b>{$pending}</b> pending tasks.</p>

            {$overdueMsg}
            {$dueTodayMsg}

            <p>Stay consistent and finish your work 💪</p>

            <br>
            <a href='https://personaltracker-8wwb.onrender.com/' 
               style='padding:10px 15px; background:#007bff; color:white; text-decoration:none; border-radius:5px;'>
               Open Tracker
            </a>
        ";

        // ✅ Send
        $mail->send();

        // ⚡ small delay to avoid server stress
        sleep(1);

    } catch (Exception $e) {
        error_log("Mail failed for {$user['email']}: " . $mail->ErrorInfo);
    }
}

echo "✅ Reminder emails sent successfully.";