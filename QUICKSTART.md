# Aurora Quick Start Guide

## Access URLs

### Local Network Access
- **URL:** https://aurora.acumenus.net
- **Direct IP:** https://192.168.1.58
- **API Base:** https://aurora.acumenus.net/api

### Test User Accounts
The database has been seeded with test users:

| Name | Email | Password |
|------|-------|----------|
| Dr. Lisa Anderson | lisa.anderson@example.com | password |
| Dr. David Kim | david.kim@example.com | password |
| Dr. Rachel Green | rachel.green@example.com | password |

### From Another Device on LAN
Add to `/etc/hosts` (or `C:\Windows\System32\drivers\etc\hosts` on Windows):
```
192.168.1.58 aurora.acumenus.net
```

Then visit: https://aurora.acumenus.net (accept certificate warning)

---

## Common Commands

### Check Status
```bash
# Apache status
sudo systemctl status apache2

# Test site
curl -I https://aurora.acumenus.net

# Test API
curl -s https://aurora.acumenus.net/api/events
```

### View Logs
```bash
# Apache access log
sudo tail -f /var/log/apache2/aurora.acumenus.net-access.log

# Apache error log
sudo tail -f /var/log/apache2/aurora.acumenus.net-error.log

# Laravel log
tail -f storage/logs/laravel.log
```

### Update Application
```bash
# Pull latest code
git pull origin main

# Update dependencies
composer install --optimize-autoloader
npm install

# Build frontend
npm run build

# Run migrations
php artisan migrate --force

# Clear and rebuild caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Reload Apache
sudo systemctl reload apache2
```

### Database Operations
```bash
# Access database
psql -U smudoshi -d aurora

# Run migrations
php artisan migrate

# Seed database
php artisan db:seed

# Fresh migration (WARNING: destroys data)
php artisan migrate:fresh --seed
```

---

## File Locations

### Application
- **Root:** `/home/smudoshi/Github/Aurora`
- **Public:** `/home/smudoshi/Github/Aurora/public`
- **Config:** `/home/smudoshi/Github/Aurora/.env`

### Apache
- **HTTP Config:** `/etc/apache2/sites-available/aurora.acumenus.net.conf`
- **HTTPS Config:** `/etc/apache2/sites-available/aurora.acumenus.net-le-ssl.conf`

### SSL Certificate
- **Cert:** `/etc/letsencrypt/live/aurora.acumenus.net/fullchain.pem`
- **Key:** `/etc/letsencrypt/live/aurora.acumenus.net/privkey.pem`

### Logs
- **Apache Access:** `/var/log/apache2/aurora.acumenus.net-access.log`
- **Apache Error:** `/var/log/apache2/aurora.acumenus.net-error.log`
- **Laravel:** `/home/smudoshi/Github/Aurora/storage/logs/laravel.log`

---

## Troubleshooting

### Site Not Loading
```bash
# Check Apache is running
sudo systemctl status apache2

# Check Apache configuration
sudo apache2ctl configtest

# Restart Apache if needed
sudo systemctl restart apache2

# Check if port is listening
sudo ss -tlnp | grep ':443'
```

### 500 Internal Server Error
```bash
# Check Laravel logs
tail -50 storage/logs/laravel.log

# Check Apache error log
sudo tail -50 /var/log/apache2/aurora.acumenus.net-error.log

# Check file permissions
ls -la storage bootstrap/cache

# Fix permissions if needed
sudo chgrp -R www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Database Connection Issues
```bash
# Check PostgreSQL is running
sudo systemctl status postgresql

# Test database connection
psql -U smudoshi -d aurora -c "SELECT 1;"

# Check .env database settings
cat .env | grep DB_
```

### SSL Certificate Issues
```bash
# Check certificate expiry
echo | openssl s_client -connect aurora.acumenus.net:443 -servername aurora.acumenus.net 2>/dev/null | openssl x509 -noout -dates

# Renew certificate manually
sudo certbot renew --dry-run
sudo certbot renew

# Reload Apache after renewal
sudo systemctl reload apache2
```

### Cache Issues
```bash
# Clear all Laravel caches
php artisan optimize:clear

# Or individually:
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Clear browser cache
# Press Ctrl+Shift+Delete in browser
```

---

## Development Workflow

### Start Development
```bash
cd /home/smudoshi/Github/Aurora

# Start Laravel dev server (alternative to Apache)
php artisan serve

# Start Vite dev server (with HMR)
npm run dev

# Or use the helper script
./start-dev.sh
```

### Make Changes
1. Edit code in `app/`, `resources/`, etc.
2. If backend changes: Clear Laravel caches
3. If frontend changes: Rebuild with `npm run build`
4. Test changes locally

### Deploy Changes
```bash
# Build production assets
npm run build

# Cache for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Reload Apache
sudo systemctl reload apache2
```

---

## Security Notes

### Current Status
- ✅ HTTPS with valid certificate
- ✅ CSRF protection enabled
- ✅ Secure session cookies
- ⚠️ Firewall disabled (UFW inactive)
- ⚠️ Running from home directory

### Before Internet Exposure
1. Enable firewall (`sudo ufw enable`)
2. Move to `/var/www/aurora`
3. Configure rate limiting
4. Set up monitoring
5. Review security headers
6. Configure fail2ban

---

## Support

### Documentation
- **Devlog:** `DEVLOG-2026-02-28-aurora-acumenus-net-deployment.md`
- **Network Status:** `NETWORK-STATUS-2026-02-28.md`
- **Project README:** `README.md`
- **AGENTS Guide:** `AGENTS.md`

### Key Information
- **Framework:** Laravel 11 + React 19
- **Database:** PostgreSQL 17 (dev schema)
- **Web Server:** Apache 2.4.64 + PHP-FPM 8.4
- **SSL:** Let's Encrypt (expires May 29, 2026)

---

**Last Updated:** 2026-02-28  
**Version:** 1.0
