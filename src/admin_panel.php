<?php
require_once 'config.php';
require_once 'header.php';
require_user(['admin']);

$message = '';

$stmt = $db->query("SELECT COUNT(*) FROM User");
$total_users = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM User WHERE role = 'company'");
$total_companies = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM Trips");
$total_trips = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM Tickets");
$total_tickets = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Admin Paneli</title>
    <style>
        .stats { display: flex; gap: 20px; margin: 20px 0; }
        .stat-box { border: 1px solid #ccc; padding: 15px; border-radius: 5px; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body>

    <div class="stats">
        <div class="stat-box">
            <h3>Toplam Kullanıcı</h3>
            <p><?php echo $total_users; ?></p>
        </div>
        <div class="stat-box">
            <h3>Toplam Firma Yetkilisi</h3>
            <p><?php echo $total_companies; ?></p>
        </div>
        <div class="stat-box">
            <h3>Toplam Sefer</h3>
            <p><?php echo $total_trips; ?></p>
        </div>
        <div class="stat-box">
            <h3>Toplam Bilet</h3>
            <p><?php echo $total_tickets; ?></p>
        </div>
    </div>

    <div>
        <button onclick="showTab('firmManagement')">Firma Yönetimi</button>
        <button onclick="showTab('adminManagement')">Firma Admin Yönetimi</button>
        <button onclick="showTab('couponManagement')">Kupon Yönetimi</button>
    </div>

    <div id="firmManagement" class="tab-content active">
        <h2>Firma Ekle</h2>
        <form method="POST" action="admin_actions.php">
            <input type="hidden" name="action" value="add_company">
            <input type="text" name="company_name" placeholder="Firma Adı" required>
            <input type="text" name="logo_path" placeholder="Logo Yolu (opsiyonel)">
            <button type="submit">Firma Ekle</button>
        </form>

        <h2>Firma Listesi</h2>
        <?php
        $stmt = $db->query("SELECT * FROM Bus_Company");
        $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <table border="1" cellpadding="6">
            <tr>
                <th>Firma Adı</th>
                <th>Oluşturulma Tarihi</th>
                <th>İşlem</th>
            </tr>
            <?php foreach ($companies as $company): ?>
            <tr>
                <td><?php echo e($company['name']); ?></td>
                <td><?php echo e($company['created_at']); ?></td>
                <td>
                    <a href="admin_actions.php?action=delete_company&id=<?php echo $company['id']; ?>" 
                       onclick="return confirm('Silmek istediğinize emin misiniz?')">Sil</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div id="adminManagement" class="tab-content">
        <h2>Firma Admin Ekle</h2>
        <form method="POST" action="admin_actions.php">
            <input type="hidden" name="action" value="add_company_admin">
            <input type="text" name="full_name" placeholder="Ad Soyad" required>
            <input type="email" name="email" placeholder="E-posta" required>
            <input type="password" name="password" placeholder="Şifre" required>
            <select name="company_id" required>
                <option value="">Firma Seçin</option>
                <?php foreach ($companies as $company): ?>
                <option value="<?php echo $company['id']; ?>"><?php echo e($company['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Admin Ekle</button>
        </form>

        <h2>Firma Admin Listesi</h2>
        <?php
        $stmt = $db->query("
            SELECT u.*, bc.name as company_name 
            FROM User u 
            LEFT JOIN Bus_Company bc ON u.company_id = bc.id 
            WHERE u.role = 'company'
        ");
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <table border="1" cellpadding="6">
            <tr>
                <th>Ad Soyad</th>
                <th>E-posta</th>
                <th>Firma</th>
                <th>İşlem</th>
            </tr>
            <?php foreach ($admins as $admin): ?>
            <tr>
                <td><?php echo e($admin['full_name']); ?></td>
                <td><?php echo e($admin['email']); ?></td>
                <td><?php echo e($admin['company_name']); ?></td>
                <td>
                    <a href="admin_actions.php?action=delete_admin&id=<?php echo $admin['id']; ?>" 
                       onclick="return confirm('Silmek istediğinize emin misiniz?')">Sil</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div id="couponManagement" class="tab-content">
        <h2>Kupon Ekle</h2>
        <form method="POST" action="admin_actions.php">
            <input type="hidden" name="action" value="add_coupon">
            <input type="text" name="code" placeholder="Kupon Kodu" required>
            <input type="number" name="discount" step="0.1" placeholder="İndirim Oranı" required>
            <select name="company_id">
                <option value="">Tüm Firmalar (Genel Kupon)</option>
                <?php foreach ($companies as $company): ?>
                <option value="<?php echo $company['id']; ?>"><?php echo e($company['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="usage_limit" placeholder="Kullanım Limiti" required>
            <input type="datetime-local" name="expire_date" required>
            <button type="submit">Kupon Ekle</button>
        </form>

        <h2>Kupon Listesi</h2>
        <?php
        $stmt = $db->query("
            SELECT c.*, bc.name as company_name 
            FROM Coupons c 
            LEFT JOIN Bus_Company bc ON c.company_id = bc.id
        ");
        $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <table border="1" cellpadding="6">
            <tr>
                <th>Kod</th>
                <th>İndirim</th>
                <th>Firma</th>
                <th>Kullanım Limiti</th>
                <th>Son Kullanma</th>
                <th>İşlem</th>
            </tr>
            <?php foreach ($coupons as $coupon): ?>
            <tr>
                <td><?php echo e($coupon['code']); ?></td>
                <td>%<?php echo e($coupon['discount']); ?></td>
                <td><?php echo e($coupon['company_name'] ?: 'Tüm Firmalar'); ?></td>
                <td><?php echo e($coupon['usage_limit']); ?></td>
                <td><?php echo e($coupon['expire_date']); ?></td>
                <td>
                    <a href="admin_actions.php?action=delete_coupon&id=<?php echo $coupon['id']; ?>" 
                       onclick="return confirm('Silmek istediğinize emin misiniz?')">Sil</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <script>
        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.getElementById(tabName).classList.add('active');
        }
    </script>
</body>
</html>