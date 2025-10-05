<?php
/**
 * 데이터베이스 설정 파일 예시
 * 이 파일을 config.php로 복사하고 실제 값으로 수정하세요.
 */

// 데이터베이스 설정
define('DB_HOST', 'localhost');
define('DB_NAME', 'synkey_nevi_db');
define('DB_USER', 'synkey_user');
define('DB_PASS', 'your_secure_password_here');  // 강력한 비밀번호로 변경하세요!
define('DB_CHARSET', 'utf8mb4');

// 데이터베이스 연결 함수
function getDbConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // 프로덕션 환경에서는 에러 메시지를 로그 파일에 기록하고 일반 메시지를 표시
        error_log("데이터베이스 연결 오류: " . $e->getMessage());
        die("데이터베이스 연결에 실패했습니다. 시스템 관리자에게 문의하세요.");
    }
}

// 세션 보안 설정
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

// 세션 타임아웃 (30분)
define('SESSION_TIMEOUT', 1800);

// 로그인 시도 제한 설정
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15분

// CSRF 토큰 생성 함수
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// CSRF 토큰 검증 함수
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// 세션 타임아웃 확인 함수
function checkSessionTimeout() {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}
?>
