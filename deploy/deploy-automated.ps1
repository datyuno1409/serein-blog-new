# Automated Deployment Script for Serein Blog
# This script will deploy to 103.9.205.28:2012

$ErrorActionPreference = "Stop"

Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "Serein Blog - Automated Deployment" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

# Configuration
$SERVER_IP = "103.9.205.28"
$SERVER_PORT = "2012"
$SERVER_USER = "root"
$SERVER_PASSWORD = "Next-Step@2310"
$DEPLOY_PATH = "/var/www/serein-blog"
$LOCAL_PATH = "d:\serein-blog-new"

# Check if plink and pscp are available (PuTTY tools)
$plinkPath = "plink"
$pscpPath = "pscp"

Write-Host "Step 1: Checking prerequisites..." -ForegroundColor Yellow
try {
    $null = Get-Command $plinkPath -ErrorAction Stop
    $null = Get-Command $pscpPath -ErrorAction Stop
    Write-Host "✓ PuTTY tools found" -ForegroundColor Green
}
catch {
    Write-Host "✗ PuTTY tools not found. Installing..." -ForegroundColor Red
    Write-Host "Please install PuTTY from: https://www.putty.org/" -ForegroundColor Yellow
    Write-Host "Or use: winget install PuTTY.PuTTY" -ForegroundColor Yellow
    
    # Try to install via winget
    try {
        winget install PuTTY.PuTTY --silent --accept-package-agreements --accept-source-agreements
        Write-Host "✓ PuTTY installed" -ForegroundColor Green
        # Update paths
        $plinkPath = "C:\Program Files\PuTTY\plink.exe"
        $pscpPath = "C:\Program Files\PuTTY\pscp.exe"
    }
    catch {
        Write-Host "✗ Failed to install PuTTY automatically" -ForegroundColor Red
        Write-Host "Using alternative method with SSH..." -ForegroundColor Yellow
        $useSsh = $true
    }
}

Write-Host ""
Write-Host "Step 2: Creating deployment package..." -ForegroundColor Yellow

# Create temporary directory for deployment
$tempDir = Join-Path $env:TEMP "serein-deploy-$(Get-Date -Format 'yyyyMMdd-HHmmss')"
New-Item -ItemType Directory -Path $tempDir -Force | Out-Null

# Copy files excluding unnecessary directories
$excludeDirs = @('.venv', '__pycache__', '.git', 'node_modules', 'old_php_code', '.pytest_cache')
$excludeFiles = @('*.pyc', '*.pyo', '*.pyd', '.DS_Store', 'serein.db')

Write-Host "Copying files to temporary directory..." -ForegroundColor Gray
robocopy $LOCAL_PATH $tempDir /E /XD $excludeDirs /XF $excludeFiles .env /NFL /NDL /NJH /NJS | Out-Null

Write-Host "✓ Deployment package created" -ForegroundColor Green
Write-Host ""

Write-Host "Step 3: Transferring files to server..." -ForegroundColor Yellow
Write-Host "Server: $SERVER_USER@$SERVER_IP:$SERVER_PORT" -ForegroundColor Gray
Write-Host "Target: $DEPLOY_PATH" -ForegroundColor Gray

# Create a script to run on the server
$serverScript = @"
#!/bin/bash
set -e

echo "Creating deployment directory..."
mkdir -p $DEPLOY_PATH
echo "✓ Directory created"
"@

$serverScriptPath = Join-Path $tempDir "prepare.sh"
$serverScript | Out-File -FilePath $serverScriptPath -Encoding ASCII -NoNewline

if (-not $useSsh) {
    # Use PuTTY tools
    Write-Host "Using PuTTY for file transfer..." -ForegroundColor Gray
    
    # First, accept the host key
    Write-Host "Accepting host key..." -ForegroundColor Gray
    echo "y" | & $plinkPath -P $SERVER_PORT -pw $SERVER_PASSWORD $SERVER_USER@$SERVER_IP "exit" 2>$null
    
    # Transfer files using pscp
    Write-Host "Transferring files (this may take a few minutes)..." -ForegroundColor Gray
    & $pscpPath -r -P $SERVER_PORT -pw $SERVER_PASSWORD "$tempDir\*" "${SERVER_USER}@${SERVER_IP}:${DEPLOY_PATH}/"
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✓ Files transferred successfully" -ForegroundColor Green
    }
    else {
        Write-Host "✗ File transfer failed" -ForegroundColor Red
        exit 1
    }
}
else {
    # Use OpenSSH (requires sshpass or manual password entry)
    Write-Host "Using OpenSSH for file transfer..." -ForegroundColor Gray
    Write-Host "Note: You may need to enter the password manually" -ForegroundColor Yellow
    
    # Try using scp
    scp -P $SERVER_PORT -r "$tempDir\*" "${SERVER_USER}@${SERVER_IP}:${DEPLOY_PATH}/"
}

Write-Host ""
Write-Host "Step 4: Running deployment script on server..." -ForegroundColor Yellow

# Create the deployment command
$deployCommand = @"
cd $DEPLOY_PATH && chmod +x deploy/deploy.sh && ./deploy/deploy.sh
"@

if (-not $useSsh) {
    # Execute deployment script using plink
    & $plinkPath -P $SERVER_PORT -pw $SERVER_PASSWORD -t $SERVER_USER@$SERVER_IP $deployCommand
}
else {
    # Execute using ssh
    ssh -p $SERVER_PORT -t $SERVER_USER@$SERVER_IP $deployCommand
}

Write-Host ""
Write-Host "Step 5: Verifying deployment..." -ForegroundColor Yellow

# Check if service is running
$checkCommand = "systemctl is-active serein-blog && curl -s http://localhost:8000/health"

if (-not $useSsh) {
    $result = & $plinkPath -P $SERVER_PORT -pw $SERVER_PASSWORD $SERVER_USER@$SERVER_IP $checkCommand
}
else {
    $result = ssh -p $SERVER_PORT $SERVER_USER@$SERVER_IP $checkCommand
}

Write-Host ""
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "Deployment Complete!" -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Application URLs:" -ForegroundColor Yellow
Write-Host "  Homepage:    http://103.9.205.28/" -ForegroundColor White
Write-Host "  API Docs:    http://103.9.205.28/api/docs" -ForegroundColor White
Write-Host "  Admin Panel: http://103.9.205.28/admin" -ForegroundColor White
Write-Host "  Health:      http://103.9.205.28/health" -ForegroundColor White
Write-Host ""
Write-Host "Useful Commands:" -ForegroundColor Yellow
Write-Host "  View logs:    ssh -p 2012 root@103.9.205.28 'journalctl -u serein-blog -f'" -ForegroundColor Gray
Write-Host "  Restart app:  ssh -p 2012 root@103.9.205.28 'systemctl restart serein-blog'" -ForegroundColor Gray
Write-Host "  Check status: ssh -p 2012 root@103.9.205.28 'systemctl status serein-blog'" -ForegroundColor Gray
Write-Host ""

# Cleanup
Write-Host "Cleaning up temporary files..." -ForegroundColor Gray
Remove-Item -Path $tempDir -Recurse -Force
Write-Host "✓ Cleanup complete" -ForegroundColor Green
