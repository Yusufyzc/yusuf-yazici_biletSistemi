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

// adding a trip
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_trip'])) {
    $departure_city = trim($_POST['departure_city'] ?? '');
    $destination_city = trim($_POST['destination_city'] ?? '');
    $departure_time = trim($_POST['departure_time'] ?? '');
    $arrival_time = trim($_POST['arrival_time'] ?? '');
    $price = intval($_POST['price'] ?? 0);
    $capacity = intval($_POST['capacity'] ?? 0);

    if ($departure_city && $destination_city && $departure_time && $arrival_time && $price > 0 && $capacity > 0) {
        try {
            $stmt = $db->prepare('
                INSERT INTO Trips (id, company_id, destination_city, arrival_time, departure_time, departure_city, price, capacity)
                VALUES (:id, :company_id, :destination_city, :arrival_time, :departure_time, :departure_city, :price, :capacity)
            ');
            $stmt->execute([
                ':id' => uniqid(),
                ':company_id' => $user['company_id'],
                ':destination_city' => $destination_city,
                ':arrival_time' => $arrival_time,
                ':departure_time' => $departure_time,
                ':departure_city' => $departure_city,
                ':price' => $price,
                ':capacity' => $capacity
            ]);
            $message = "Sefer başarıyla eklendi.";
        } catch (PDOException $e) {
            $message = "Hata: " . $e->getMessage();
        }
    } else {
        $message = "Lütfen tüm alanları doldurun.";
    }
}

// deleting coupon
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_coupon'])) {
    $coupon_id = trim($_POST['coupon_id'] ?? '');
    
    if ($coupon_id) {
        try {
            $check_stmt = $db->prepare("SELECT id FROM Coupons WHERE id = :id AND company_id = :company_id");
            $check_stmt->execute([':id' => $coupon_id, ':company_id' => $user['company_id']]);
            
            if ($check_stmt->fetch()) {
                $stmt = $db->prepare("DELETE FROM Coupons WHERE id = :id");
                $stmt->execute([':id' => $coupon_id]);
                $message = "Kupon başarıyla silindi.";
            } else {
                $message = "Bu kuponu silme yetkiniz yok.";
            }
        } catch (PDOException $e) {
            $message = "Kupon silme hatası: " . $e->getMessage();
        }
    } else {
        $message = "Geçersiz kupon ID.";
    }
}

// deleting trip
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_trip'])) {
    $trip_id = trim($_POST['trip_id'] ?? '');
    
    if ($trip_id) {
        try {
            $check_stmt = $db->prepare("SELECT id FROM Trips WHERE id = :id AND company_id = :company_id");
            $check_stmt->execute([':id' => $trip_id, ':company_id' => $user['company_id']]);
            
            if ($check_stmt->fetch()) {
                $stmt = $db->prepare("DELETE FROM Trips WHERE id = :id");
                $stmt->execute([':id' => $trip_id]);
                $message = "Sefer başarıyla silindi.";
            } else {
                $message = "Bu seferi silme yetkiniz yok.";
            }
        } catch (PDOException $e) {
            $message = "Sefer silme hatası: " . $e->getMessage();
        }
    } else {
        $message = "Geçersiz sefer ID.";
    }
}

// get trips
$stmt = $db->prepare("
    SELECT * FROM Trips WHERE company_id = :cid ORDER BY departure_time ASC
");
$stmt->execute([':cid' => $user['company_id']]);
$trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

// get tickets from the company which is selected
$coupon_stmt = $db->prepare("
    SELECT * FROM Coupons WHERE company_id = :cid ORDER BY created_at DESC
");
$coupon_stmt->execute([':cid' => $user['company_id']]);
$coupons = $coupon_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Firma Paneli</title>
</head>
<body>

<h2>Seferlerim</h2>
<?php if ($message): ?><p><?php echo e($message); ?></p><?php endif; ?>

<?php if (empty($trips)): ?>
    <p>Henüz sefer eklemediniz.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr>
            <th>Kalkış</th>
            <th>Varış</th>
            <th>Kalkış Zamanı</th>
            <th>Varış Zamanı</th>
            <th>Fiyat</th>
            <th>Kapasite</th>
            <th>İşlem</th>
        </tr>
        <?php foreach ($trips as $trip): ?>
        <tr>
            <td><?php echo e($trip['departure_city']); ?></td>
            <td><?php echo e($trip['destination_city']); ?></td>
            <td><?php echo e($trip['departure_time']); ?></td>
            <td><?php echo e($trip['arrival_time']); ?></td>
            <td><?php echo e($trip['price']); ?> TL</td>
            <td><?php echo e($trip['capacity']); ?></td>
            <td>
                <a href="trip_edit.php?id=<?php echo $trip['id']; ?>">Düzenle</a>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="delete_trip" value="1">
                    <input type="hidden" name="trip_id" value="<?php echo e($trip['id']); ?>">
                    <input type="submit" value="Sil" onclick="return confirm('Bileti silecek misiniz?')">
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<br>

<h2>Yeni Sefer Ekle</h2>
<form method="POST">
    <input type="hidden" name="add_trip" value="1">
    <label>Kalkış Şehri:</label><br>
    <input type="text" name="departure_city"><br>
    <label>Varış Şehri:</label><br>
    <input type="text" name="destination_city"><br>
    <label>Kalkış Zamanı:</label><br>
    <input type="datetime-local" name="departure_time"><br>
    <label>Varış Zamanı:</label><br>
    <input type="datetime-local" name="arrival_time"><br>
    <label>Fiyat:</label><br>
    <input type="number" name="price" min="1"><br>
    <label>Kapasite:</label><br>
    <input type="number" name="capacity" min="1"><br><br>
    <input type="submit" value="Ekle">
</form>

<br>

<h2>Kupon Yönetimi</h2>
<p><a href="firm_coupon_create.php">Yeni Kupon Oluştur</a></p>

<h3>Oluşturduğum Kuponlar</h3>
<?php if (empty($coupons)): ?>
    <p>Henüz kupon oluşturmadınız.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr>
            <th>Kupon Kodu</th>
            <th>İndirim</th>
            <th>Son Kullanma</th>
            <th>Kullanım Limiti</th>
            <th>Oluşturulma</th>
            <th>İşlem</th>
        </tr>
        <?php foreach ($coupons as $coupon): ?>
        <tr>
            <td><?php echo e($coupon['code']); ?></td>
            <td><?php echo e($coupon['discount']); ?>%</td>
            <td><?php echo e($coupon['expire_date'] ?: 'Sınırsız'); ?></td>
            <td><?php echo e($coupon['usage_limit']); ?></td>
            <td><?php echo e($coupon['created_at']); ?></td>
            <td>
                <form method="POST">
                    <input type="hidden" name="delete_coupon" value="1">
                    <input type="hidden" name="coupon_id" value="<?php echo e($coupon['id']); ?>">
                    <input type="submit" value="Sil" onclick="return confirm('Emin misiniz?')">
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

</body>
</html>