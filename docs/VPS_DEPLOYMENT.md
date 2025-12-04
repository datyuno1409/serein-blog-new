# VPS Deployment Guide - Serein Blog Platform

Complete guide for deploying FastAPI application to VPS with PostgreSQL.

## Prerequisites

- VPS with Ubuntu 20.04+ (DigitalOcean, Vultr, AWS EC2, etc.)
- Domain name (optional but recommended)
- SSH access to VPS

## Step 1: Initial Server Setup

```bash
# SSH into your VPS
ssh root@your-server-ip

# Update system
sudo apt update && sudo apt upgrade -y

# Create deploy user
sudo adduser serein
sudo usermod -aG sudo serein

# Switch to deploy user
su - serein
```

## Step 2: Install Dependencies

```bash
# Install Python 3.11
sudo apt install software-properties-common -y
sudo add-apt-repository ppa:deadsnakes/ppa -y
sudo apt update
sudo apt install python3.11 python3.11-venv python3.11-dev -y

# Install PostgreSQL
sudo apt install postgresql postgresql-contrib -y

# Install Nginx
sudo apt install nginx -y

# Install Git
sudo apt install git -y
```

## Step 3: Setup PostgreSQL

```bash
# Switch to postgres user
sudo -u postgres psql

# Create database and user
CREATE DATABASE serein_db;
CREATE USER serein_user WITH PASSWORD 'your_secure_password';
GRANT ALL PRIVILEGES ON DATABASE serein_db TO serein_user;
\q
```

## Step 4: Clone and Setup Application

```bash
# Clone repository
cd /home/serein
git clone https://github.com/datyuno1409/serein-blog-new.git
cd serein-blog-new

# Create virtual environment
python3.11 -m venv venv
source venv/bin/activate

# Install dependencies
pip install --upgrade pip
pip install -r requirements.txt
pip install gunicorn
```

## Step 5: Configure Environment

```bash
# Create .env file
nano .env
```

Add the following:

```env
ENVIRONMENT=production
DATABASE_URL=postgresql://serein_user:your_secure_password@localhost:5432/serein_db
SECRET_KEY=your-very-secure-secret-key-change-this
ALGORITHM=HS256
ACCESS_TOKEN_EXPIRE_MINUTES=30
ALLOWED_ORIGINS=https://your-domain.com,https://www.your-domain.com
UPLOAD_FOLDER=uploads
MAX_UPLOAD_SIZE=5242880
ADMIN_EMAIL=admin@your-domain.com
```

## Step 6: Run Database Migrations

```bash
# Run migrations
alembic upgrade head

# Create admin user
python scripts/create_admin.py
```

## Step 7: Setup Systemd Service

```bash
# Create service file
sudo nano /etc/systemd/system/serein.service
```

Add the following:

```ini
[Unit]
Description=Serein Blog Platform
After=network.target

[Service]
User=serein
Group=www-data
WorkingDirectory=/home/serein/serein-blog-new
Environment="PATH=/home/serein/serein-blog-new/venv/bin"
ExecStart=/home/serein/serein-blog-new/venv/bin/gunicorn -w 4 -k uvicorn.workers.UvicornWorker app:app --bind 0.0.0.0:8000

[Install]
WantedBy=multi-user.target
```

```bash
# Enable and start service
sudo systemctl daemon-reload
sudo systemctl enable serein
sudo systemctl start serein
sudo systemctl status serein
```

## Step 8: Configure Nginx

```bash
# Create Nginx configuration
sudo nano /etc/nginx/sites-available/serein
```

Add the following:

```nginx
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;

    client_max_body_size 10M;

    location / {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    location /assets {
        alias /home/serein/serein-blog-new/assets;
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    location /admin/css {
        alias /home/serein/serein-blog-new/admin/css;
        expires 30d;
    }

    location /admin/js {
        alias /home/serein/serein-blog-new/admin/js;
        expires 30d;
    }
}
```

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/serein /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

## Step 9: Setup SSL with Let's Encrypt

```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx -y

# Get SSL certificate
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# Auto-renewal is configured automatically
sudo certbot renew --dry-run
```

## Step 10: Setup Firewall

```bash
# Configure UFW
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
sudo ufw status
```

## Deployment Commands

### Update Application

```bash
cd /home/serein/serein-blog-new
git pull origin main
source venv/bin/activate
pip install -r requirements.txt
alembic upgrade head
sudo systemctl restart serein
```

### View Logs

```bash
# Application logs
sudo journalctl -u serein -f

# Nginx logs
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/log/nginx/access.log
```

### Restart Services

```bash
# Restart application
sudo systemctl restart serein

# Restart Nginx
sudo systemctl restart nginx

# Restart PostgreSQL
sudo systemctl restart postgresql
```

## Monitoring

### Check Service Status

```bash
sudo systemctl status serein
sudo systemctl status nginx
sudo systemctl status postgresql
```

### Database Backup

```bash
# Backup database
pg_dump -U serein_user serein_db > backup_$(date +%Y%m%d).sql

# Restore database
psql -U serein_user serein_db < backup_20250101.sql
```

## Security Checklist

- ✅ Change default PostgreSQL password
- ✅ Use strong SECRET_KEY in .env
- ✅ Enable UFW firewall
- ✅ Setup SSL with Let's Encrypt
- ✅ Disable root SSH login
- ✅ Use SSH keys instead of passwords
- ✅ Keep system updated
- ✅ Regular database backups

## Troubleshooting

### Application won't start

```bash
# Check logs
sudo journalctl -u serein -n 50

# Check if port is in use
sudo lsof -i :8000

# Restart service
sudo systemctl restart serein
```

### Database connection error

```bash
# Check PostgreSQL status
sudo systemctl status postgresql

# Check connection
psql -U serein_user -d serein_db -h localhost
```

### Nginx 502 Bad Gateway

```bash
# Check if app is running
sudo systemctl status serein

# Check Nginx config
sudo nginx -t

# Restart both
sudo systemctl restart serein
sudo systemctl restart nginx
```

---

**Deployment complete! 🚀**

Your application should now be accessible at https://your-domain.com
