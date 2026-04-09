<?php
require '../includes/auth.php';
require '../includes/db.php';

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $priority = $_POST['priority'];
    $progress = (int) $_POST['progress'];

    $completed = $progress >= 100;

    $stmt = $pdo->prepare(
        "INSERT INTO tasks(user_id, title, description, category, priority, progress, completed, completed_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt->execute([
        $userId,
        $title,
        $description,
        $category,
</html>