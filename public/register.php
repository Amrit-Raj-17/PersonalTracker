<?php
require '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users(name, email, password) VALUES (?, ?, ?)");
    $stmt->execute([$name, $email, $password]);

    header('Location: login.php');
    exit;
}
?>