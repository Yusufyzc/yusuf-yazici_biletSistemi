<?php
require_once 'config.php';
require_login();

$user = current_user();
if ($user['role'] !== 'company') {
    die("Yetkiniz yok.");
}

$trip_id = $_GET['id'] ?? '';

// check the trip is under the company
$stmt = $db->prepare("SELECT id FROM Trips WHERE id = ? AND company_id = ?");
$stmt->execute([$trip_id, $user['company_id']]);
$trip = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trip) {
    die("Sefer bulunamadı.");
}

// ticket control
$stmt = $db->prepare("SELECT COUNT(*) FROM Tickets WHERE trip_id = ? AND status = 'active'");
$stmt->execute([$trip_id]);
$active_tickets = $stmt->fetchColumn();

if ($active_tickets > 0) {
    echo "Bu seferde aktif biletler bulunduğu için silinemez.";
    echo "<br><a href='firm_panel.php'>Geri Dön</a>";
    exit;
}

// deletion
try {
    // delete booked seats
    try {
        $stmt = $db->prepare("
            DELETE FROM Booked_Seats 
            WHERE ticket_id IN (SELECT id FROM Tickets WHERE trip_id = ?)
        ");
        $stmt->execute([$trip_id]);
    } catch (PDOException $e) {
        // Booked_Seats tablosu yoksa devam et
    }

    // delete tickets
    $stmt = $db->prepare("DELETE FROM Tickets WHERE trip_id = ?");
    $stmt->execute([$trip_id]);

    // delete trips
    $stmt = $db->prepare("DELETE FROM Trips WHERE id = ?");
    $stmt->execute([$trip_id]);
    
    header("Location: firm_panel.php?deleted=1");
    exit;
} catch (PDOException $e) {
    die("Silme hatası: " . $e->getMessage());
}