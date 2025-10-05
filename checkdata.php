<?php
require_once __DIR__ . '/config.php';

// CORS 헤더 설정 (필요한 경우)
header('Access-Control-Allow-Origin: *');
header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = getDbConnection();
    
    // 승인 상태 읽기
    $stmt = $pdo->prepare("SELECT status FROM approval_status WHERE id = 1");
    $stmt->execute();
    $result = $stmt->fetch();
    $status = $result['status'] ?? 'not';
    
    // "check" 또는 "not"만 출력
    if ($status === 'check') {
        echo 'check';
    } else {
        echo 'not';
    }
} catch (Exception $e) {
    error_log("승인 상태 조회 오류: " . $e->getMessage());
    echo 'not'; // 오류 발생 시 기본적으로 거부 상태 반환
}
?>
