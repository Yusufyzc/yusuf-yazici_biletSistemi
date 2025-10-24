<?php
require_once 'config.php';
require_once 'header.php';
require_login();

$user = current_user();
if ($user['role'] !== 'company') {
    die("Yetkiniz yok.");
}

$trip_id = $_GET['id'] ?? '';
$message = '';

$stmt = $db->prepare("SELECT * FROM Trips WHERE id = ? AND company_id = ?");
$stmt->execute([$trip_id, $user['company_id']]);
$trip = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trip) {
    die("Sefer bulunamadı.");
}

// take seats which are occupied
try {
    $stmt = $db->prepare("
        SELECT bs.seat_number 
        FROM Booked_Seats bs
        JOIN Tickets t ON bs.ticket_id = t.id 
        WHERE t.trip_id = ? AND t.status = 'active'
    ");
    $stmt->execute([$trip_id]);
    $taken_seats = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $taken_seats = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $departure_city = trim($_POST['departure_city']);
    $destination_city = trim($_POST['destination_city']);
    $departure_time = trim($_POST['departure_time']);
    $arrival_time = trim($_POST['arrival_time']);
    $price = intval($_POST['price']);
    $capacity = intval($_POST['capacity']);

    try {
        $stmt = $db->prepare("
            UPDATE Trips 
            SET departure_city = ?, destination_city = ?, departure_time = ?, 
                arrival_time = ?, price = ?, capacity = ? 
            WHERE id = ? AND company_id = ?
        ");
        $stmt->execute([
            $departure_city, $destination_city, $departure_time, 
            $arrival_time, $price, $capacity, $trip_id, $user['company_id']
        ]);
        
        header("Location: firm_panel.php?updated=1");
        $message = "Sefer başarıyla güncellendi.";
        exit;
    } catch (PDOException $e) {
        $message = "Hata: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sefer Düzenle</title>
    <style>
        .seat { display: inline-block; width: 40px; height: 40px; border: 1px solid #ccc; margin: 2px; text-align: center; line-height: 40px; }
        .taken { background: #ffcccc; }
        .empty { background: #ccffcc; }
    </style>
</head>
<body>
    <h1>Sefer Düzenle</h1>
    
    <?php if ($message): ?>
        <p style="color: red;"><?php echo e($message); ?></p>
    <?php endif; ?>
    
    <form method="POST">
        <div>
            <label>Kalkış Şehri:</label>
            <input type="text" name="departure_city" value="<?php echo e($trip['departure_city']); ?>" required>
        </div>
        <div>
            <label>Varış Şehri:</label>
            <input type="text" name="destination_city" value="<?php echo e($trip['destination_city']); ?>" required>
        </div>
        <div>
            <label>Kalkış Zamanı:</label>
            <input type="datetime-local" name="departure_time" value="<?php echo date('Y-m-d\TH:i', strtotime($trip['departure_time'])); ?>" required>
        </div>
        <div>
            <label>Varış Zamanı:</label>
            <input type="datetime-local" name="arrival_time" value="<?php echo date('Y-m-d\TH:i', strtotime($trip['arrival_time'])); ?>" required>
        </div>
        <div>
            <label>Fiyat (TL):</label>
            <input type="number" name="price" value="<?php echo e($trip['price']); ?>" min="1" required>
        </div>
        <div>
            <label>Kapasite:</label>
            <input type="number" name="capacity" value="<?php echo e($trip['capacity']); ?>" min="1" required>
        </div>
        
        <button type="submit">Güncelle</button>
        <a href="firm_panel.php">İptal</a>
    </form>

    <h2>Koltuk Durumu</h2>
    <p>Toplam: <?php echo $trip['capacity']; ?> koltuk | Dolu: <?php echo count($taken_seats); ?> koltuk</p>
    
    <div>
        <?php for ($i = 1; $i <= $trip['capacity']; $i++): ?>
            <?php $is_taken = in_array($i, $taken_seats); ?>
            <div class="seat <?php echo $is_taken ? 'taken' : 'empty'; ?>">
                <?php echo $i; ?>
            </div>
            <?php if ($i % 4 == 0) echo "<br>"; ?>
        <?php endfor; ?>
    </div>

</body>
</html>