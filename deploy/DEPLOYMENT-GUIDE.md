# Serein Blog Deployment - Complete Guide

## 📋 Overview

Deploy **Serein Blog Platform** to VPS with domain **serein.io.vn**

### Server Details
- **IP**: 103.9.205.28
- **Port**: 2012
- **User**: root
- **Password**: Next-Step@2310
- **Domain**: serein.io.vn

### Final URLs
- **Main Site**: https://serein.io.vn
- **API Documentation**: https://serein.io.vn/api/docs
- **Admin Panel**: https://serein.io.vn/admin

---

## 🚀 Quick Start (3 Steps)

### Step 1: Configure DNS (Do This First!)

**Important**: Configure DNS before deployment for SSL to work properly.

See detailed instructions in: [`DNS-SETUP.md`](./DNS-SETUP.md)

**Quick version**:
1. Login to your domain registrar (where you bought serein.io.vn)
2. Add these DNS records:
   ```
   Type: A, Name: @, Value: 103.9.205.28
   Type: A, Name: www, Value: 103.9.205.28
   ```
3. Wait 15-30 minutes for DNS propagation
4. Verify: `nslookup serein.io.vn` should show 103.9.205.28

### Step 2: Deploy Application

**Option A: Automated (Recommended)**

```powershell
# From Windows (PowerShell)
cd d:\serein-blog-new
.\deploy\deploy-simple.ps1
```

When prompted for password, enter: `Next-Step@2310`

**Option B: Manual**

See detailed steps in: [`README.md`](./README.md)

### Step 3: Setup SSL Certificate

**After deployment completes and DNS is working**:

```bash
# SSH into server
ssh -p 2012 root@103.9.205.28

# Run SSL setup script
bash /var/www/serein-blog/deploy/setup-ssl.sh
```

This will:
- Install Certbot
- Obtain free SSL certificate from Let's Encrypt
- Configure HTTPS
- Setup auto-renewal

---

## 📁 Deployment Files

| File | Purpose |
|------|---------|
| `deploy-simple.ps1` | Simple deployment script for Windows |
| `deploy.sh` | Server setup script (runs on VPS) |
| `production.env` | Production environment variables |
| `serein-blog.service` | Systemd service configuration |
| `nginx-serein-blog.conf` | Nginx config (HTTP only) |
| `nginx-serein-blog-ssl.conf` | Nginx config with SSL |
| `setup-ssl.sh` | SSL certificate setup script |
| `DNS-SETUP.md` | DNS configuration guide |
| `README.md` | Detailed deployment documentation |

---

## 🔄 Deployment Process Flow

```
1. Configure DNS (serein.io.vn → 103.9.205.28)
   ↓
2. Wait for DNS propagation (15-30 min)
   ↓
3. Run deployment script
   ├─ Transfer files to server
   ├─ Setup Python environment
   ├─ Configure PostgreSQL
   ├─ Run database migrations
   ├─ Setup systemd service
   └─ Configure Nginx (HTTP)
   ↓
4. Verify HTTP access (http://serein.io.vn)
   ↓
5. Run SSL setup script
   ├─ Install Certbot
   ├─ Obtain SSL certificate
   ├─ Update Nginx config (HTTPS)
   └─ Setup auto-renewal
   ↓
6. Verify HTTPS access (https://serein.io.vn)
   ↓
7. Done! 🎉
```

---

## ✅ Pre-Deployment Checklist

- [ ] DNS configured (serein.io.vn points to 103.9.205.28)
- [ ] DNS propagated (verify with `nslookup serein.io.vn`)
- [ ] SSH access working (`ssh -p 2012 root@103.9.205.28`)
- [ ] Server has internet connection
- [ ] Ports 80, 443, 2012 are open

---

## 🎯 Post-Deployment Checklist

- [ ] Application accessible via HTTP (http://serein.io.vn)
- [ ] API docs working (http://serein.io.vn/api/docs)
- [ ] Admin panel accessible (http://serein.io.vn/admin)
- [ ] SSL certificate installed
- [ ] HTTPS working (https://serein.io.vn)
- [ ] HTTP redirects to HTTPS
- [ ] Auto-renewal configured

---

## 🔧 Useful Commands

### Check Application Status
```bash
ssh -p 2012 root@103.9.205.28 'systemctl status serein-blog'
```

### View Application Logs
```bash
ssh -p 2012 root@103.9.205.28 'journalctl -u serein-blog -f'
```

### Restart Application
```bash
ssh -p 2012 root@103.9.205.28 'systemctl restart serein-blog'
```

### Check Nginx Status
```bash
ssh -p 2012 root@103.9.205.28 'systemctl status nginx'
```

### View Nginx Logs
```bash
ssh -p 2012 root@103.9.205.28 'tail -f /var/log/nginx/serein-blog-error.log'
```

### Check SSL Certificate
```bash
ssh -p 2012 root@103.9.205.28 'certbot certificates'
```

### Test SSL Renewal
```bash
ssh -p 2012 root@103.9.205.28 'certbot renew --dry-run'
```

---

## 🔍 Troubleshooting

### Issue: Cannot connect via SSH
- Verify IP address: 103.9.205.28
- Verify port: 2012
- Verify password: Next-Step@2310
- Check if server is running

### Issue: Domain doesn't resolve
- Wait longer for DNS propagation (up to 1 hour)
- Verify DNS records are correct
- Clear local DNS cache: `ipconfig /flushdns` (Windows)

### Issue: HTTP works but HTTPS doesn't
- Make sure SSL setup script was run
- Check if certificate was obtained: `certbot certificates`
- Verify Nginx SSL config: `nginx -t`
- Check Nginx error logs

### Issue: Application not responding
- Check service status: `systemctl status serein-blog`
- View logs: `journalctl -u serein-blog -n 50`
- Check if port 8000 is listening: `netstat -tulpn | grep 8000`
- Restart service: `systemctl restart serein-blog`

### Issue: Database connection error
- Check PostgreSQL status: `systemctl status postgresql`
- Verify database exists: `sudo -u postgres psql -l`
- Check .env file has correct DATABASE_URL

---

## 🔄 Updating the Application

When you make changes to your code:

```powershell
# 1. Transfer updated files
scp -P 2012 -r backend root@103.9.205.28:/var/www/serein-blog/
scp -P 2012 -r api root@103.9.205.28:/var/www/serein-blog/

# 2. Restart application
ssh -p 2012 root@103.9.205.28 'systemctl restart serein-blog'
```

Or use the full deployment script again (it will update everything).

---

## 📞 Support & Resources

- **Deployment Docs**: See `README.md` in this directory
- **DNS Setup**: See `DNS-SETUP.md`
- **SSL Setup**: Run `setup-ssl.sh` on server
- **Nginx Docs**: https://nginx.org/en/docs/
- **Certbot Docs**: https://certbot.eff.org/
- **FastAPI Docs**: https://fastapi.tiangolo.com/

---

## 🎉 Success Indicators

Your deployment is successful when:

1. ✅ `https://serein.io.vn` loads your blog
2. ✅ `https://serein.io.vn/api/docs` shows API documentation
3. ✅ `https://serein.io.vn/admin` shows admin login
4. ✅ `https://serein.io.vn/health` returns `{"status":"healthy"}`
5. ✅ Browser shows green padlock (valid SSL)
6. ✅ HTTP automatically redirects to HTTPS

---

**Made with ❤️ for easy deployment to serein.io.vn**
