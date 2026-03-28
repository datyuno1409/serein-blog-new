# Serein Blog - Simple Deployment Script for Windows
# Server: 103.9.205.28:2012

Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "Serein Blog Platform - Deployment" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

$SERVER = "root@103.9.205.28"
$PORT = "2012"

Write-Host "Step 1: Testing SSH connection..." -ForegroundColor Green
ssh -p $PORT $SERVER "echo 'Connected successfully!'"
if ($LASTEXITCODE -ne 0) {
    Write-Host "Failed to connect. Please check your SSH credentials." -ForegroundColor Red
    exit 1
}
Write-Host ""

Write-Host "Step 2: Creating deployment directory..." -ForegroundColor Green
ssh -p $PORT $SERVER "mkdir -p /var/www/serein-blog"
Write-Host ""

Write-Host "Step 3: Transferring files (this may take a few minutes)..." -ForegroundColor Green
scp -P $PORT -r deploy ${SERVER}:/var/www/serein-blog/
scp -P $PORT -r backend ${SERVER}:/var/www/serein-blog/
scp -P $PORT -r api ${SERVER}:/var/www/serein-blog/
scp -P $PORT -r models ${SERVER}:/var/www/serein-blog/
scp -P $PORT -r alembic ${SERVER}:/var/www/serein-blog/
scp -P $PORT -r assets ${SERVER}:/var/www/serein-blog/
scp -P $PORT -r admin ${SERVER}:/var/www/serein-blog/
scp -P $PORT -r scripts ${SERVER}:/var/www/serein-blog/
scp -P $PORT *.html ${SERVER}:/var/www/serein-blog/ 2>$null
scp -P $PORT *.py ${SERVER}:/var/www/serein-blog/
scp -P $PORT *.txt ${SERVER}:/var/www/serein-blog/
scp -P $PORT *.ini ${SERVER}:/var/www/serein-blog/
scp -P $PORT Procfile ${SERVER}:/var/www/serein-blog/ 2>$null
Write-Host "Files transferred successfully" -ForegroundColor Green
Write-Host ""

Write-Host "Step 4: Running server setup script..." -ForegroundColor Green
Write-Host "This will install dependencies and configure the server..." -ForegroundColor Yellow
ssh -p $PORT $SERVER "bash /var/www/serein-blog/deploy/deploy.sh"

Write-Host ""
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "Deployment Complete!" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Access your application at:" -ForegroundColor Green
Write-Host "  http://103.9.205.28" -ForegroundColor Yellow
Write-Host "  http://103.9.205.28/api/docs" -ForegroundColor Yellow
Write-Host ""
