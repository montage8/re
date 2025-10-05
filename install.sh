#!/bin/bash

# Synkey Nevi 설치 스크립트
# 이 스크립트는 시스템 설치를 자동화합니다.

echo "=========================================="
echo "    Synkey Nevi 설치 스크립트"
echo "=========================================="
echo ""

# 색상 코드
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 에러 처리
set -e

# 1. 사전 요구사항 확인
echo "1. 사전 요구사항 확인 중..."

# PHP 버전 확인
if ! command -v php &> /dev/null; then
    echo -e "${RED}오류: PHP가 설치되어 있지 않습니다.${NC}"
    exit 1
fi

PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo -e "${GREEN}✓ PHP $PHP_VERSION 발견${NC}"

# MySQL 확인
if ! command -v mysql &> /dev/null; then
    echo -e "${RED}오류: MySQL/MariaDB가 설치되어 있지 않습니다.${NC}"
    exit 1
fi
echo -e "${GREEN}✓ MySQL/MariaDB 발견${NC}"

# 2. 설정 파일 생성
echo ""
echo "2. 설정 파일 생성 중..."

if [ -f "config.php" ]; then
    echo -e "${YELLOW}경고: config.php 파일이 이미 존재합니다.${NC}"
    read -p "덮어쓰시겠습니까? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "설정 파일 생성을 건너뜁니다."
    else
        cp config.example.php config.php
        echo -e "${GREEN}✓ config.php 파일 생성됨${NC}"
    fi
else
    cp config.example.php config.php
    echo -e "${GREEN}✓ config.php 파일 생성됨${NC}"
fi

# 3. 데이터베이스 정보 입력
echo ""
echo "3. 데이터베이스 설정"
echo "데이터베이스 연결 정보를 입력하세요:"

read -p "데이터베이스 호스트 [localhost]: " DB_HOST
DB_HOST=${DB_HOST:-localhost}

read -p "데이터베이스 이름 [synkey_nevi_db]: " DB_NAME
DB_NAME=${DB_NAME:-synkey_nevi_db}

read -p "데이터베이스 사용자 [synkey_user]: " DB_USER
DB_USER=${DB_USER:-synkey_user}

read -sp "데이터베이스 비밀번호: " DB_PASS
echo

# config.php 파일 업데이트
if [ -f "config.php" ]; then
    sed -i "s/define('DB_HOST', 'localhost');/define('DB_HOST', '$DB_HOST');/" config.php
    sed -i "s/define('DB_NAME', 'synkey_nevi_db');/define('DB_NAME', '$DB_NAME');/" config.php
    sed -i "s/define('DB_USER', 'synkey_user');/define('DB_USER', '$DB_USER');/" config.php
    sed -i "s/define('DB_PASS', 'your_secure_password_here');/define('DB_PASS', '$DB_PASS');/" config.php
    echo -e "${GREEN}✓ 데이터베이스 설정이 config.php에 저장되었습니다.${NC}"
fi

# 4. 데이터베이스 생성
echo ""
echo "4. 데이터베이스 생성"
read -p "데이터베이스를 생성하시겠습니까? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    read -p "MySQL root 비밀번호를 입력하세요: " -s MYSQL_ROOT_PASS
    echo
    
    mysql -u root -p"$MYSQL_ROOT_PASS" < database_setup.sql
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ 데이터베이스가 성공적으로 생성되었습니다.${NC}"
    else
        echo -e "${RED}오류: 데이터베이스 생성 실패${NC}"
        echo "수동으로 다음 명령을 실행하세요:"
        echo "mysql -u root -p < database_setup.sql"
        exit 1
    fi
else
    echo -e "${YELLOW}데이터베이스 생성을 건너뜁니다.${NC}"
    echo "수동으로 다음 명령을 실행하세요:"
    echo "mysql -u root -p < database_setup.sql"
fi

# 5. 파일 권한 설정
echo ""
echo "5. 파일 권한 설정"
if [ -f "config.php" ]; then
    chmod 600 config.php
    echo -e "${GREEN}✓ config.php 권한 설정 (600)${NC}"
fi

chmod 644 *.php 2>/dev/null || true
echo -e "${GREEN}✓ PHP 파일 권한 설정 (644)${NC}"

# 6. 설치 완료
echo ""
echo "=========================================="
echo -e "${GREEN}설치가 완료되었습니다!${NC}"
echo "=========================================="
echo ""
echo "기본 로그인 정보:"
echo "  사용자명: admin"
echo "  비밀번호: synkey_delete_secure_2024"
echo ""
echo -e "${YELLOW}중요: 첫 로그인 후 즉시 비밀번호를 변경하세요!${NC}"
echo ""
echo "비밀번호 변경 방법:"
echo "  php generate_password_hash.php"
echo ""
echo "자세한 설정 방법은 DATABASE_SETUP_GUIDE.md를 참조하세요."
echo ""
