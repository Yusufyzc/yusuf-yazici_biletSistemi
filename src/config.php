<?php
date_default_timezone_set('Europe/Istanbul');
session_start();

try {
    $db_path = '/var/www/database/biletSistemi.db';
    $db = new PDO("sqlite:" . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch(PDOException $e) {
    echo "Connection error: " . $e->getMessage();
}

function current_user(){
    global $db; // Global variable in function
    
    if (isset($_SESSION['user_id'])) {
        $stmt = $db->prepare("SELECT id, full_name, role, balance, company_id FROM User WHERE id = :id");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    return null;
}

function e($conv){
    return htmlspecialchars($conv ?? '', ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
}

function require_login(){
    if(!isset($_SESSION['user_id'])){
        header('location: index.php');
        exit;
    }
}

function require_user($roles=[]){
    $u = current_user();
    if(!$u || !in_array($u['role'], (array)$roles)){
        http_response_code(403);
        exit;
    }
}
?>