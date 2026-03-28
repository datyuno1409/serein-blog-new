# Script Deploy Tu Dong - Serein Blog
$ErrorActionPreference = "Continue"

$SERVER = "103.9.205.28"
$PORT = "2012"
$USER = "root"
$PASS = "Next-Step@2310"
$REMOTE = "/var/www/serein-blog"

Write-Host "=========================================="
Write-Host "Serein Blog - Deploy Tu Dong"
Write-Host "=========================================="
Write-Host ""

# Buoc 1: Cai PuTTY
Write-Host "[1/6] Kiem tra PuTTY..."
try {
    $null = Get-Command plink -ErrorAction Stop
    Write-Host "OK - PuTTY da co"
}
catch {
    Write-Host "Dang cai PuTTY..."
    winget install -e --id PuTTY.PuTTY --silent
    Write-Host "OK - Da cai PuTTY"
}

# Buoc 2: Chap nhan host key
Write-Host ""
Write-Host "[2/6] Chap nhan host key..."
echo y | plink -P $PORT $USER@$SERVER -pw $PASS "exit" 2>$null
Write-Host "OK"

# Buoc 3: Tao thu muc
Write-Host ""
Write-Host "[3/6] Tao thu muc tren server..."
plink -P $PORT $USER@$SERVER -pw $PASS "mkdir -p $REMOTE"
Write-Host "OK"

# Buoc 4: Chuyen files
Write-Host ""
Write-Host "[4/6] Chuyen files (co the mat 5-10 phut)..."

Write-Host "  -> backend..."
pscp -r -P $PORT -pw $PASS "d:\serein-blog-new\backend" "${USER}@${SERVER}:${REMOTE}/"

Write-Host "  -> deploy..."
pscp -r -P $PORT -pw $PASS "d:\serein-blog-new\deploy" "${USER}@${SERVER}:${REMOTE}/"

Write-Host "  -> admin..."
pscp -r -P $PORT -pw $PASS "d:\serein-blog-new\admin" "${USER}@${SERVER}:${REMOTE}/"

Write-Host "  -> assets..."
pscp -r -P $PORT -pw $PASS "d:\serein-blog-new\assets" "${USER}@${SERVER}:${REMOTE}/"

Write-Host "  -> models..."
pscp -r -P $PORT -pw $PASS "d:\serein-blog-new\models" "${USER}@${SERVER}:${REMOTE}/"

Write-Host "  -> alembic..."
pscp -r -P $PORT -pw $PASS "d:\serein-blog-new\alembic" "${USER}@${SERVER}:${REMOTE}/"

Write-Host "  -> scripts..."
pscp -r -P $PORT -pw $PASS "d:\serein-blog-new\scripts" "${USER}@${SERVER}:${REMOTE}/"

Write-Host "  -> main.py..."
pscp -P $PORT -pw $PASS "d:\serein-blog-new\main.py" "${USER}@${SERVER}:${REMOTE}/"

Write-Host "  -> requirements.txt..."
pscp -P $PORT -pw $PASS "d:\serein-blog-new\requirements.txt" "${USER}@${SERVER}:${REMOTE}/"

Write-Host "  -> alembic.ini..."
pscp -P $PORT -pw $PASS "d:\serein-blog-new\alembic.ini" "${USER}@${SERVER}:${REMOTE}/"

Write-Host "  -> HTML files..."
pscp -P $PORT -pw $PASS "d:\serein-blog-new\*.html" "${USER}@${SERVER}:${REMOTE}/"

Write-Host "OK - Tat ca files da duoc chuyen"

# Buoc 5: Chay deployment script
Write-Host ""
Write-Host "[5/6] Chay script setup tren server..."
Write-Host "Qua trinh nay se cai dat va cau hinh moi thu..."
Write-Host ""

plink -P $PORT $USER@$SERVER -pw $PASS -t "cd $REMOTE && chmod +x deploy/deploy.sh && ./deploy/deploy.sh"

# Buoc 6: Kiem tra
Write-Host ""
Write-Host "[6/6] Kiem tra ket qua..."

$status = plink -P $PORT $USER@$SERVER -pw $PASS "systemctl is-active serein-blog"
Write-Host "Service status: $status"

$health = plink -P $PORT $USER@$SERVER -pw $PASS "curl -s http://localhost:8000/health"
Write-Host "Health check: $health"

Write-Host ""
Write-Host "=========================================="
Write-Host "HOAN TAT!"
Write-Host "=========================================="
Write-Host ""
Write-Host "Truy cap:"
Write-Host "  http://103.9.205.28/"
Write-Host "  http://103.9.205.28/api/docs"
Write-Host "  http://103.9.205.28/admin"
Write-Host ""
