<?php
require_once 'config.php';
require_once 'header.php';
require_login();

$user = current_user();
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if ($full_name && $email) {
        try {
            $stmt = $db->prepare("UPDATE User SET full_name = ?, email = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $user['id']]);
            $message = "Profil bilgileri güncellendi.";
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'UNIQUE')) {
                $message = "Bu e-posta adresi zaten kullanılıyor.";
            } else {
                $message = "Hata: " . $e->getMessage();
            }
        }
    }

    if ($current_password && $new_password && $confirm_password) {
        if ($new_password !== $confirm_password) {
            $message = "Yeni şifreler eşleşmiyor.";
        } else {
            
            $stmt = $db->prepare("SELECT password FROM User WHERE id = ?");
            $stmt->execute([$user['id']]);
            $db_user = $stmt->fetch(PDO::FETCH_ASSOC);
            
           if ($db_user && password_verify($current_password, $db_user['password'])) {
           $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
           $stmt = $db->prepare("UPDATE User SET password = ? WHERE id = ?");
           $stmt->execute([$hashed_password, $user['id']]);
           $message = "Şifre başarıyla değiştirildi.";
        }else {
            $message = "Mevcut şifre hatalı.";
        }
    }
    }
}

// get user's information and data
$stmt = $db->prepare("SELECT full_name, email FROM User WHERE id = ?");
$stmt->execute([$user['id']]);
$current_user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Profil Düzenle</title>
</head>
<body>
    <h1>Profil Düzenle</h1>
    
    <?php if ($message): ?>
        <p style="color: green;"><?php echo e($message); ?></p>
    <?php endif; ?>

    <form method="POST">
        <h3>Kişisel Bilgiler</h3>
        <div>
            <label>Ad Soyad:</label><br>
            <input type="text" name="full_name" value="<?php echo e($current_user['full_name']); ?>" required>
        </div>
        <div>
            <label>E-posta:</label><br>
            <input type="email" name="email" value="<?php echo e($current_user['email']); ?>" required>
        </div>

        <h3>Şifre Değiştir</h3>
        <div>
            <label>Mevcut Şifre:</label><br>
            <input type="password" name="current_password">
        </div>
        <div>
            <label>Yeni Şifre:</label><br>
            <input type="password" name="new_password">
        </div>
        <div>
            <label>Yeni Şifre Tekrar:</label><br>
            <input type="password" name="confirm_password">
        </div>

        <br>
        <button type="submit">Güncelle</button>
        <a href="index.php">İptal</a>
    </form>
</body>
</html>