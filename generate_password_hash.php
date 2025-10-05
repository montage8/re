<?php
/**
 * 비밀번호 해시 생성 유틸리티
 * 관리자 비밀번호를 안전하게 해싱하기 위한 도구입니다.
 * 
 * 사용법:
 * php generate_password_hash.php
 */

echo "===========================================\n";
echo "     Synkey Nevi 비밀번호 해시 생성기     \n";
echo "===========================================\n\n";

// 비밀번호 입력
echo "새 비밀번호를 입력하세요: ";
$password = trim(fgets(STDIN));

if (empty($password)) {
    echo "오류: 비밀번호를 입력해야 합니다.\n";
    exit(1);
}

// 비밀번호 확인
echo "비밀번호 확인: ";
$password_confirm = trim(fgets(STDIN));

if ($password !== $password_confirm) {
    echo "오류: 비밀번호가 일치하지 않습니다.\n";
    exit(1);
}

// 비밀번호 강도 확인
if (strlen($password) < 8) {
    echo "경고: 비밀번호가 8자 미만입니다. 더 긴 비밀번호를 사용하는 것이 좋습니다.\n";
}

// 비밀번호 해시 생성
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "\n===========================================\n";
echo "생성된 해시:\n";
echo "===========================================\n";
echo $hash . "\n\n";

echo "이 해시를 데이터베이스에 저장하려면 다음 SQL 명령을 실행하세요:\n\n";
echo "UPDATE admin_users SET password_hash = '$hash' WHERE username = 'admin';\n\n";

echo "또는 새 사용자를 추가하려면:\n\n";
echo "INSERT INTO admin_users (username, password_hash) VALUES ('사용자명', '$hash');\n\n";

echo "===========================================\n";
?>
