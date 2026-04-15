<?php
require __DIR__ . '/../includes/db.php';

// ✅ Manual autoloader for SendGrid
spl_autoload_register(function ($class) {

    // Convert namespace to file path
    $prefix = 'SendGrid\\';
    $base_dir = __DIR__ . '/../sendgrid/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);

    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

use SendGrid\Mail\Mail;

$email = new Mail();
$email->setFrom("thakur.amritraj38@gmail.com@gmail.com", "Test");
$email->setSubject("Manual Test");
$email->addTo("amritrajt15@gmail.com", "You");
$email->addContent("text/html", "<h1>It works!</h1>");

$sendgrid = new \SendGrid(getenv('SENDGRID_API_KEY'));

$response = $sendgrid->send($email);

echo $response->statusCode();
/*

// 🔐 Cron protection
if (!isset($_GET['key']) || $_GET['key'] !== getenv('CRON_SECRET')) {
    http_response_code(403);
    exit("Unauthorized");
}

// ✅ Get all users
$stmt = $pdo->query("SELECT id, name, email FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Loop users
foreach ($users as $user) {

    // 🔥 Get task stats (FAST)
    $taskStmt = $pdo->prepare("
        SELECT 
            COUNT(*) AS pending_tasks,
            COUNT(CASE WHEN due_date < CURRENT_DATE THEN 1 END) AS overdue_tasks,
            COUNT(CASE WHEN due_date = CURRENT_DATE THEN 1 END) AS due_today_tasks
        FROM tasks
        WHERE user_id = ? AND completed = false
    ");

    $taskStmt->execute([$user['id']]);
    $task = $taskStmt->fetch(PDO::FETCH_ASSOC);

    $pending = $task['pending_tasks'] ?? 0;
    $overdue = $task['overdue_tasks'] ?? 0;
    $dueToday = $task['due_today_tasks'] ?? 0;

    // ⛔ Skip users with no tasks
    if ($pending == 0) continue;

    // 🔥 Create email
    $email = new Mail();
    $email->setFrom(getenv('MAIL_USER'), "Work Tracker");
    $email->addTo($user['email'], $user['name']);

    // ✅ Subject
    if ($overdue > 0) {
        $subject = "⚠️ {$overdue} Overdue | {$dueToday} Due Today";
    } else {
        $subject = "📅 {$dueToday} Tasks Due Today";
    }

    $email->setSubject($subject);

    // ✅ Email Body
    $body = "
        <h3>Hello {$user['name']},</h3>
        <p>You have <b>{$pending}</b> pending tasks.</p>
    ";

    if ($overdue > 0) {
        $body .= "<p style='color:red;'><b>⚠️ {$overdue} overdue tasks!</b></p>";
    }

    if ($dueToday > 0) {
        $body .= "<p style='color:orange;'><b>📅 {$dueToday} due today.</b></p>";
    }

    $body .= "
        <p>Stay consistent and finish your work 💪</p>
        <br>
        <a href='https://personaltracker-8wwb.onrender.com/' 
           style='padding:10px 15px; background:#007bff; color:white; text-decoration:none; border-radius:5px;'>
           Open Tracker
        </a>
    ";

    $email->addContent("text/html", $body);

    // ✅ Send using SendGrid API
    $sendgrid = new \SendGrid(getenv('SENDGRID_API_KEY'));

    try {
        $response = $sendgrid->send($email);

        if ($response->statusCode() >= 200 && $response->statusCode() < 300) {
            echo "✅ Sent to {$user['email']}<br>";
        } else {
            echo "❌ Failed ({$response->statusCode()}) for {$user['email']}<br>";
        }

    } catch (Exception $e) {
        echo "❌ Error sending to {$user['email']}: " . $e->getMessage() . "<br>";
    }

    // small delay
    sleep(1);
}

echo "<br>🎉 All reminders processed.";