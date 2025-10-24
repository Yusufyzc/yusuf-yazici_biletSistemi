<?php
require_once 'config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['mail'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $message = "Lütfen e-posta ve şifre giriniz.";
    } else {
        $statement = $db->prepare("SELECT * FROM User WHERE email = :email LIMIT 1");
        $statement->execute([':email' => $email]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['full_name'];
            header("Location: index.php");
            exit;
        } else {
            $message = "E-posta veya şifre hatalı.";
            header("Refresh: 1.5; url=log-reg.php");
            echo $message;
            exit;
        }
    }
}
?>