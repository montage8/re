<?php
session_start();
require_once __DIR__ . '/config.php';

// 이미 로그인한 경우 리다이렉트
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    if (checkSessionTimeout()) {
        header('Location: synkey_nevi_data.php');
        exit;
    }
}

// 사용자 IP 주소 가져오기
function getClientIp() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

// 로그인 시도 횟수 확인
function checkLoginAttempts($username, $ip, $pdo) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as attempt_count 
        FROM login_attempts 
        WHERE (username = ? OR ip_address = ?) 
        AND success = FALSE 
        AND attempt_time > DATE_SUB(NOW(), INTERVAL ? SECOND)
    ");
    $stmt->execute([$username, $ip, LOGIN_LOCKOUT_TIME]);
    $result = $stmt->fetch();
    return $result['attempt_count'] < MAX_LOGIN_ATTEMPTS;
}

// 로그인 시도 기록
function recordLoginAttempt($username, $ip, $success, $pdo) {
    $stmt = $pdo->prepare("INSERT INTO login_attempts (username, ip_address, success) VALUES (?, ?, ?)");
    $stmt->execute([$username, $ip, $success]);
}

// POST 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    $ip = getClientIp();
    
    // CSRF 토큰 검증
    if (!verifyCsrfToken($csrf_token)) {
        $error = "잘못된 요청입니다. 페이지를 새로고침하고 다시 시도하세요.";
    } else {
        try {
            $pdo = getDbConnection();
            
            // 로그인 시도 횟수 확인
            if (!checkLoginAttempts($username, $ip, $pdo)) {
                $error = "로그인 시도 횟수를 초과했습니다. " . (LOGIN_LOCKOUT_TIME / 60) . "분 후에 다시 시도하세요.";
            } else {
                // 사용자 정보 조회
                $stmt = $pdo->prepare("SELECT id, username, password_hash, is_active FROM admin_users WHERE username = ? AND is_active = TRUE");
                $stmt->execute([$username]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password_hash'])) {
                    // 인증 성공
                    recordLoginAttempt($username, $ip, true, $pdo);
                    
                    // 마지막 로그인 시간 업데이트
                    $stmt = $pdo->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    
                    // 활동 로그 기록
                    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, ip_address, user_agent) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$user['id'], '로그인 성공', $ip, $_SERVER['HTTP_USER_AGENT'] ?? '']);
                    
                    // 세션 재생성 (세션 고정 공격 방지)
                    session_regenerate_id(true);
                    
                    $_SESSION['authenticated'] = true;
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['last_activity'] = time();
                    
                    header('Location: synkey_nevi_data.php');
                    exit;
                } else {
                    // 인증 실패
                    recordLoginAttempt($username, $ip, false, $pdo);
                    
                    // 활동 로그 기록
                    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent) VALUES (NULL, ?, ?, ?, ?)");
                    $stmt->execute(['로그인 실패', "사용자명: $username", $ip, $_SERVER['HTTP_USER_AGENT'] ?? '']);
                    
                    $error = "사용자명 또는 비밀번호가 올바르지 않습니다.";
                }
            }
        } catch (Exception $e) {
            error_log("로그인 오류: " . $e->getMessage());
            $error = "로그인 처리 중 오류가 발생했습니다. 잠시 후 다시 시도하세요.";
        }
    }
}

// CSRF 토큰 생성
$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Synkey Nevi - 로그인</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: bold;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }
        input[type="submit"] {
            width: 100%;
            padding: 12px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }
        input[type="submit"]:hover {
            background-color: #0056b3;
        }
        .error {
            color: red;
            text-align: center;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #ffe6e6;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Synkey Nevi 관리</h1>
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <div class="form-group">
                <label for="username">사용자명:</label>
                <input type="text" id="username" name="username" required autofocus autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">비밀번호:</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <input type="submit" value="로그인">
        </form>
    </div>
</body>
</html>
