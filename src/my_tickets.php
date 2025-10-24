<?php
require_once 'config.php';
require_once 'header.php';

require_login();
$user = current_user();

// get tickets without coupons
$sql = "
SELECT 
    T.id AS ticket_id, T.status, T.total_price, T.created_at,
    TR.departure_city, TR.destination_city, TR.departure_time, TR.arrival_time,
    BC.name AS company_name
FROM Tickets T 
JOIN Trips TR ON T.trip_id = TR.id 
JOIN Bus_Company BC ON TR.company_id = BC.id 
WHERE T.user_id = :uid 
ORDER BY T.created_at DESC";

$statement = $db->prepare($sql);
$statement->execute([':uid' => $user['id']]);
$tickets = $statement->fetchAll(PDO::FETCH_ASSOC);

// query the tickets their coupons independently from top
foreach ($tickets as &$ticket) {
    try {
        // get the coupon which are used with the ticket
        $stmt = $db->prepare("
            SELECT C.code, C.discount 
            FROM User_Coupons UC 
            JOIN Coupons C ON UC.coupon_id = C.id 
            WHERE UC.user_id = ? 
            AND datetime(UC.created_at) <= datetime(?)
            ORDER BY datetime(UC.created_at) DESC 
            LIMIT 1
        ");
        $stmt->execute([$user['id'], $ticket['created_at']]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $ticket['coupon_code'] = $coupon['code'] ?? '';
        $ticket['coupon_discount'] = $coupon['discount'] ?? '';
        
        
    } catch (PDOException $e) {
        error_log("Kupon sorgulama hatası: " . $e->getMessage());
        $ticket['coupon_code'] = '';
        $ticket['coupon_discount'] = '';
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Biletlerim</title>
</head>
<body>
    <br><br>

<?php if (!$tickets): ?>
<p>Hiç biletiniz bulunmamaktadır.</p>
<?php else: ?>
<table border="1" cellpadding="6">
<tr>
    <th>Firma</th>
    <th>Kalkış</th>
    <th>Varış</th>
    <th>Kalkış Zamanı</th>
    <th>Varış Zamanı</th>
    <th>Kupon</th>
    <th>Durum</th>
    <th>Toplam Fiyat</th>
    <th>İşlem</th>
</tr>
<?php foreach ($tickets as $t): ?>
<tr>
    <td><?php echo e($t['company_name']); ?></td>
    <td><?php echo e($t['departure_city']); ?></td>
    <td><?php echo e($t['destination_city']); ?></td>
    <td><?php echo e($t['departure_time']); ?></td>
    <td><?php echo e($t['arrival_time']); ?></td>
    <td>
        <?php if (!empty($t['coupon_code'])): ?>
            <?php echo e($t['coupon_code']); ?> (<?php echo e($t['coupon_discount']); ?>%)
        <?php else: ?>
            -
        <?php endif; ?>
    </td>
    <td><?php echo e($t['status']); ?></td>
    <td><?php echo e($t['total_price']); ?> TL</td>
    <td>
        <?php if ($t['status'] === 'active'): ?>
            <a href="cancel.php?id=<?php echo e($t['ticket_id']); ?>">İptal Et</a> |
        <?php endif; ?>
        <a href="ticket_print.php?id=<?php echo e($t['ticket_id']); ?>">Yazdır</a>
    </td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</body>
</html>