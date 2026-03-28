#!/bin/bash

# SSL Setup Script for Serein Blog
# Run this AFTER initial deployment and DNS is configured

set -e

echo "=========================================="
echo "Serein Blog - SSL Certificate Setup"
echo "=========================================="
echo ""

DOMAIN="serein.io.vn"
EMAIL="admin@serein.com"  # Change this to your email

echo "Domain: $DOMAIN"
echo "Email: $EMAIL"
echo ""

# Check if domain resolves to this server
echo "Step 1: Checking DNS configuration..."
SERVER_IP=$(curl -s ifconfig.me)
DOMAIN_IP=$(dig +short $DOMAIN | tail -n1)

echo "Server IP: $SERVER_IP"
echo "Domain resolves to: $DOMAIN_IP"

if [ "$SERVER_IP" != "$DOMAIN_IP" ]; then
    echo "⚠ WARNING: Domain does not point to this server!"
    echo "Please configure your DNS A record:"
    echo "  Type: A"
    echo "  Name: @"
    echo "  Value: $SERVER_IP"
    echo "  TTL: 3600"
    echo ""
    read -p "Continue anyway? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi
echo ""

# Install Certbot
echo "Step 2: Installing Certbot..."
if ! command -v certbot &> /dev/null; then
    apt update
    apt install -y certbot python3-certbot-nginx
    echo "✓ Certbot installed"
else
    echo "✓ Certbot already installed"
fi
echo ""

# Stop nginx temporarily
echo "Step 3: Preparing for certificate generation..."
systemctl stop nginx
echo ""

# Obtain SSL certificate
echo "Step 4: Obtaining SSL certificate..."
certbot certonly --standalone \
    -d $DOMAIN \
    -d www.$DOMAIN \
    --non-interactive \
    --agree-tos \
    --email $EMAIL \
    --preferred-challenges http

if [ $? -eq 0 ]; then
    echo "✓ SSL certificate obtained successfully"
else
    echo "✗ Failed to obtain SSL certificate"
    systemctl start nginx
    exit 1
fi
echo ""

# Update Nginx configuration
echo "Step 5: Updating Nginx configuration..."
if [ -f "/var/www/serein-blog/deploy/nginx-serein-blog-ssl.conf" ]; then
    cp /var/www/serein-blog/deploy/nginx-serein-blog-ssl.conf /etc/nginx/sites-available/serein-blog
    echo "✓ SSL configuration applied"
else
    echo "⚠ SSL config file not found, using certbot auto-config"
    certbot --nginx -d $DOMAIN -d www.$DOMAIN --non-interactive
fi

# Test nginx configuration
nginx -t

# Restart nginx
systemctl start nginx
echo "✓ Nginx restarted with SSL"
echo ""

# Setup auto-renewal
echo "Step 6: Setting up auto-renewal..."
systemctl enable certbot.timer
systemctl start certbot.timer
echo "✓ Auto-renewal configured"
echo ""

# Test certificate renewal
echo "Step 7: Testing certificate renewal..."
certbot renew --dry-run
echo ""

echo "=========================================="
echo "SSL Setup Complete!"
echo "=========================================="
echo ""
echo "Your site is now accessible via HTTPS:"
echo "  https://serein.io.vn"
echo "  https://www.serein.io.vn"
echo ""
echo "Certificate details:"
certbot certificates
echo ""
echo "Certificate will auto-renew before expiration."
echo "Check renewal timer: systemctl status certbot.timer"
echo ""
