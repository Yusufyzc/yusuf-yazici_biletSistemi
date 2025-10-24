<?php
require_once 'config.php';
require_once "header.php";
require_login();

$user = current_user();
if ($user['role'] !== 'company') {
    die("Yetkiniz yok.");
}

// deletion of ticket
if (isset($_GET['delete_ticket'])) {
    $ticket_id = $_GET['delete_ticket'];
    
    // ticket is in company or not?
    $stmt = $db->prepare("
        SELECT T.id 
        FROM Tickets T
        JOIN Trips TR ON T.trip_id = TR.id
        WHERE T.id = ? AND TR.company_id = ?
    ");
    $stmt->execute([$ticket_id, $user['company_id']]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($ticket) {
        try {
            // deletion from booked_seats
            try {
                $stmt = $db->prepare("DELETE FROM Booked_Seats WHERE ticket_id = ?");
                $stmt->execute([$ticket_id]);
            } catch (PDOException $e) {}
            
            // deletion of ticket
            $stmt = $db->prepare("DELETE FROM Tickets WHERE id = ?");
            $stmt->execute([$ticket_id]);
            
            $message = "Bilet silindi.";
        } catch (PDOException $e) {
            $message = "Silme hatası: " . $e->getMessage();
        }
    }
}

// get the company's ticket which was sold
$stmt = $db->prepare("
    SELECT 
        T.id AS ticket_id,
        T.status,
        T.total_price,
        T.created_at,
        U.full_name AS passenger_name,
        U.email AS passenger_email,
        TR.departure_city,
        TR.destination_city,
        TR.departure_time,
        TR.arrival_time,
        BS.seat_number
    FROM Tickets T
    JOIN Trips TR ON T.trip_id = TR.id
    JOIN User U ON T.user_id = U.id
    LEFT JOIN Booked_Seats BS ON T.id = BS.ticket_id
    WHERE TR.company_id = ?
    ORDER BY T.created_at DESC
");
$stmt->execute([$user['company_id']]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Firma Biletleri</title>
</head>
<body>
    <h1>Satın Alınan Biletler</h1>
    <a href="firm_panel.php">← Firma Paneline Dön</a>
    <hr>

    <?php if (isset($message)): ?>
        <p style="color: green;"><?php echo e($message); ?></p>
    <?php endif; ?>

    <?php if (empty($tickets)): ?>
        <p>Henüz bilet satışı bulunmuyor.</p>
    <?php else: ?>
        <table border="1" cellpadding="8">
            <tr>
                <th>Yolcu</th>
                <th>E-posta</th>
                <th>Güzergah</th>
                <th>Tarih</th>
                <th>Koltuk</th>
                <th>Fiyat</th>
                <th>Durum</th>
                <th>Satın Alma</th>
                <th>İşlem</th>
            </tr>
            <?php foreach ($tickets as $ticket): ?>
            <tr>
                <td><?php echo e($ticket['passenger_name']); ?></td>
                <td><?php echo e($ticket['passenger_email']); ?></td>
                <td><?php echo e($ticket['departure_city']); ?> - <?php echo e($ticket['destination_city']); ?></td>
                <td><?php echo date('d.m.Y H:i', strtotime($ticket['departure_time'])); ?></td>
                <td><?php echo e($ticket['seat_number'] ?? '-'); ?></td>
                <td><?php echo e($ticket['total_price']); ?> TL</td>
                <td><?php echo e($ticket['status']); ?></td>
                <td><?php echo date('d.m.Y H:i', strtotime($ticket['created_at'])); ?></td>
                <td>
                    <a href="firm_tickets.php?delete_ticket=<?php echo $ticket['ticket_id']; ?>" 
                       onclick="return confirm('Bu bileti silmek istediğinize emin misiniz?')">Sil</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</body>
</html>