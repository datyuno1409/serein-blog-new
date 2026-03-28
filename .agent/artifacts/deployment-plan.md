# Deployment Plan for Serein Blog Platform

## Overview
Deploy Serein Blog Platform to VPS with custom domain **serein.io.vn**

## Server Information
- **IP**: 103.9.205.28
- **Port**: 2012
- **Domain**: serein.io.vn (will point to 103.9.205.28)
- **User**: root
- **Password**: Next-Step@2310

## Tasks

### Task 1: Update Configuration Files for Domain
- [x] Update production.env with domain in ALLOWED_ORIGINS
- [x] Update Nginx config to use serein.io.vn as server_name
- [x] Update deployment documentation

### Task 2: DNS Configuration (User Action Required)
- [ ] Point serein.io.vn A record to 103.9.205.28
- [ ] Wait for DNS propagation (can take 5-60 minutes)

### Task 3: Deploy Application
- [ ] Transfer files to server
- [ ] Setup Python environment
- [ ] Configure PostgreSQL database
- [ ] Run database migrations
- [ ] Setup systemd service
- [ ] Configure Nginx

### Task 4: SSL Certificate Setup
- [ ] Install Certbot
- [ ] Obtain Let's Encrypt SSL certificate for serein.io.vn
- [ ] Configure auto-renewal

## Expected URLs After Deployment
- **Main Site**: https://serein.io.vn
- **API Docs**: https://serein.io.vn/api/docs
- **Admin Panel**: https://serein.io.vn/admin
- **Health Check**: https://serein.io.vn/health

## Notes
- HTTP (port 80) will redirect to HTTPS (port 443)
- IP access (http://103.9.205.28) will also work but domain is preferred
- SSL certificate is free via Let's Encrypt
