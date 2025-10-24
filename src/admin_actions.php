<?php
require_once 'config.php';
require_user(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add_company':
                $company_name = trim($_POST['company_name']);
                $logo_path = trim($_POST['logo_path'] ?? '');
                
                $stmt = $db->prepare("INSERT INTO Bus_Company (id, name, logo_path) VALUES (?, ?, ?)");
                $stmt->execute([uniqid(), $company_name, $logo_path]);
                
                $_SESSION['success'] = "Firma başarıyla eklendi.";
                break;
                
            case 'add_company_admin':
                $full_name = trim($_POST['full_name']);
                $email = trim($_POST['email']);
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $company_id = $_POST['company_id'];
                
                $stmt = $db->prepare("INSERT INTO User (id, full_name, email, role, password, company_id, balance) VALUES (?, ?, ?, 'company', ?, ?, 0)");
                $stmt->execute([uniqid(), $full_name, $email, $password, $company_id]);
                
                $_SESSION['success'] = "Firma admini başarıyla eklendi.";
                break;
                
            case 'add_coupon':
                $code = trim($_POST['code']);
                $discount = floatval($_POST['discount']);
                $company_id = $_POST['company_id'] ?: null;
                $usage_limit = intval($_POST['usage_limit']);
                $expire_date = $_POST['expire_date'];
                
                $stmt = $db->prepare("INSERT INTO Coupons (id, code, discount, company_id, usage_limit, expire_date) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([uniqid(), $code, $discount, $company_id, $usage_limit, $expire_date]);
                
                $_SESSION['success'] = "Kupon başarıyla eklendi.";
                break;
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Hata: " . $e->getMessage();
    }
    
    header("Location: admin_panel.php");
    exit;
}

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

if ($action && $id) {
    try {
        switch ($action) {
            case 'delete_company':
                $stmt = $db->prepare("DELETE FROM Bus_Company WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['success'] = "Firma silindi.";
                break;
                
            case 'delete_admin':
                $stmt = $db->prepare("DELETE FROM User WHERE id = ? AND role = 'company'");
                $stmt->execute([$id]);
                $_SESSION['success'] = "Admin silindi.";
                break;
                
            case 'delete_coupon':
                $stmt = $db->prepare("DELETE FROM Coupons WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['success'] = "Kupon silindi.";
                break;
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Hata: " . $e->getMessage();
    }
    
    header("Location: admin_panel.php");
    exit;
}