# Deployment Files for Serein Blog Platform

This directory contains all necessary files and scripts for deploying the Serein Blog Platform to a VPS server.

## 📁 Files Overview

- **`deploy.ps1`** - Automated deployment script for Windows (PowerShell)
- **`deploy.sh`** - Automated deployment script for Linux/Mac (Bash)
- **`production.env`** - Production environment configuration
- **`serein-blog.service`** - Systemd service file
- **`nginx-serein-blog.conf`** - Nginx reverse proxy configuration

## 🎯 Server Information

- **IP Address**: 103.9.205.28
- **SSH Port**: 2012
- **SSH User**: root
- **Password**: Next-Step@2310
- **Deploy Path**: /var/www/serein-blog

## 🚀 Quick Deployment

### For Windows Users:

```powershell
# Navigate to project directory
cd d:\serein-blog-new

# Run deployment script
.\deploy\deploy.ps1
```

### For Linux/Mac Users:

```bash
# Navigate to project directory
cd /path/to/serein-blog-new

# Make script executable
chmod +x deploy/deploy.sh

# Run deployment script
./deploy/deploy.sh
```

## 📋 What the Script Does

The automated deployment script performs the following steps:

1. **Tests SSH Connection** - Verifies connectivity to the server
2. **Creates Deployment Directory** - Sets up `/var/www/serein-blog`
3. **Transfers Files** - Uploads project files (excluding .venv, __pycache__, etc.)
4. **Sets Up Python Environment** - Creates virtual environment and installs dependencies
5. **Configures Environment** - Copies production.env as .env
6. **Sets Up PostgreSQL** - Creates database and user
7. **Runs Migrations** - Applies database schema using Alembic
8. **Creates Admin User** - Runs admin creation script (if available)
9. **Configures Systemd Service** - Sets up auto-start service
10. **Configures Nginx** - Sets up reverse proxy
11. **Configures Firewall** - Opens necessary ports (80, 443, 2012)
12. **Runs Health Checks** - Verifies deployment success

## 🔧 Manual Deployment

If you prefer to deploy manually, follow these steps:

### 1. Connect to Server

```bash
ssh -p 2012 root@103.9.205.28
```

### 2. Create Directory

```bash
mkdir -p /var/www/serein-blog
```

### 3. Transfer Files (from local machine)

```bash
# Using SCP
scp -P 2012 -r . root@103.9.205.28:/var/www/serein-blog/

# Or using rsync (recommended)
rsync -avz --progress \
    --exclude='.venv' \
    --exclude='__pycache__' \
    --exclude='.git' \
    --exclude='serein.db' \
    -e "ssh -p 2012" \
    ./ root@103.9.205.28:/var/www/serein-blog/
```

### 4. Setup Python Environment (on server)

```bash
cd /var/www/serein-blog
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
pip install gunicorn
```

### 5. Configure Environment

```bash
# Copy the production env file
cp deploy/production.env .env

# Edit if needed
nano .env
```

### 6. Setup PostgreSQL

```bash
# Install PostgreSQL (if not installed)
apt update
apt install -y postgresql postgresql-contrib

# Create database
sudo -u postgres psql << EOF
CREATE DATABASE serein_db;
CREATE USER serein_user WITH PASSWORD 'SereinBlog@2310';
GRANT ALL PRIVILEGES ON DATABASE serein_db TO serein_user;
\c serein_db
GRANT ALL ON SCHEMA public TO serein_user;
ALTER DATABASE serein_db OWNER TO serein_user;
EOF
```

### 7. Run Migrations

```bash
cd /var/www/serein-blog
source .venv/bin/activate
alembic upgrade head
```

### 8. Setup Systemd Service

```bash
# Copy service file
cp deploy/serein-blog.service /etc/systemd/system/

# Enable and start service
systemctl daemon-reload
systemctl enable serein-blog
systemctl start serein-blog

# Check status
systemctl status serein-blog
```

### 9. Setup Nginx

```bash
# Install Nginx (if not installed)
apt install -y nginx

# Copy configuration
cp deploy/nginx-serein-blog.conf /etc/nginx/sites-available/serein-blog

# Enable site
ln -s /etc/nginx/sites-available/serein-blog /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Test and restart
nginx -t
systemctl restart nginx
```

### 10. Configure Firewall

```bash
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 2012/tcp
```

## 🌐 Access Your Application

After successful deployment:

- **Main Site**: http://103.9.205.28
- **API Docs**: http://103.9.205.28/api/docs
- **Admin Panel**: http://103.9.205.28/admin
- **Health Check**: http://103.9.205.28/health

## 🔍 Troubleshooting

### Check Application Logs

```bash
ssh -p 2012 root@103.9.205.28 'journalctl -u serein-blog -f'
```

### Check Service Status

```bash
ssh -p 2012 root@103.9.205.28 'systemctl status serein-blog'
```

### Restart Application

```bash
ssh -p 2012 root@103.9.205.28 'systemctl restart serein-blog'
```

### Check Nginx Logs

```bash
ssh -p 2012 root@103.9.205.28 'tail -f /var/log/nginx/serein-blog-error.log'
```

### Test Database Connection

```bash
ssh -p 2012 root@103.9.205.28
cd /var/www/serein-blog
source .venv/bin/activate
python -c "from backend.database import engine; print('Database connection:', engine.connect())"
```

### Manual Application Start (for debugging)

```bash
ssh -p 2012 root@103.9.205.28
cd /var/www/serein-blog
source .venv/bin/activate
gunicorn main:app -w 4 -k uvicorn.workers.UvicornWorker --bind 0.0.0.0:8000
```

## 🔄 Updating the Application

To update after making changes:

```bash
# From local machine
cd d:\serein-blog-new

# Transfer updated files
scp -P 2012 -r . root@103.9.205.28:/var/www/serein-blog/

# On server
ssh -p 2012 root@103.9.205.28 << 'EOF'
cd /var/www/serein-blog
source .venv/bin/activate
pip install -r requirements.txt
alembic upgrade head
systemctl restart serein-blog
EOF
```

## 🔐 Security Recommendations

1. **Change Default Passwords**: Update database password in production.env
2. **Generate New SECRET_KEY**: Use a strong, random secret key
3. **Setup SSL/TLS**: Install Let's Encrypt certificate
4. **Configure Firewall**: Limit SSH access to specific IPs
5. **Regular Updates**: Keep system and dependencies updated
6. **Backup Database**: Set up automated PostgreSQL backups

## 📝 Environment Variables

Key environment variables in `production.env`:

- `ENVIRONMENT=production` - Sets production mode
- `DATABASE_URL` - PostgreSQL connection string
- `SECRET_KEY` - JWT signing key (CHANGE THIS!)
- `ALLOWED_ORIGINS` - CORS allowed origins
- `ADMIN_EMAIL` - Default admin email

## 📞 Support

For issues or questions:
- Check logs: `journalctl -u serein-blog -f`
- Review Nginx logs: `/var/log/nginx/serein-blog-error.log`
- Test database connection
- Verify environment variables

---

**Made with ❤️ for easy deployment**
