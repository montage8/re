<?php
session_start();

// 인증 확인
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php');
    exit;
}

// 승인 상태 파일 경로
$approval_file = __DIR__ . '/approval_status.txt';

// POST 요청 처리 (승인/거부)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'approve') {
        // 승인
        file_put_contents($approval_file, 'check');
        $message = "승인되었습니다.";
        $message_type = "success";
    } elseif ($action === 'reject') {
        // 거부
        file_put_contents($approval_file, 'not');
        $message = "거부되었습니다.";
        $message_type = "info";
    }
}

// 현재 승인 상태 읽기
$current_status = 'not';
if (file_exists($approval_file)) {
    $current_status = trim(file_get_contents($approval_file));
}
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
