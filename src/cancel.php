<?php
require_once 'config.php';
require_login();

$user = current_user();
$ticket_id = $_GET['id'] ?? '';

if ($ticket_id === '') {
    die("Geçersiz istek.");
}

$stmt = $db->prepare("
    SELECT T.*, TR.departure_time 
    FROM Tickets T 
    JOIN Trips TR ON T.trip_id = TR.id 
    WHERE T.id = ? AND T.user_id = ?
");
$stmt->execute([$ticket_id, $user['id']]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    die("Bilet bulunamadı veya erişiminiz yok.");
}

if ($ticket['status'] !== 'active') {
    die("Bu bilet zaten iptal edilmiş.");
}

$departure_time = new DateTime($ticket['departure_time']);
$current_time = new DateTime();
$time_diff = $current_time->diff($departure_time);

if ($time_diff->h < 1 && $time_diff->invert == 0) {
    header("Refresh: 2; url=my_tickets.php");
    echo 'Kalkışa 1 saatten az kaldığı için bilet iptal edilemez.';
    exit;
}

$db->beginTransaction();
try {
    $stmt = $db->prepare("UPDATE Tickets SET status = 'cancelled' WHERE id = ?");
    $stmt->execute([$ticket_id]);

    $stmt = $db->prepare("UPDATE User SET balance = balance + ? WHERE id = ?");
    $stmt->execute([$ticket['total_price'], $user['id']]);

    $db->commit();
    header("Location: my_tickets.php?cancelled=1");
    exit;
} catch (Exception $e) {
    $db->rollBack();
    die("İptal sırasında hata: " . $e->getMessage());
}
?>