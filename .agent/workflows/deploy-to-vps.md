---
description: Deploy Serein Blog to VPS Server
---

# Deploy Serein Blog Platform to VPS

This workflow guides you through deploying the Serein Blog Platform to your VPS server.

## Server Information
- **IP**: 103.9.205.28
- **Port**: 2012
- **User**: root (assumed)

## Prerequisites
- SSH access to the server
- Python 3.9+ installed on server
- PostgreSQL installed on server
- Nginx installed on server

## Deployment Steps

### 1. Test SSH Connection
```bash
ssh -p 2012 root@103.9.205.28
```

### 2. Prepare Local Files for Transfer
Create a deployment package excluding unnecessary files:
```bash
# Create a temporary deployment directory
mkdir -p deploy-temp
# Copy necessary files
robocopy . deploy-temp /E /XD .venv __pycache__ .git node_modules old_php_code /XF .env serein.db *.pyc
```

### 3. Transfer Files to Server
```bash
# Using SCP to transfer files
scp -P 2012 -r . root@103.9.205.28:/var/www/serein-blog/
```

### 4. SSH into Server and Setup
```bash
ssh -p 2012 root@103.9.205.28
```

Then on the server:
```bash
# Navigate to project directory
cd /var/www/serein-blog

# Create virtual environment
python3 -m venv .venv
source .venv/bin/activate

# Install dependencies
pip install -r requirements.txt
pip install gunicorn

# Create .env file
nano .env
```

### 5. Configure Environment Variables
Add the following to `.env`:
```env
ENVIRONMENT=production
DATABASE_URL=postgresql://postgres:YOUR_DB_PASSWORD@localhost:5432/serein_db
SECRET_KEY=YOUR_SECURE_SECRET_KEY_HERE
ALGORITHM=HS256
ACCESS_TOKEN_EXPIRE_MINUTES=30
ALLOWED_ORIGINS=http://103.9.205.28,https://yourdomain.com
UPLOAD_FOLDER=uploads
MAX_UPLOAD_SIZE=5242880
ADMIN_EMAIL=admin@serein.com
```

### 6. Setup PostgreSQL Database
```bash
# Switch to postgres user
sudo -u postgres psql

# In PostgreSQL prompt:
CREATE DATABASE serein_db;
CREATE USER serein_user WITH PASSWORD 'your_secure_password';
GRANT ALL PRIVILEGES ON DATABASE serein_db TO serein_user;
\q
```

### 7. Run Database Migrations
```bash
# Activate virtual environment if not already
source .venv/bin/activate

# Run migrations
alembic upgrade head

# Create admin user
python scripts/create_admin.py
```

### 8. Create Systemd Service
```bash
sudo nano /etc/systemd/system/serein-blog.service
```

Add this content:
```ini
[Unit]
Description=Serein Blog Platform
After=network.target

[Service]
User=root
Group=root
WorkingDirectory=/var/www/serein-blog
Environment="PATH=/var/www/serein-blog/.venv/bin"
ExecStart=/var/www/serein-blog/.venv/bin/gunicorn main:app -w 4 -k uvicorn.workers.UvicornWorker --bind 0.0.0.0:8000

[Install]
WantedBy=multi-user.target
```

### 9. Start the Service
```bash
# Reload systemd
sudo systemctl daemon-reload

# Enable service to start on boot
sudo systemctl enable serein-blog

# Start the service
sudo systemctl start serein-blog

# Check status
sudo systemctl status serein-blog
```

### 10. Configure Nginx
```bash
sudo nano /etc/nginx/sites-available/serein-blog
```

Add this configuration:
```nginx
server {
    listen 80;
    server_name 103.9.205.28 yourdomain.com;

    client_max_body_size 10M;

    location / {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    location /assets {
        alias /var/www/serein-blog/assets;
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    location /admin {
        alias /var/www/serein-blog/admin;
    }
}
```

### 11. Enable Nginx Site
```bash
# Create symbolic link
sudo ln -s /etc/nginx/sites-available/serein-blog /etc/nginx/sites-enabled/

# Test nginx configuration
sudo nginx -t

# Restart nginx
sudo systemctl restart nginx
```

### 12. Configure Firewall (if UFW is enabled)
```bash
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 2012/tcp
sudo ufw reload
```

### 13. Test Deployment
Visit: http://103.9.205.28

### 14. Setup SSL (Optional but Recommended)
```bash
# Install certbot
sudo apt install certbot python3-certbot-nginx

# Get SSL certificate
sudo certbot --nginx -d yourdomain.com
```

## Useful Commands

### View Application Logs
```bash
sudo journalctl -u serein-blog -f
```

### Restart Application
```bash
sudo systemctl restart serein-blog
```

### Update Application
```bash
cd /var/www/serein-blog
git pull  # if using git
source .venv/bin/activate
pip install -r requirements.txt
alembic upgrade head
sudo systemctl restart serein-blog
```

### Check Application Status
```bash
sudo systemctl status serein-blog
```

## Troubleshooting

### If service fails to start:
```bash
# Check logs
sudo journalctl -u serein-blog -n 50

# Check if port 8000 is in use
sudo netstat -tulpn | grep 8000

# Test gunicorn manually
cd /var/www/serein-blog
source .venv/bin/activate
gunicorn main:app -w 4 -k uvicorn.workers.UvicornWorker --bind 0.0.0.0:8000
```

### Database connection issues:
```bash
# Test database connection
psql -U serein_user -d serein_db -h localhost

# Check PostgreSQL status
sudo systemctl status postgresql
```
