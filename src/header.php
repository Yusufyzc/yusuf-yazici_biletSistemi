<?php
require_once "config.php";
$me = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilet Sistemi</title>
</head>
<body>
    <header>
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 20px; background: #f5f5f5; border-bottom: 1px solid #ddd;">
            <div>
                <a href="index.php" style="font-weight: bold; font-size: 18px; text-decoration: none;">🚌 Bilet Sistemi</a>
            </div>
            
            <nav style="display: flex; align-items: center; gap: 15px;">
                <?php if(!$me): ?>
                    <a href="log-reg.php">Giriş-Kayıt</a>
                <?php else: ?>       
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="font-weight: bold; color: #333;">
                            <a href="profile.php">👤 <?php echo e($me['full_name']); ?></a>
                        </div>
                        
                    </div>
                    
                    <?php if($me['role'] ==="user"):?>
                    <a href="my_tickets.php">🎫 Biletlerim</a>
                    <span style="font-weight: bold;">💰 <?php echo e($me['balance']); ?> TL</span>
                    <?php endif; ?>
                    
                    <?php if($me['role'] === "company"): ?>
                        <a href="firm_panel.php">🏢 Firma Paneli</a>
                        <a href="firm_tickets.php">🎫 Firma Biletleri</a>
                    <?php endif; ?>
                    
                    <?php if ($me['role'] === "admin"): ?>
                        <a href="admin_panel.php">⚙️ Admin Paneli</a>
                    <?php endif; ?>
                    
                    <a href="logout.php" style="color: #d32f2f;">🚪 Çıkış Yap</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
</body>
</html>