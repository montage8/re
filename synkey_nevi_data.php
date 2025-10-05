<?php
session_start();
require_once __DIR__ . '/config.php';

// 인증 확인
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php');
    exit;
}

// 세션 타임아웃 확인
if (!checkSessionTimeout()) {
    header('Location: login.php');
    exit;
}

$pdo = getDbConnection();
$user_id = $_SESSION['user_id'] ?? null;

// POST 요청 처리 (승인/거부)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // CSRF 토큰 검증
    if (!verifyCsrfToken($csrf_token)) {
        $message = "잘못된 요청입니다. 페이지를 새로고침하고 다시 시도하세요.";
        $message_type = "error";
    } else {
        try {
            if ($action === 'approve') {
                // 승인
                $stmt = $pdo->prepare("UPDATE approval_status SET status = 'check', updated_by = ? WHERE id = 1");
                $stmt->execute([$user_id]);
                
                // 활동 로그 기록
                $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $user_id, 
                    '승인 상태 변경', 
                    '데이터베이스 삭제 승인됨', 
                    $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]);
                
                $message = "승인되었습니다.";
                $message_type = "success";
            } elseif ($action === 'reject') {
                // 거부
                $stmt = $pdo->prepare("UPDATE approval_status SET status = 'not', updated_by = ? WHERE id = 1");
                $stmt->execute([$user_id]);
                
                // 활동 로그 기록
                $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $user_id, 
                    '승인 상태 변경', 
                    '데이터베이스 삭제 거부됨', 
                    $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]);
                
                $message = "거부되었습니다.";
                $message_type = "info";
            }
        } catch (Exception $e) {
            error_log("승인 상태 변경 오류: " . $e->getMessage());
            $message = "상태 변경 중 오류가 발생했습니다.";
            $message_type = "error";
        }
    }
}

// 현재 승인 상태 읽기
try {
    $stmt = $pdo->prepare("SELECT status FROM approval_status WHERE id = 1");
    $stmt->execute();
    $result = $stmt->fetch();
    $current_status = $result['status'] ?? 'not';
} catch (Exception $e) {
    error_log("승인 상태 조회 오류: " . $e->getMessage());
    $current_status = 'not';
}

// CSRF 토큰 생성
$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Synkey Nevi - 데이터 관리</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 600px;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
        }
        h2 {
            text-align: center;
            color: #666;
            font-size: 18px;
            margin-bottom: 30px;
        }
        .status-display {
            text-align: center;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 30px;
            font-weight: bold;
        }
        .status-approved {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .radio-group {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .radio-option {
            display: flex;
            align-items: center;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .radio-option:hover {
            background-color: #f8f9fa;
            border-color: #007bff;
        }
        .radio-option input[type="radio"] {
            margin-right: 10px;
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        .radio-option label {
            cursor: pointer;
            font-size: 16px;
            flex: 1;
        }
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        input[type="submit"] {
            flex: 1;
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
        .logout-btn {
            flex: 1;
            padding: 12px;
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .logout-btn:hover {
            background-color: #545b62;
        }
        .message {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Synkey Nevi 데이터 관리</h1>
        <h2>데이터베이스 삭제 승인</h2>
        
        <?php if (isset($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="status-display <?php echo $current_status === 'check' ? 'status-approved' : 'status-rejected'; ?>">
            현재 상태: <?php echo $current_status === 'check' ? '승인됨' : '거부됨'; ?>
        </div>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <div class="form-group">
                <div class="radio-group">
                    <div class="radio-option">
                        <input type="radio" id="approve" name="action" value="approve" required>
                        <label for="approve">승인 (클라이언트가 데이터베이스를 삭제할 수 있습니다)</label>
                    </div>
                    <div class="radio-option">
                        <input type="radio" id="reject" name="action" value="reject" required>
                        <label for="reject">거부 (클라이언트가 데이터베이스를 삭제할 수 없습니다)</label>
                    </div>
                </div>
            </div>
            <div class="button-group">
                <input type="submit" value="적용">
                <a href="logout.php" class="logout-btn">로그아웃</a>
            </div>
        </form>
    </div>
</body>
</html>
