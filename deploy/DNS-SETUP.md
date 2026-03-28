# DNS Configuration Guide for serein.io.vn

## Overview
Configure your domain **serein.io.vn** to point to your VPS server at **103.9.205.28**

## DNS Records to Add

### Required Records

#### 1. A Record (Main Domain)
```
Type: A
Name: @
Value: 103.9.205.28
TTL: 3600 (or Auto)
```

#### 2. A Record (WWW Subdomain)
```
Type: A
Name: www
Value: 103.9.205.28
TTL: 3600 (or Auto)
```

### Optional but Recommended

#### 3. CNAME Record (Alternative for WWW)
Instead of A record for www, you can use:
```
Type: CNAME
Name: www
Value: serein.io.vn
TTL: 3600 (or Auto)
```

## Step-by-Step Instructions

### If using a Vietnamese registrar (e.g., MATBAO, INET, PA Vietnam):

1. **Login to your domain registrar's control panel**
   - Go to your domain management page
   - Find DNS management or "Quản lý DNS"

2. **Add A Record for root domain (@)**
   - Type/Loại: A
   - Host/Tên: @ (or leave blank for root)
   - Points to/Trỏ đến: 103.9.205.28
   - TTL: 3600 or Auto

3. **Add A Record for www subdomain**
   - Type/Loại: A
   - Host/Tên: www
   - Points to/Trỏ đến: 103.9.205.28
   - TTL: 3600 or Auto

4. **Save changes**

### If using Cloudflare:

1. **Login to Cloudflare Dashboard**
   - Go to https://dash.cloudflare.com
   - Select your domain serein.io.vn

2. **Go to DNS settings**
   - Click on "DNS" in the left menu

3. **Add A Record for root**
   - Type: A
   - Name: @
   - IPv4 address: 103.9.205.28
   - Proxy status: DNS only (gray cloud) initially
   - TTL: Auto

4. **Add A Record for www**
   - Type: A
   - Name: www
   - IPv4 address: 103.9.205.28
   - Proxy status: DNS only (gray cloud) initially
   - TTL: Auto

5. **Save**

**Note**: After SSL is setup, you can enable Cloudflare proxy (orange cloud) for additional security and CDN benefits.

## Verification

### Check DNS Propagation

Use these tools to verify your DNS configuration:

1. **Command line (on your local machine)**:
   ```bash
   # Check A record
   nslookup serein.io.vn
   nslookup www.serein.io.vn
   
   # Or using dig (Linux/Mac)
   dig serein.io.vn
   dig www.serein.io.vn
   ```

2. **Online tools**:
   - https://dnschecker.org
   - https://www.whatsmydns.net
   - Enter "serein.io.vn" and check if it resolves to 103.9.205.28

### Expected Results

Both commands should show:
```
serein.io.vn        A    103.9.205.28
www.serein.io.vn    A    103.9.205.28
```

## DNS Propagation Time

- **Minimum**: 5-15 minutes
- **Average**: 30-60 minutes
- **Maximum**: Up to 48 hours (rare)

**Tip**: Use incognito mode or clear DNS cache to test faster

## Clear DNS Cache

### Windows:
```cmd
ipconfig /flushdns
```

### Mac:
```bash
sudo dscacheutil -flushcache
sudo killall -HUP mDNSResponder
```

### Linux:
```bash
sudo systemd-resolve --flush-caches
```

## After DNS is Configured

Once DNS is propagating (you can see serein.io.vn resolving to 103.9.205.28):

1. **Complete the deployment** (if not done yet)
2. **Test HTTP access**: http://serein.io.vn
3. **Setup SSL certificate**: Run the SSL setup script
4. **Test HTTPS access**: https://serein.io.vn

## Common Issues

### Issue: Domain doesn't resolve
- **Solution**: Wait longer for DNS propagation
- **Solution**: Check if you added the records correctly
- **Solution**: Verify TTL is not too high

### Issue: "This site can't be reached"
- **Solution**: DNS not propagated yet, wait 15-30 minutes
- **Solution**: Check if server firewall allows port 80/443

### Issue: SSL certificate fails
- **Solution**: Make sure DNS is fully propagated first
- **Solution**: Ensure port 80 is accessible from the internet
- **Solution**: Check if domain resolves to correct IP

## Contact Information

If you need help with DNS configuration:
- Contact your domain registrar's support
- Check their documentation/knowledge base
- Most Vietnamese registrars have 24/7 support via phone/chat

---

**Next Steps**: After DNS is configured and propagating, proceed with deployment and SSL setup.
