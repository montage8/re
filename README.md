# Synkey Nevi 관리 시스템

이 시스템은 Synkey Nevi 데이터베이스 삭제 승인을 관리하는 웹 기반 애플리케이션입니다.

## 주요 기능

- 관리자 로그인 및 인증
- 데이터베이스 삭제 승인/거부 관리
- 클라이언트 승인 상태 확인 (checkdata.php)

## 보안 강화 사항

### 1. SQL 데이터베이스 사용
- 기존 텍스트 파일 기반에서 MySQL/MariaDB 데이터베이스로 전환
- 데이터 무결성 및 보안 향상
- 트랜잭션 지원 및 동시성 제어

### 2. 비밀번호 해싱
- bcrypt 알고리즘을 사용한 비밀번호 해싱
- 평문 비밀번호 저장 금지
- 비밀번호 검증 시 안전한 비교 함수 사용

### 3. CSRF 토큰 보호
- 모든 폼 제출에 CSRF 토큰 검증
- 크로스 사이트 요청 위조 공격 방지

### 4. 로그인 시도 제한
- 5회 실패 시 15분간 계정 잠금
- 무차별 대입 공격(Brute Force) 방지
- IP 주소 기반 제한

### 5. 세션 보안
- 세션 타임아웃 (30분)
- HttpOnly, Secure, SameSite 쿠키 플래그
- 세션 고정 공격 방지 (로그인 시 세션 ID 재생성)

### 6. 활동 로그
- 모든 중요 작업 기록 (로그인, 로그아웃, 승인 상태 변경)
- 감사 추적(Audit Trail) 제공
- 의심스러운 활동 모니터링 가능

### 7. 파일 접근 제한
- .htaccess를 통한 민감한 파일 접근 차단
- 데이터베이스 설정 파일 보호
- SQL 및 백업 파일 직접 접근 차단

## 파일 구조

```
/
├── login.php                      # 로그인 페이지
├── synkey_nevi_data.php          # 승인 관리 페이지
├── checkdata.php                 # 승인 상태 확인 API
├── logout.php                    # 로그아웃 처리
├── config.php                    # 데이터베이스 설정 (보안 필수!)
├── config.example.php            # 설정 파일 예시
├── database_setup.sql            # 데이터베이스 스키마
├── DATABASE_SETUP_GUIDE.md       # 데이터베이스 설정 가이드
├── generate_password_hash.php    # 비밀번호 해시 생성 도구
├── .htaccess                     # Apache 보안 설정
├── .gitignore                    # Git 제외 파일 목록
└── README.md                     # 이 파일
```

## 설치 방법

### 빠른 설치 (자동)

설치 스크립트를 사용하면 빠르게 설정할 수 있습니다:

```bash
chmod +x install.sh
./install.sh
```

### 수동 설치

#### 1. config.php 파일 생성

**중요**: 시스템을 사용하기 전에 반드시 `config.php` 파일을 생성해야 합니다.

```bash
# config.example.php를 config.php로 복사
cp config.example.php config.php

# config.php 편집 (데이터베이스 정보 입력)
nano config.php

# 파일 권한 제한
chmod 600 config.php
```

#### 2. 데이터베이스 설정

자세한 내용은 [DATABASE_SETUP_GUIDE.md](DATABASE_SETUP_GUIDE.md)를 참조하세요.

```bash
# MySQL에 접속
mysql -u root -p

# 데이터베이스 스키마 실행
source database_setup.sql
```

#### 3. 파일 권한 설정

```bash
# config.php 권한 제한
chmod 600 config.php

# PHP 파일 실행 권한
chmod 644 *.php
```

#### 4. 웹 서버 설정

Apache 서버에서 .htaccess 파일이 작동하도록 설정:

```apache
<Directory /var/www/html>
    AllowOverride All
</Directory>
```

## 기본 계정 정보

- **사용자명**: admin
- **비밀번호**: synkey_delete_secure_2024

**중요**: 첫 로그인 후 즉시 비밀번호를 변경하세요!

## 비밀번호 변경 방법

### 방법 1: 비밀번호 해시 생성 도구 사용

```bash
php generate_password_hash.php
```

출력된 해시를 데이터베이스에 업데이트:

```sql
UPDATE admin_users SET password_hash = '생성된_해시' WHERE username = 'admin';
```

### 방법 2: PHP 명령줄에서 직접 생성

```bash
php -r "echo password_hash('새로운_비밀번호', PASSWORD_DEFAULT);"
```

## API 사용법

### 승인 상태 확인

```http
GET /checkdata.php
```

**응답**:
- `check`: 승인됨 (데이터베이스 삭제 가능)
- `not`: 거부됨 (데이터베이스 삭제 불가)

## 보안 권장사항

### 필수 사항
1. ✅ HTTPS 사용 (SSL/TLS 인증서 설치)
2. ✅ 강력한 비밀번호 사용 (최소 12자, 대소문자, 숫자, 특수문자 포함)
3. ✅ 정기적인 비밀번호 변경 (3개월마다)
4. ✅ 데이터베이스 정기 백업
5. ✅ PHP 및 데이터베이스 최신 버전 유지

### 권장 사항
1. 방화벽 설정 (MySQL 포트 3306 외부 차단)
2. 로그 모니터링 (activity_logs 테이블 정기 확인)
3. 2단계 인증 (2FA) 추가 고려
4. IP 화이트리스트 설정
5. 침입 탐지 시스템(IDS) 설치

## 문제 해결

### 데이터베이스 연결 오류

```
데이터베이스 연결에 실패했습니다.
```

**해결 방법**:
1. MySQL/MariaDB 서비스 실행 확인: `sudo systemctl status mysql`
2. config.php의 데이터베이스 정보 확인
3. 데이터베이스 사용자 권한 확인

### 로그인 불가

```
로그인 시도 횟수를 초과했습니다.
```

**해결 방법**:
1. 15분 대기 후 재시도
2. 또는 데이터베이스에서 로그인 시도 기록 삭제:

```sql
DELETE FROM login_attempts WHERE username = 'admin';
```

### CSRF 토큰 오류

```
잘못된 요청입니다. 페이지를 새로고침하고 다시 시도하세요.
```

**해결 방법**:
- 브라우저에서 페이지 새로고침 (F5)
- 브라우저 쿠키 확인 및 허용

## 로그 확인

### 활동 로그 조회

```sql
USE synkey_nevi_db;

-- 최근 활동 로그
SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 20;

-- 특정 사용자 활동
SELECT * FROM activity_logs WHERE user_id = 1 ORDER BY created_at DESC;

-- 로그인 실패 기록
SELECT * FROM login_attempts WHERE success = FALSE ORDER BY attempt_time DESC;
```

## 라이센스

이 프로젝트는 내부 사용을 위한 것입니다.

## 지원

문제가 발생하면 시스템 관리자에게 문의하세요.
