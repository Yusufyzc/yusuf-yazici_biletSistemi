<?php
require_once 'config.php';

require_login();
$me = current_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

if($me && $me['role'] !== 'user') {
    echo "Sadece kayıtlı ve kullanıcı rolündekiler bilet satın alabilir.";
    echo "<script>setTimeout(function(){ window.location.href = 'index.php'; }, 1500);</script>";
    exit;
}

$trip_id = $_POST['trip_id'] ?? '';
$seat_no = intval($_POST['seat_no'] ?? 0);
$coupon_code = trim($_POST['coupon'] ?? '');

if (empty($trip_id) || $seat_no <= 0) {
    die("Geçersiz sefer veya koltuk bilgisi.");
}

$statement = $db->prepare("SELECT * FROM Trips WHERE id = :id");
$statement->execute([':id' => $trip_id]);
$trip = $statement->fetch(PDO::FETCH_ASSOC);

if (!$trip) {
    die("Sefer bulunamadı.");
}

// check the seat

$price = intval($trip['price']);
$coupon_id = null;
$coupon = null;

if ($coupon_code !== '') {
    $statement = $db->prepare("
        SELECT * FROM Coupons 
        WHERE code = :code 
        AND (expire_date IS NULL OR expire_date >= datetime('now')) 
        AND (company_id IS NULL OR company_id = :cid)
        AND usage_limit > 0
    ");
    $statement->execute([':code' => $coupon_code, ':cid' => $trip['company_id']]);
    $coupon = $statement->fetch(PDO::FETCH_ASSOC);
    
    if ($coupon) {
        // check the coupon was used or not
        try {
            $stmt = $db->prepare("SELECT id FROM User_Coupons WHERE user_id = ? AND coupon_id = ?");
            $stmt->execute([$me['id'], $coupon['id']]);
            $already_used = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($already_used) {
                die("Bu kuponu zaten kullandınız. Başka kupon deneyin.");
            }
        } catch (PDOException $e) {
            // if there is not user_coupons, continue
        }
        
        $discount = floatval($coupon['discount']);
        $price = intval(round($price * (100 - $discount) / 100));
        $coupon_id = $coupon['id'];
    } else {
        die("Geçersiz kupon kodu.");
    }
}

if ($me['balance'] < $price) {
    die("Yetersiz bakiye. Gerekli: {$price} TL, Mevcut: {$me['balance']} TL");
}

try {
    $db->beginTransaction();
    
    $statement = $db->prepare("UPDATE User SET balance = balance - :amt WHERE id = :id");
    $statement->execute([':amt' => $price, ':id' => $me['id']]);
    
    $ticket_id = uniqid();
    $statement = $db->prepare("
        INSERT INTO Tickets (id, trip_id, user_id, status, total_price) 
        VALUES (:id, :trip_id, :user_id, 'active', :total_price)
    ");
    $statement->execute([
        ':id' => $ticket_id,
        ':trip_id' => $trip_id,
        ':user_id' => $me['id'],
        ':total_price' => $price
    ]);
    
    if ($coupon_id) {
    // Coupons tablosundaki usage_limit'i azalt
    $statement = $db->prepare("UPDATE Coupons SET usage_limit = usage_limit - 1 WHERE id = :id");
    $statement->execute([':id' => $coupon_id]);
    
    // save into User_Coupons
    try {
        $stmt = $db->prepare("INSERT INTO User_Coupons (id, user_id, coupon_id, created_at) VALUES (?, ?, ?, datetime('now'))");
        $stmt->execute([uniqid(), $me['id'], $coupon_id]);
        
        // Debug
        if ($stmt->rowCount() > 0) {
            error_log("Kupon kullanımı kaydedildi: User {$me['id']}, Coupon {$coupon_id}");
        }
    } catch (PDOException $e) {
        error_log("User_Coupons hatası: " . $e->getMessage());
    }
}
    
    $db->commit();
    header('Location: my_tickets.php?purchase=success');
    exit;
    
} catch (Exception $ex) {
    $db->rollBack();
    die("Satın alma sırasında hata: " . $ex->getMessage());
}
?>