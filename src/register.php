<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['name-surname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password1 = $_POST['password1'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if ($full_name === '' || $email === '' || $password1 === '' || $password2 === '') {
        $message = "Lütfen tüm alanları doldurun.";
    } elseif ($password1 !== $password2) {
        $message = "Parolalar eşleşmiyor.";
    } else {
        try {
            // emailCheck
            $check_stmt = $db->prepare("SELECT id FROM User WHERE email = :email LIMIT 1");
            $check_stmt->execute([':email' => $email]);
            $existing_user = $check_stmt->fetch(PDO::FETCH_ASSOC);

            //email is taken
            if ($existing_user) {
                header("Refresh: 2; url=log-reg.php");
                $message = "Bu e-posta adresi zaten kayıtlı.";
            } 
            //email is not taken
            else {
                // registration
                $hashed = password_hash($password1, PASSWORD_DEFAULT);
                $user_id = uniqid();

                $statement = $db->prepare("
                    INSERT INTO User (id, full_name, email, role, password, company_id, balance)
                    VALUES (:id, :full_name, :email, 'user', :password, NULL, 800)
                ");
                
                $statement->execute([
                    ':id' => $user_id,
                    ':full_name' => $full_name,
                    ':email' => $email,
                    ':password' => $hashed
                ]);
                
                // registration and login
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_role'] = 'user';
                $_SESSION['user_name'] = $full_name;
                
                header("Location: index.php");
                exit;
            }
            
        } catch (PDOException $e) {
            $message = "Kayıt sırasında bir hata oluştu. Lütfen tekrar deneyin.";
        }
    }
}

// if there is unusual problem 
if ($message) {
    echo $message;
}
?>