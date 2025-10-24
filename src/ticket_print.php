<?php
require_once 'config.php';
require_login();

$user = current_user();
$ticket_id = $_GET['id'] ?? '';

$stmt = $db->prepare("
SELECT 
    T.id AS ticket_id,
    T.total_price,
    T.status,
    TR.departure_city,
    TR.destination_city,
    TR.departure_time,
    TR.arrival_time,
    BC.name AS company_name
FROM Tickets T
JOIN Trips TR ON T.trip_id = TR.id
JOIN Bus_Company BC ON TR.company_id = BC.id
WHERE T.id = :tid AND T.user_id = :uid
LIMIT 1
");
$stmt->execute([':tid' => $ticket_id, ':uid' => $user['id']]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    die("Bilet bulunamadı veya erişiminiz yok.");
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Bilet Yazdır</title>
    <style>
        body { font-family: Arial; }
        .ticket { border: 1px solid #333; padding: 20px; width: 400px; margin: 20px auto; }
        .btn { background: #4CAF50; color: #fff; padding: 8px 12px; border: none; cursor: pointer; }
    </style>
</head>
<body>
<div class="ticket">
    <h2><?php echo e($ticket['company_name']); ?> - Yolcu Bileti</h2>
    <p><strong>Kalkış:</strong> <?php echo e($ticket['departure_city']); ?></p>
    <p><strong>Varış:</strong> <?php echo e($ticket['destination_city']); ?></p>
    <p><strong>Kalkış Zamanı:</strong> <?php echo e($ticket['departure_time']); ?></p>
    <p><strong>Varış Zamanı:</strong> <?php echo e($ticket['arrival_time']); ?></p>
    <p><strong>Durum:</strong> <?php echo e($ticket['status']); ?></p>
    <p><strong>Fiyat:</strong> <?php echo e($ticket['total_price']); ?> TL</p>
    <hr>
    <p>Yolcu: <?php echo e($user['full_name']); ?></p>
    <p>Bilet No: <?php echo e($ticket['ticket_id']); ?></p>
</div>

<div style="text-align:center;">
    <button class="btn" onclick="window.print()">Yazdır</button>
    <a href="my_tickets.php">Geri Dön</a>
</div>
</body>
</html>
