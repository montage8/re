<?php
// 승인 상태 파일 경로
$approval_file = __DIR__ . '/approval_status.txt';

// CORS 헤더 설정 (필요한 경우)
header('Access-Control-Allow-Origin: *');
header('Content-Type: text/plain; charset=utf-8');

// 승인 상태 읽기
$status = 'not';
if (file_exists($approval_file)) {
    $status = trim(file_get_contents($approval_file));
}

// "check" 또는 "not"만 출력
if ($status === 'check') {
    echo 'check';
} else {
    echo 'not';
}
?>
