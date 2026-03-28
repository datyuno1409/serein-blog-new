#!/bin/bash
# Apache Deployment Script for Serein Blog

set -e  # Exit on error

echo "========================================="
echo "Serein Blog - Apache Deployment"
echo "========================================="

# Configuration
APP_DIR="/var/www/serein-blog"
VENV_DIR="$APP_DIR/venv"
SERVICE_NAME="serein-blog"
DOMAIN="serein.io.vn"

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Functions
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    log_error "Please run as root (use sudo)"
    exit 1
fi

# Step 1: Stop services
log_info "Stopping services..."
systemctl stop apache2
systemctl stop $SERVICE_NAME || true

# Step 2: Backup current deployment
log_info "Creating backup..."
BACKUP_DIR="/var/backups/serein-blog-$(date +%Y%m%d-%H%M%S)"
mkdir -p $BACKUP_DIR
cp -r $APP_DIR $BACKUP_DIR/ || log_warn "Backup failed, continuing..."

# Step 3: Pull latest code
log_info "Pulling latest code..."
cd $APP_DIR
git pull origin main

# Step 4: Install/Update dependencies
log_info "Installing dependencies..."
source $VENV_DIR/bin/activate
pip install --upgrade pip
pip install -r requirements.txt

# Step 5: Run database migrations
log_info "Running database migrations..."
alembic upgrade head

# Step 6: Create/Update admin user
log_info "Setting up admin user..."
export ADMIN_USERNAME="${ADMIN_USERNAME:-admin}"
export ADMIN_PASSWORD="${ADMIN_PASSWORD:-admin}"
python scripts/create_admin.py || log_warn "Admin user setup failed"

# Step 7: Set permissions
log_info "Setting permissions..."
chown -R www-data:www-data $APP_DIR
chmod -R 755 $APP_DIR

# Step 8: Restart FastAPI service
log_info "Restarting FastAPI service..."
systemctl restart $SERVICE_NAME
systemctl enable $SERVICE_NAME

# Wait for service to start
sleep 3

# Check service status
if systemctl is-active --quiet $SERVICE_NAME; then
    log_info "FastAPI service is running"
else
    log_error "FastAPI service failed to start"
    systemctl status $SERVICE_NAME
    exit 1
fi

# Step 9: Restart Apache
log_info "Restarting Apache..."
systemctl restart apache2

# Check Apache status
if systemctl is-active --quiet apache2; then
    log_info "Apache is running"
else
    log_error "Apache failed to start"
    systemctl status apache2
    exit 1
fi

# Step 10: Test endpoints
log_info "Testing endpoints..."
sleep 2

# Test health endpoint
if curl -f http://localhost:8000/health > /dev/null 2>&1; then
    log_info "Health check passed"
else
    log_error "Health check failed"
fi

# Test through Apache
if curl -f http://localhost/ > /dev/null 2>&1; then
    log_info "Apache proxy working"
else
    log_warn "Apache proxy test failed"
fi

echo ""
echo "========================================="
log_info "Deployment completed successfully!"
echo "========================================="
echo ""
echo "Service Status:"
systemctl status $SERVICE_NAME --no-pager | head -n 5
echo ""
systemctl status apache2 --no-pager | head -n 5
echo ""
echo "Access your site at: http://$DOMAIN"
echo "Admin panel: http://$DOMAIN/admin"
echo ""
echo "Logs:"
echo "  FastAPI: sudo journalctl -u $SERVICE_NAME -f"
echo "  Apache: sudo tail -f /var/log/apache2/serein-blog-error.log"
echo ""
