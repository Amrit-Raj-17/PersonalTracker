<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/phpmailer/PHPMailer.php';
require __DIR__ . '/../includes/phpmailer/SMTP.php';
require __DIR__ . '/../includes/phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 🔐 Simple security
if (!isset($_GET['key']) || $_GET['key'] !== $_ENV['CRON_SECRET']) {
    http_response_code(403);
    exit("Unauthorized");
}


$stmt = $pdo->query("
    SELECT u.id, u.name, u.email,
    COUNT(t.id) AS pending_tasks
    FROM users u
    JOIN tasks t
        ON u.id = t.user_id

    WHERE t.completed = false
    AND (
        t.due_date = CURRENT_DATE
        OR t.due_date < CURRENT_DATE
        OR t.progress < 100
    )
    GROUP BY u.id, u.name, u.email
");

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);


foreach ($users as $user) {

    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->setFrom($_ENV['MAIL_USER'], 'Work Tracker');
        $mail->addAddress($user['email'], $user['name']);

        $mail->isHTML(true);
        $mail->Subject = '📌 Daily Reminder';


        $mail->Body = "
            <h3>Hello {$user['name']},</h3>
            <p>You have <b>{$user['pending_tasks']}</b> pending tasks.</p>
            <p>Some tasks are due today or overdue.</p>
            <br>
            <a href='https://personaltracker-8wwb.onrender.com/'>Open Tracker</a>
        ";

        $mail->send();

    } catch (Exception $e) {
        error_log("Mail failed: " . $mail->ErrorInfo);
    }
}

echo "Done";