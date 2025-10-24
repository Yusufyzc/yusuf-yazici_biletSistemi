<?php
require_once "config.php";
require_once "header.php";

$id = $_GET['id'] ?? '';

if (empty($id)) {
    echo "Geçersiz sefer ID.<br>";
    exit;
}

$statement = $db->prepare("SELECT Trips.*, Bus_Company.name as firm_name FROM Trips 
LEFT JOIN Bus_Company ON Bus_Company.id = Trips.company_id WHERE Trips.id = :id");
$statement->execute([':id' => $id]);
$trip = $statement->fetch(PDO::FETCH_ASSOC);

if(!$trip){
    echo "Sefer bulunamadı. ID: " . htmlspecialchars($id) . "<br>";
    exit;
}

$statement = $db->prepare("
    SELECT bs.seat_number 
    FROM Booked_Seats bs 
    JOIN Tickets t ON bs.ticket_id = t.id 
    WHERE t.trip_id = :tid AND t.status = 'active'
");
$statement->execute([':tid' => $trip['id']]);
$taken = $statement->fetchAll(PDO::FETCH_COLUMN);

error_log("Trip Details: " . print_r($trip, true));
error_log("Taken Seats: " . print_r($taken, true));

try {
    $statement = $db->prepare("
        SELECT bs.seat_number 
        FROM Booked_Seats bs
        JOIN Tickets t ON bs.ticket_id = t.id 
        WHERE t.trip_id = :tid AND t.status = 'active'
    ");
    $statement->execute([':tid' => $id]);
    $taken = $statement->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    try {
        $statement = $db->prepare("
            SELECT seat_number 
            FROM Tickets 
            WHERE trip_id = :tid AND status = 'active'
        ");
        $statement->execute([':tid' => $id]);
        $taken = $statement->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e2) {
        $taken = [];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sefer Detayları</title>
    <style>
        .seat { display: inline-block; width: 40px; height: 40px; border: 1px solid #ccc; margin: 5px; text-align: center; line-height: 40px; cursor: pointer; }
        .seat.taken { background: #ffcccc; cursor: not-allowed; }
        .seat.selected { background: #ccffcc; }
    </style>
</head>
<body>
    <div>
        <h2>Sefer Detayları</h2>
        <p><strong>Firma:</strong> <?php echo e($trip['firm_name']); ?></p>
        <p><strong>Güzergah:</strong> <?php echo e($trip['departure_city']); ?> -> <?php echo e($trip['destination_city']); ?></p>
        <p><strong>Kalkış:</strong> <?php echo e($trip['departure_time']); ?></p>
        <p><strong>Varış:</strong> <?php echo e($trip['arrival_time']); ?></p>
        <p><strong>Fiyat:</strong> <?php echo e($trip['price']); ?> TL</p>
        <p><strong>Kapasite:</strong> <?php echo e($trip['capacity']); ?> koltuk</p>

        <h3>Koltuk Seçimi</h3>
            <form method="post" action="purchase.php">
                <input type="hidden" name="trip_id" value="<?php echo $trip['id']; ?>">
                
                <div>
                    <?php
                    $seatCount = intval($trip['capacity']);
                    for ($i = 1; $i <= $seatCount; $i++) {
                        $isTaken = in_array($i, $taken);
                        $cls = $isTaken ? 'seat taken' : 'seat';
                        
                        echo "<label class='{$cls}'>";
                        if ($isTaken) {
                            echo "❌"; 
                        } else {
                            echo "<input type='radio' name='seat_no' value='{$i}' required> {$i}";
                        }
                        echo "</label>";
                        
                        if ($i % 4 == 0) echo "<br>";
                    }
                    ?>
                </div>
                <br>
                <div>
                    <input type="text" name="coupon" placeholder="Kupon kodu (varsa)">
                    <button type="submit">Satın Al</button>
                </div>
            </form>
    </div>


</body>
</html>