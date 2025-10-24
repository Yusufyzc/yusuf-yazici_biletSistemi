<?php
require_once "config.php";
include "header.php";

$from=$_GET['from'] ?? '';
$to=$_GET['to'] ?? '';

$parameters=[];
$sql = "SELECT Trips.*, Bus_Company.name as company_name FROM Trips LEFT JOIN Bus_Company ON Bus_Company.id=Trips.company_id WHERE 1=1";

if($from !== ''){
    $sql.=" AND departure_city LIKE :from";
    $parameters[':from']="%$from%";
}
if($to !== ''){
    $sql.=" AND destination_city LIKE :to";
    $parameters[':to']="%$to%";
}

$sql.=" ORDER BY departure_time ASC";

$statement=$db->prepare($sql);
$statement->execute($parameters);
$all_trips=$statement->fetchAll(PDO::FETCH_ASSOC);

// the trips which are in future
$trips = array_filter($all_trips, function($trip) {
    return strtotime($trip['departure_time']) > time();
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ana Sayfa</title>
</head>
<body>
    <div>
        <h1>Bilet Arama</h1>
        <form method="GET">
            <input type="text" name="from" placeholder="Kalkış">
            <input type="text" name="to" placeholder="Varış">
            <input type="submit" value="Ara">
        </form>
    </div>

    <div>
        <h2>Seferler</h2>
        <?php if(count($trips) === 0):?>
            <b>Sefer bulunamadı.</b>
        <?php else:?>
            <table>
                <tr>
                    <th>Firma</th>
                    <th>Kalkış</th>
                    <th>Varış</th>
                    <th>Tarih</th>
                    <th>Fiyat</th>
                    <th>İşlem</th>
                </tr>
                <?php foreach ($trips as $trip):?>
                    <tr>
                        <td><?php echo htmlspecialchars($trip['company_name']); ?></td>
                        <td><?php echo htmlspecialchars($trip['departure_city']); ?></td>
                        <td><?php echo htmlspecialchars($trip['destination_city']); ?></td>
                        <td><?php echo htmlspecialchars($trip['departure_time']); ?></td>
                        <td><?php echo htmlspecialchars($trip['price']); ?> TL</td>
                        <td><a href="trip.php?id=<?php echo urlencode($trip['id']); ?>">Detay / Bilet Al</a></td>
                    </tr>
                    <?php endforeach; ?>
            </table>
            <?php endif; ?>
    </div>
</body>
</html>