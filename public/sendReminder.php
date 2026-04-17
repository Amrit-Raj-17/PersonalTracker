<?php
require __DIR__ . '/../includes/db.php';

// 🔐 Cron protection
if (!isset($_GET['key']) || $_GET['key'] !== getenv('CRON_SECRET')) {
    http_response_code(403);
    exit("Unauthorized");
}

// 📅 Get all users
$stmt = $pdo->query("SELECT id, name, email FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$apiKey = getenv('BREVO_API_KEY');

foreach ($users as $user) {

    // 📊 Task stats
    $taskStmt = $pdo->prepare("
        SELECT 
            COUNT(*) AS pending_tasks,
            COUNT(CASE 
                WHEN due_date < (CURRENT_TIMESTAMP AT TIME ZONE 'Asia/Kolkata')::date 
                THEN 1 
            END) AS overdue_tasks,
            COUNT(CASE 
                WHEN due_date = (CURRENT_TIMESTAMP AT TIME ZONE 'Asia/Kolkata')::date 
                THEN 1 
            END) AS due_today_tasks
        FROM tasks
        WHERE user_id = ? AND completed = false
    ");

    $taskStmt->execute([$user['id']]);
    $task = $taskStmt->fetch(PDO::FETCH_ASSOC);

    $pending = $task['pending_tasks'] ?? 0;
    $overdue = $task['overdue_tasks'] ?? 0;
    $dueToday = $task['due_today_tasks'] ?? 0;

    // ⛔ Skip if no tasks
    if ($pending == 0) continue;

    // 📋 Fetch task names (overdue + due today)
    $listStmt = $pdo->prepare("
        SELECT title, due_date
        FROM tasks
        WHERE user_id = ?
          AND completed = false
          AND due_date <= (CURRENT_TIMESTAMP AT TIME ZONE 'Asia/Kolkata')::date
        ORDER BY due_date ASC
    ");

    $listStmt->execute([$user['id']]);
    $tasksList = $listStmt->fetchAll(PDO::FETCH_ASSOC);

    // 📩 Subject
    if ($overdue > 0) {
        $subject = "⚠️ {$overdue} Overdue | {$dueToday} Due Today";
    } else {
        $subject = "📅 {$dueToday} Tasks Due Today";
    }

    // 🧾 Email body
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

    // 📋 Task list
    if (!empty($tasksList)) {
        $body .= "<h4>📝 Tasks:</h4><ul>";

        $today = date('Y-m-d');

        foreach ($tasksList as $t) {
            $due = $t['due_date'];

            if ($due < $today) {
                $color = "red";
                $label = "Overdue";
            } elseif ($due == $today) {
                $color = "orange";
                $label = "Today";
            } else {
                $color = "black";
                $label = "";
            }

            $body .= "<li style='color:$color;'>
                        {$t['title']} 
                        <small>($label - {$due})</small>
                      </li>";
        }

        $body .= "</ul>";
    }

    $body .= "
        <p>Stay consistent 💪</p>
        <br>
        <a href='https://personaltracker-8wwb.onrender.com/'>Open Tracker</a>
    ";

    // 🚀 BREVO API CALL
    $data = [
        "sender" => [
            "name" => "Work Tracker",
            "email" => "amritrajt15@gmail.com" // must be verified in Brevo
        ],
        "to" => [
            [
                "email" => $user['email'],
                "name" => $user['name']
            ]
        ],
        "subject" => $subject,
        "htmlContent" => $body
    ];

    $ch = curl_init("https://api.brevo.com/v3/smtp/email");

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "api-key: $apiKey",
        "Content-Type: application/json"
    ]);

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        echo "❌ Curl error for {$user['email']}: " . curl_error($ch) . "<br>";
    } else {
        if ($httpCode == 201) {
            echo "✅ Sent to {$user['email']}<br>";
        } else {
            echo "❌ Failed for {$user['email']} | Response: $response<br>";
        }
    }

    curl_close($ch);

    sleep(1); // avoid rate limit
}

echo "🎉 All reminders processed.";