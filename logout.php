<?php
session_start();
require_once __DIR__ . '/config.php';

// 로그아웃 활동 기록
if (isset($_SESSION['user_id'])) {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, ip_address, user_agent) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['user_id'], 
            '로그아웃', 
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (Exception $e) {
        error_log("로그아웃 로그 기록 오류: " . $e->getMessage());
    }
}

session_unset();
session_destroy();
header('Location: login.php');
exit;
?>
