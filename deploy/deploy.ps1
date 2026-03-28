# Serein Blog Deployment Script for Windows
# Server: 103.9.205.28:2012

$ErrorActionPreference = "Stop"

Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "Serein Blog Platform - Deployment Script" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

# Configuration
$SERVER_IP = "103.9.205.28"
$SERVER_PORT = "2012"
$SERVER_USER = "root"
$DEPLOY_PATH = "/var/www/serein-blog"
$DB_NAME = "serein_db"
$DB_USER = "serein_user"
$DB_PASSWORD = "SereinBlog@2310"

Write-Host "Target Server: $SERVER_USER@$SERVER_IP`:$SERVER_PORT" -ForegroundColor Yellow
Write-Host "Deploy Path: $DEPLOY_PATH" -ForegroundColor Yellow
Write-Host ""

# Check if SSH is available
if (-not (Get-Command ssh -ErrorAction SilentlyContinue)) {
    Write-Host "Error: SSH is not installed or not in PATH" -ForegroundColor Red
    Write-Host "Please install OpenSSH client for Windows" -ForegroundColor Red
    exit 1
}

# Step 1: Test SSH Connection
Write-Host "Step 1: Testing SSH connection..." -ForegroundColor Green
try {
    $result = ssh -p $SERVER_PORT -o ConnectTimeout=10 "$SERVER_USER@$SERVER_IP" "echo 'SSH connection successful!'" 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✓ SSH connection successful" -ForegroundColor Green
    } else {
        throw "SSH connection failed"
    }
} catch {
    Write-Host "Error: Cannot connect to server. Please check:" -ForegroundColor Red
    Write-Host "  - Server IP: $SERVER_IP" -ForegroundColor Red
    Write-Host "  - Port: $SERVER_PORT" -ForegroundColor Red
    Write-Host "  - SSH credentials" -ForegroundColor Red
    Write-Host "  - Password: Next-Step@2310" -ForegroundColor Red
    exit 1
}
Write-Host ""

# Step 2: Create deployment directory on server
Write-Host "Step 2: Creating deployment directory..." -ForegroundColor Green
ssh -p $SERVER_PORT "$SERVER_USER@$SERVER_IP" "mkdir -p $DEPLOY_PATH"
Write-Host "✓ Directory created" -ForegroundColor Green
Write-Host ""

# Step 3: Transfer files to server using SCP
Write-Host "Step 3: Transferring files to server..." -ForegroundColor Green
Write-Host "This may take a few minutes..." -ForegroundColor Yellow

# Create a temporary directory with files to deploy
$tempDir = Join-Path $env:TEMP "serein-deploy-$(Get-Date -Format 'yyyyMMddHHmmss')"
New-Item -ItemType Directory -Path $tempDir -Force | Out-Null

# Copy files excluding certain directories
Write-Host "Preparing files for deployment..." -ForegroundColor Yellow
$excludeDirs = @('.venv', '__pycache__', '.git', 'node_modules', 'old_php_code', '.pytest_cache')
$excludeFiles = @('.env', 'serein.db', '*.pyc')

Get-ChildItem -Path . -Recurse | Where-Object {
    $item = $_
    $exclude = $false
    
    foreach ($dir in $excludeDirs) {
        if ($item.FullName -like "*\$dir\*" -or $item.Name -eq $dir) {
            $exclude = $true
            break
        }
    }
    
    foreach ($file in $excludeFiles) {
        if ($item.Name -like $file) {
            $exclude = $true
            break
        }
    }
    
    -not $exclude
} | ForEach-Object {
    $relativePath = $_.FullName.Substring((Get-Location).Path.Length + 1)
    $destPath = Join-Path $tempDir $relativePath
    $destDir = Split-Path $destPath -Parent
    
    if (-not (Test-Path $destDir)) {
        New-Item -ItemType Directory -Path $destDir -Force | Out-Null
    }
    
    if (-not $_.PSIsContainer) {
        Copy-Item $_.FullName -Destination $destPath -Force
    }
}

# Use SCP to transfer
Write-Host "Uploading files via SCP..." -ForegroundColor Yellow
scp -P $SERVER_PORT -r "$tempDir\*" "$SERVER_USER@$SERVER_IP`:$DEPLOY_PATH/"

# Clean up temp directory
Remove-Item -Path $tempDir -Recurse -Force
Write-Host "✓ Files transferred" -ForegroundColor Green
Write-Host ""

# Step 4: Setup Python environment
Write-Host "Step 4: Setting up Python environment..." -ForegroundColor Green
ssh -p $SERVER_PORT "$SERVER_USER@$SERVER_IP" @"
cd $DEPLOY_PATH
python3 --version
if [ ! -d '.venv' ]; then
    python3 -m venv .venv
    echo '✓ Virtual environment created'
else
    echo '✓ Virtual environment already exists'
fi
source .venv/bin/activate
pip install --upgrade pip
pip install -r requirements.txt
pip install gunicorn
echo '✓ Python dependencies installed'
"@
Write-Host ""

# Step 5: Copy environment file
Write-Host "Step 5: Setting up environment configuration..." -ForegroundColor Green
scp -P $SERVER_PORT "deploy\production.env" "$SERVER_USER@$SERVER_IP`:$DEPLOY_PATH/.env"
Write-Host "✓ Environment file copied" -ForegroundColor Green
Write-Host ""

# Step 6: Setup PostgreSQL
Write-Host "Step 6: Setting up PostgreSQL database..." -ForegroundColor Green
ssh -p $SERVER_PORT "$SERVER_USER@$SERVER_IP" @"
if ! command -v psql &> /dev/null; then
    echo 'Installing PostgreSQL...'
    apt update
    apt install -y postgresql postgresql-contrib
    systemctl start postgresql
    systemctl enable postgresql
fi

sudo -u postgres psql << 'EOF'
DROP DATABASE IF EXISTS $DB_NAME;
DROP USER IF EXISTS $DB_USER;
CREATE DATABASE $DB_NAME;
CREATE USER $DB_USER WITH PASSWORD '$DB_PASSWORD';
GRANT ALL PRIVILEGES ON DATABASE $DB_NAME TO $DB_USER;
\c $DB_NAME
GRANT ALL ON SCHEMA public TO $DB_USER;
ALTER DATABASE $DB_NAME OWNER TO $DB_USER;
EOF

echo '✓ PostgreSQL database configured'
"@
Write-Host ""

# Step 7: Run database migrations
Write-Host "Step 7: Running database migrations..." -ForegroundColor Green
ssh -p $SERVER_PORT "$SERVER_USER@$SERVER_IP" @"
cd $DEPLOY_PATH
source .venv/bin/activate
alembic upgrade head
echo '✓ Database migrations completed'
"@
Write-Host ""

# Step 8: Setup systemd service
Write-Host "Step 8: Setting up systemd service..." -ForegroundColor Green
scp -P $SERVER_PORT "deploy\serein-blog.service" "$SERVER_USER@$SERVER_IP`:/tmp/"
ssh -p $SERVER_PORT "$SERVER_USER@$SERVER_IP" @"
mv /tmp/serein-blog.service /etc/systemd/system/
systemctl daemon-reload
systemctl enable serein-blog
systemctl restart serein-blog
sleep 3
systemctl status serein-blog --no-pager || true
echo '✓ Systemd service configured'
"@
Write-Host ""

# Step 9: Setup Nginx
Write-Host "Step 9: Setting up Nginx..." -ForegroundColor Green
ssh -p $SERVER_PORT "$SERVER_USER@$SERVER_IP" @"
if ! command -v nginx &> /dev/null; then
    echo 'Installing Nginx...'
    apt update
    apt install -y nginx
fi
"@

scp -P $SERVER_PORT "deploy\nginx-serein-blog.conf" "$SERVER_USER@$SERVER_IP`:/tmp/"
ssh -p $SERVER_PORT "$SERVER_USER@$SERVER_IP" @"
mv /tmp/nginx-serein-blog.conf /etc/nginx/sites-available/serein-blog
ln -sf /etc/nginx/sites-available/serein-blog /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl restart nginx
systemctl enable nginx
echo '✓ Nginx configured'
"@
Write-Host ""

# Step 10: Configure firewall
Write-Host "Step 10: Configuring firewall..." -ForegroundColor Green
ssh -p $SERVER_PORT "$SERVER_USER@$SERVER_IP" @"
if command -v ufw &> /dev/null; then
    ufw allow 80/tcp
    ufw allow 443/tcp
    ufw allow 2012/tcp
    echo '✓ Firewall rules added'
else
    echo '⚠ UFW not installed, skipping firewall configuration'
fi
"@
Write-Host ""

# Step 11: Final checks
Write-Host "Step 11: Running final checks..." -ForegroundColor Green
ssh -p $SERVER_PORT "$SERVER_USER@$SERVER_IP" @"
echo 'Checking service status...'
systemctl is-active serein-blog && echo '✓ Serein Blog service is running' || echo '✗ Service is not running'
echo ''
echo 'Checking if application is responding...'
curl -s http://localhost:8000/health | grep -q 'healthy' && echo '✓ Application is responding' || echo '✗ Application is not responding'
echo ''
echo 'Checking Nginx...'
systemctl is-active nginx && echo '✓ Nginx is running' || echo '✗ Nginx is not running'
"@
Write-Host ""

Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "Deployment Complete!" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Your application should now be accessible at:" -ForegroundColor Green
Write-Host "  http://103.9.205.28" -ForegroundColor Yellow
Write-Host ""
Write-Host "API Documentation:" -ForegroundColor Green
Write-Host "  http://103.9.205.28/api/docs" -ForegroundColor Yellow
Write-Host ""
Write-Host "Admin Panel:" -ForegroundColor Green
Write-Host "  http://103.9.205.28/admin" -ForegroundColor Yellow
Write-Host ""
Write-Host "Useful commands:" -ForegroundColor Green
Write-Host "  View logs: ssh -p $SERVER_PORT $SERVER_USER@$SERVER_IP 'journalctl -u serein-blog -f'" -ForegroundColor Gray
Write-Host "  Restart app: ssh -p $SERVER_PORT $SERVER_USER@$SERVER_IP 'systemctl restart serein-blog'" -ForegroundColor Gray
Write-Host "  Check status: ssh -p $SERVER_PORT $SERVER_USER@$SERVER_IP 'systemctl status serein-blog'" -ForegroundColor Gray
Write-Host ""
