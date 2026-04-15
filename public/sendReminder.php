<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/phpmailer/PHPMailer.php';
require __DIR__ . '/../includes/phpmailer/SMTP.php';
require __DIR__ . '/../includes/phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 🔐 सुरक्षा (Cron protection)
if (!isset($_GET['key']) || $_GET['key'] !== $_ENV['CRON_SECRET']) {
    http_response_code(403);
    exit("Unauthorized");
}

// ✅ Fetch users with task stats
$stmt = $pdo->query("
    SELECT u.id, u.name, u.email,

           COUNT(t.id) AS pending_tasks,

           SUM(CASE 
                WHEN t.due_date < CURRENT_DATE THEN 1 
                ELSE 0 
           END) AS overdue_tasks,

           SUM(CASE 
                WHEN t.due_date = CURRENT_DATE THEN 1 
                ELSE 0 
           END) AS due_today_tasks

    FROM users u
    JOIN tasks t
        ON u.id = t.user_id

    WHERE t.completed = false

    GROUP BY u.id, u.name, u.email
");

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Loop through users
foreach ($users as $user) {

    $mail = new PHPMailer(true);

    try {
        // 🔥 SMTP Config
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['MAIL_USER'];
        $mail->Password = $_ENV['MAIL_PASS'];
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // ✅ Values
        $pending = $user['pending_tasks'] ?? 0;
        $overdue = $user['overdue_tasks'] ?? 0;
        $dueToday = $user['due_today_tasks'] ?? 0;

        // 🔥 Dynamic Subject
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

        // ✅ Send mail
        $mail->send();

    } catch (Exception $e) {
        error_log("Mail failed for {$user['email']}: " . $mail->ErrorInfo);
    }
}

echo "✅ Reminder emails sent successfully.";