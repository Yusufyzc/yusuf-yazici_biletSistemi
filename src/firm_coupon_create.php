<?php
require_once 'config.php';
require_once 'header.php';
require_login();

$user = current_user();

if ($user['role'] !== 'company') {
    http_response_code(403);
    die("Bu sayfaya erişim yetkiniz yok.");
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    $discount = floatval($_POST['discount'] ?? 0);
    $expire_date = trim($_POST['expire_date'] ?? '');
    $usage_limit = intval($_POST['usage_limit'] ?? 1);

    if ($code && $discount > 0 && $discount <= 100) {
        try {
            $check_stmt = $db->prepare("SELECT id FROM Coupons WHERE code = :code");
            $check_stmt->execute([':code' => $code]);
            
            if ($check_stmt->fetch()) {
                $message = "Bu kupon kodu zaten mevcut. Lütfen farklı bir kod girin.";
            } else {
                $stmt = $db->prepare('
                    INSERT INTO Coupons (id, code, discount, expire_date, usage_limit, company_id, created_at)
                    VALUES (:id, :code, :discount, :expire_date, :usage_limit, :company_id, datetime("now"))
                ');
                
                $params = [
                    ':id' => uniqid(),
                    ':code' => $code,
                    ':discount' => $discount,
                    ':usage_limit' => $usage_limit,
                    ':company_id' => $user['company_id']
                ];
                
                if (!empty($expire_date)) {
                    $params[':expire_date'] = $expire_date;
                } else {
                    $params[':expire_date'] = null;
                }
                
                $stmt->execute($params);
                $message = "Kupon başarıyla oluşturuldu.";
            }
        } catch (PDOException $e) {
            $message = "Kupon oluşturma hatası: " . $e->getMessage();
        }
    } else {
        $message = "Lütfen geçerli bir kupon kodu ve indirim oranı girin (1-100 arası).";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kupon Oluştur</title>
</head>
<body>

<h2>Kupon Oluştur</h2>
<?php if ($message): ?><p style="color:red;"><?php echo e($message); ?></p><?php endif; ?>

<form method="POST">
    <label>Kupon Kodu:</label><br>
    <input type="text" name="code" required><br><br>
    
    <label>İndirim Oranı (%):</label><br>
    <input type="number" name="discount" min="1" max="100" required><br><br>
    
    <label>Son Kullanma Tarihi (opsiyonel):</label><br>
    <input type="datetime-local" name="expire_date"><br><br>
    
    <label>Kullanım Limiti:</label><br>
    <input type="number" name="usage_limit" min="1" value="1"><br><br>
    
    <input type="submit" value="Kupon Oluştur">
</form>

<br>
<a href="firm_panel.php">Firma Paneline Dön</a>

</body>
</html>