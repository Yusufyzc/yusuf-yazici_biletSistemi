<?php
require_once "config.php";
require_once "header.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket System</title>
</head>
<body>
    <div>
        <p>Giriş Yapınız</p>
        <form action="login.php" method="POST">       
            <label for="e-mail">E-posta:</label>
            <input type="email" name="mail"><br>
            <label for="password">Şifre:</label>
            <input type="password" name="password">
            <input type="submit" value="Giriş Yap">
        </form>
    </div>
    <br><br>
    <div>
        <p>Kayıt Olunuz</p>
        <form action="register.php" method="POST">            
            <label for="name-surname">İsim ve Soyisim</label>
            <input type="text" name="name-surname"><br>
            <label for="email">E-posta</label>
            <input type="text" name="email"><br>
            <label for="password_first">Şifre</label>
            <input type="password" name="password1"><br>
            <label for="password_confirm">Şifrenizi Doğrulayın</label>
            <input type="password" name="password2"><br>
            <input type="submit" value="Kayıt Ol">
        </form>       
    </div>
    
</body>
</html>
