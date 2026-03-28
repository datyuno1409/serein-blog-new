#!/bin/bash

# Serein Blog Server Setup Script
# Run this script ON THE SERVER after files are transferred

set -e  # Exit on error

echo "=========================================="
echo "Serein Blog - Server Setup"
echo "=========================================="
echo ""

# Configuration
DEPLOY_PATH="/var/www/serein-blog"
DB_NAME="serein_db"
DB_USER="serein_user"
DB_PASSWORD="SereinBlog2310"

cd $DEPLOY_PATH

# Step 1: Setup Python environment
echo "Step 1: Setting up Python environment..."
python3 --version

if [ ! -d ".venv" ]; then
    python3 -m venv .venv
    echo "✓ Virtual environment created"
else
    echo "✓ Virtual environment already exists"
fi

source .venv/bin/activate
pip install --upgrade pip
pip install -r requirements.txt
pip install gunicorn
echo "✓ Python dependencies installed"
echo ""

# Step 2: Setup environment file
echo "Step 2: Setting up environment configuration..."
if [ -f "deploy/production.env" ]; then
    cp deploy/production.env .env
    echo "✓ Environment file configured"
else
    echo "⚠ Warning: deploy/production.env not found"
fi
echo ""

# Step 3: Setup PostgreSQL
echo "Step 3: Setting up PostgreSQL database..."
if ! command -v psql &> /dev/null; then
    echo "Installing PostgreSQL..."
    apt update
    apt install -y postgresql postgresql-contrib
    systemctl start postgresql
    systemctl enable postgresql
fi

# Create database and user
sudo -u postgres psql << EOF
DROP DATABASE IF EXISTS $DB_NAME;
DROP USER IF EXISTS $DB_USER;
CREATE DATABASE $DB_NAME;
CREATE USER $DB_USER WITH PASSWORD '$DB_PASSWORD';
GRANT ALL PRIVILEGES ON DATABASE $DB_NAME TO $DB_USER;
\c $DB_NAME
GRANT ALL ON SCHEMA public TO $DB_USER;
ALTER DATABASE $DB_NAME OWNER TO $DB_USER;
EOF

echo "✓ PostgreSQL database configured"
echo ""

# Step 4: Run database migrations
echo "Step 4: Running database migrations..."
source .venv/bin/activate
alembic upgrade head
echo "✓ Database migrations completed"
echo ""

# Step 5: Create admin user (if script exists)
echo "Step 5: Creating admin user..."
if [ -f "scripts/create_admin.py" ]; then
    export ADMIN_USERNAME="admin"
    export ADMIN_PASSWORD="ChangeMeAc@2310"
    python scripts/create_admin.py || echo "⚠ Admin creation failed or already exists"
else
    echo "⚠ Admin creation script not found, skipping..."
fi
echo ""

# Step 6: Setup systemd service
echo "Step 6: Setting up systemd service..."
if [ -f "deploy/serein-blog.service" ]; then
    cp deploy/serein-blog.service /etc/systemd/system/
    systemctl daemon-reload
    systemctl enable serein-blog
    systemctl restart serein-blog
    sleep 3
    systemctl status serein-blog --no-pager || true
    echo "✓ Systemd service configured"
else
    echo "⚠ Service file not found"
fi
echo ""

# Step 7: Setup Nginx
echo "Step 7: Setting up Nginx..."
if ! command -v nginx &> /dev/null; then
    echo "Installing Nginx..."
    apt update
    apt install -y nginx
fi

if [ -f "deploy/nginx-serein-blog.conf" ]; then
    cp deploy/nginx-serein-blog.conf /etc/nginx/sites-available/serein-blog
    ln -sf /etc/nginx/sites-available/serein-blog /etc/nginx/sites-enabled/
    rm -f /etc/nginx/sites-enabled/default
    nginx -t
    systemctl restart nginx
    systemctl enable nginx
    echo "✓ Nginx configured"
else
    echo "⚠ Nginx config file not found"
fi
echo ""

# Step 8: Configure firewall
echo "Step 8: Configuring firewall..."
if command -v ufw &> /dev/null; then
    ufw allow 80/tcp
    ufw allow 443/tcp
    ufw allow 2012/tcp
    echo "✓ Firewall rules added"
else
    echo "⚠ UFW not installed, skipping firewall configuration"
fi
echo ""

# Step 9: Final checks
echo "Step 9: Running final checks..."
echo "Checking service status..."
systemctl is-active serein-blog && echo "✓ Serein Blog service is running" || echo "✗ Service is not running"

echo ""
echo "Checking if application is responding..."
sleep 2
curl -s http://localhost:8000/health | grep -q "healthy" && echo "✓ Application is responding" || echo "✗ Application is not responding"

echo ""
echo "Checking Nginx..."
systemctl is-active nginx && echo "✓ Nginx is running" || echo "✗ Nginx is not running"
echo ""

echo "=========================================="
echo "Server Setup Complete!"
echo "=========================================="
echo ""
echo "Your application should now be accessible at:"
echo "  http://103.9.205.28"
echo "  http://103.9.205.28/api/docs"
echo "  http://103.9.205.28/admin"
echo ""
echo "Useful commands:"
echo "  View logs: journalctl -u serein-blog -f"
echo "  Restart app: systemctl restart serein-blog"
echo "  Check status: systemctl status serein-blog"
echo ""
