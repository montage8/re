# Synkey Nevi 데이터베이스 설정 가이드

이 문서는 Synkey Nevi 시스템의 데이터베이스 설정 방법을 안내합니다.

## 목차
1. [사전 요구사항](#사전-요구사항)
2. [데이터베이스 설정](#데이터베이스-설정)
3. [보안 설정](#보안-설정)
4. [비밀번호 변경](#비밀번호-변경)
5. [문제 해결](#문제-해결)

---

## 사전 요구사항

- **PHP 7.4 이상** (PDO 및 MySQL 확장 필요)
- **MySQL 5.7 이상** 또는 **MariaDB 10.2 이상**
- 서버 관리자 권한 (데이터베이스 생성 및 사용자 권한 부여)

---

## 데이터베이스 설정

### 1단계: MySQL/MariaDB에 접속

터미널이나 명령 프롬프트에서 MySQL에 접속합니다:

```bash
mysql -u root -p
```

또는 phpMyAdmin을 사용하여 웹 인터페이스로 접속할 수 있습니다.

### 2단계: 데이터베이스 스키마 실행

`database_setup.sql` 파일을 실행하여 데이터베이스를 생성합니다:

```bash
mysql -u root -p < database_setup.sql
```

또는 MySQL 콘솔에서:

```sql
source /path/to/database_setup.sql;
```

### 3단계: 데이터베이스 사용자 생성 (권장)

보안을 위해 전용 데이터베이스 사용자를 생성합니다:

```sql
CREATE USER 'synkey_user'@'localhost' IDENTIFIED BY 'your_secure_password_here';
GRANT SELECT, INSERT, UPDATE ON synkey_nevi_db.* TO 'synkey_user'@'localhost';
FLUSH PRIVILEGES;
```

**중요**: `your_secure_password_here`를 강력한 비밀번호로 변경하세요!

### 4단계: config.php 파일 설정

`config.php` 파일을 열고 데이터베이스 연결 정보를 수정합니다:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'synkey_nevi_db');
define('DB_USER', 'synkey_user');
define('DB_PASS', 'your_secure_password_here');  // 3단계에서 설정한 비밀번호
```

### 5단계: 파일 권한 설정

`config.php` 파일의 권한을 제한합니다:

```bash
chmod 600 config.php
```

---

## 보안 설정

### 1. HTTPS 활성화 (필수)

서버에서 SSL/TLS 인증서를 설정하고 HTTPS를 활성화합니다:

- Let's Encrypt 무료 인증서 사용 권장: https://letsencrypt.org/
- `.htaccess` 파일에서 HTTPS 리다이렉션 활성화

```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 2. PHP 세션 보안

`php.ini` 파일에서 다음 설정을 확인합니다:

```ini
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
session.cookie_samesite = Strict
```

### 3. 파일 접근 제한

`.htaccess` 파일을 통해 민감한 파일 접근을 차단합니다:

```apache
<Files "config.php">
    Require all denied
</Files>

<Files "database_setup.sql">
    Require all denied
</Files>
```

### 4. 데이터베이스 백업

정기적으로 데이터베이스를 백업합니다:

```bash
mysqldump -u synkey_user -p synkey_nevi_db > backup_$(date +%Y%m%d).sql
```

---

## 비밀번호 변경

### 관리자 비밀번호 변경

기본 관리자 비밀번호(`synkey_delete_secure_2024`)를 변경하려면:

1. 새 비밀번호 해시 생성 스크립트 실행:

```bash
php -r "echo password_hash('새로운_비밀번호', PASSWORD_DEFAULT);"
```

2. 생성된 해시를 데이터베이스에 업데이트:

```sql
USE synkey_nevi_db;
UPDATE admin_users SET password_hash = '생성된_해시_값' WHERE username = 'admin';
```

### 데이터베이스 사용자 비밀번호 변경

```sql
ALTER USER 'synkey_user'@'localhost' IDENTIFIED BY '새로운_비밀번호';
FLUSH PRIVILEGES;
```

그리고 `config.php` 파일의 `DB_PASS`도 업데이트하세요.

---

## 데이터베이스 구조

### 테이블 설명

#### 1. admin_users
- 관리자 계정 정보를 저장
- 비밀번호는 bcrypt로 해싱되어 저장

#### 2. approval_status
- 데이터베이스 삭제 승인 상태 저장
- 'check' (승인) 또는 'not' (거부)

#### 3. login_attempts
- 로그인 시도 기록 (보안 강화)
- 실패한 로그인 시도 추적 및 계정 잠금

#### 4. activity_logs
- 모든 중요한 활동 기록 (감사 추적)
- 누가, 언제, 무엇을 했는지 기록

---

## 문제 해결

### 데이터베이스 연결 오류

**문제**: "데이터베이스 연결에 실패했습니다"

**해결방법**:
1. MySQL/MariaDB 서비스가 실행 중인지 확인
2. `config.php`의 데이터베이스 정보가 정확한지 확인
3. 데이터베이스 사용자 권한 확인

```bash
sudo systemctl status mysql
# 또는
sudo systemctl status mariadb
```

### 로그인 불가

**문제**: 관리자 로그인이 안됨

**해결방법**:
1. 기본 계정: `admin` / `synkey_delete_secure_2024`
2. 데이터베이스에 사용자가 있는지 확인:

```sql
USE synkey_nevi_db;
SELECT * FROM admin_users;
```

### 권한 오류

**문제**: "Access denied for user"

**해결방법**:
```sql
GRANT SELECT, INSERT, UPDATE ON synkey_nevi_db.* TO 'synkey_user'@'localhost';
FLUSH PRIVILEGES;
```

---

## 추가 보안 권장사항

1. **정기적인 비밀번호 변경**: 관리자 비밀번호를 정기적으로 변경하세요.
2. **로그 모니터링**: `activity_logs` 테이블을 정기적으로 확인하세요.
3. **실패한 로그인 알림**: 로그인 실패가 반복되면 알림을 받도록 설정하세요.
4. **데이터베이스 백업**: 자동 백업을 설정하세요.
5. **방화벽 설정**: 데이터베이스 포트(3306)를 외부에서 접근할 수 없도록 차단하세요.

---

## 지원

문제가 발생하면 시스템 관리자에게 문의하거나 다음을 확인하세요:
- PHP 에러 로그: `/var/log/apache2/error.log` 또는 `/var/log/php/error.log`
- MySQL 에러 로그: `/var/log/mysql/error.log`

---

**중요**: 이 시스템은 민감한 데이터를 다루므로 반드시 HTTPS를 사용하고, 정기적으로 보안 업데이트를 적용하세요.
