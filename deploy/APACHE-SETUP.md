# Apache Configuration for Serein Blog

## Install Apache on Ubuntu/Debian

```bash
sudo apt update
sudo apt install apache2
sudo apt install libapache2-mod-wsgi-py3
```

## Enable Required Modules

```bash
sudo a2enmod proxy
sudo a2enmod proxy_http
sudo a2enmod headers
sudo a2enmod rewrite
sudo systemctl restart apache2
```

## Virtual Host Configuration

Create file: `/etc/apache2/sites-available/serein-blog.conf`

```apache
<VirtualHost *:80>
    ServerName serein.io.vn
    ServerAdmin admin@serein.io.vn

    # Proxy to FastAPI application
    ProxyPreserveHost On
    ProxyPass / http://127.0.0.1:8000/
    ProxyPassReverse / http://127.0.0.1:8000/

    # Static files (optional - serve directly from Apache)
    Alias /assets /var/www/serein-blog/assets
    <Directory /var/www/serein-blog/assets>
        Require all granted
    </Directory>

    # Logging
    ErrorLog ${APACHE_LOG_DIR}/serein-blog-error.log
    CustomLog ${APACHE_LOG_DIR}/serein-blog-access.log combined

    # Security headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
</VirtualHost>
```

## SSL Configuration (HTTPS)

Create file: `/etc/apache2/sites-available/serein-blog-ssl.conf`

```apache
<VirtualHost *:443>
    ServerName serein.io.vn
    ServerAdmin admin@serein.io.vn

    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/serein.io.vn/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/serein.io.vn/privkey.pem

    # Proxy to FastAPI
    ProxyPreserveHost On
    ProxyPass / http://127.0.0.1:8000/
    ProxyPassReverse / http://127.0.0.1:8000/

    # Static files
    Alias /assets /var/www/serein-blog/assets
    <Directory /var/www/serein-blog/assets>
        Require all granted
    </Directory>

    # Logging
    ErrorLog ${APACHE_LOG_DIR}/serein-blog-ssl-error.log
    CustomLog ${APACHE_LOG_DIR}/serein-blog-ssl-access.log combined

    # Security headers
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
</VirtualHost>

# Redirect HTTP to HTTPS
<VirtualHost *:80>
    ServerName serein.io.vn
    Redirect permanent / https://serein.io.vn/
</VirtualHost>
```

## Enable Sites

```bash
# Enable the site
sudo a2ensite serein-blog.conf

# For SSL (after obtaining certificate)
sudo a2ensite serein-blog-ssl.conf

# Disable default site
sudo a2dissite 000-default.conf

# Test configuration
sudo apache2ctl configtest

# Restart Apache
sudo systemctl restart apache2
```

## Obtain SSL Certificate (Let's Encrypt)

```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache

# Obtain certificate
sudo certbot --apache -d serein.io.vn

# Auto-renewal is configured automatically
# Test renewal
sudo certbot renew --dry-run
```

## Deployment Script with Apache

```bash
#!/bin/bash
# deploy-apache.sh

# Stop Apache
sudo systemctl stop apache2

# Update application
cd /var/www/serein-blog
git pull origin main

# Install dependencies
source venv/bin/activate
pip install -r requirements.txt

# Run migrations
alembic upgrade head

# Restart FastAPI service
sudo systemctl restart serein-blog

# Start Apache
sudo systemctl start apache2

echo "✅ Deployment complete!"
```

## Monitoring

```bash
# Check Apache status
sudo systemctl status apache2

# View logs
sudo tail -f /var/log/apache2/serein-blog-error.log
sudo tail -f /var/log/apache2/serein-blog-access.log

# Check FastAPI backend
sudo systemctl status serein-blog
sudo journalctl -u serein-blog -f
```

## Performance Tuning

Add to Apache config:

```apache
# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json
</IfModule>

# Enable caching for static files
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

## Firewall Configuration

```bash
# Allow HTTP and HTTPS
sudo ufw allow 'Apache Full'

# Check status
sudo ufw status
```
