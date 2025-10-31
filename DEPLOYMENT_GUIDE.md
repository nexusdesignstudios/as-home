# Deployment Guide - GitHub & Live Website

## 📋 Current Status

**Modified Files:**
- `app/Console/Commands/GuaranteedFeedbackRequests.php`
- `app/Console/Commands/GuaranteedTaxInvoices.php`
- `app/Http/Controllers/PropertyQuestionFormController.php`
- `app/Services/HelperService.php`
- `app/Services/MonthlyTaxInvoiceService.php`
- `resources/views/property-question-form/public-feedback.blade.php`

**New Files:**
- `app/Console/Commands/CheckOwnerRevenue.php`
- `app/Console/Commands/SendLiveFeedbackEmail.php`

---

## 🔄 Step 1: Pull Latest Changes from GitHub

Before pushing your changes, always pull the latest updates to avoid conflicts:

```bash
cd "D:\ashome\as-home-dashboard-Admin"
git pull origin main
```

If there are conflicts, resolve them before proceeding.

---

## ✅ Step 2: Stage Your Changes

Add all modified and new files:

```bash
# Add all modified files
git add app/Console/Commands/GuaranteedFeedbackRequests.php
git add app/Console/Commands/GuaranteedTaxInvoices.php
git add app/Http/Controllers/PropertyQuestionFormController.php
git add app/Services/HelperService.php
git add app/Services/MonthlyTaxInvoiceService.php
git add resources/views/property-question-form/public-feedback.blade.php

# Add new files
git add app/Console/Commands/CheckOwnerRevenue.php
git add app/Console/Commands/SendLiveFeedbackEmail.php

# Or add all changes at once:
git add .
```

---

## 💬 Step 3: Commit Changes

Create a descriptive commit message:

```bash
git commit -m "Fix: Hotel tax invoice filtering and payment method classification

- Filter tax invoices to only hotel properties (classification 5)
- Correctly split flexible (cash) and non-refundable (online) invoices
- Fix email template titles for hotel invoices
- Update payment method detection logic
- Add new commands for revenue checking and live feedback emails"
```

---

## 🚀 Step 4: Push to GitHub

Push your committed changes to GitHub:

```bash
git push origin main
```

If this is your first push or you need to set upstream:

```bash
git push -u origin main
```

---

## 🌐 Step 5: Deploy to Live Website

### Option A: Manual Deployment (SSH/FTP)

#### Via SSH (Recommended):

1. **Connect to your live server via SSH:**
   ```bash
   ssh username@your-server-ip
   # or
   ssh username@ashome-eg.com
   ```

2. **Navigate to project directory:**
   ```bash
   cd /path/to/as-home-dashboard-Admin
   # Example: cd /home/username/public_html/as-home-dashboard-Admin
   ```

3. **Pull changes from GitHub:**
   ```bash
   git pull origin main
   ```

4. **Install/Update dependencies:**
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

5. **Run migrations (if any):**
   ```bash
   php artisan migrate --force
   ```

6. **Clear caches:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan route:clear
   php artisan view:clear
   ```

7. **Optimize for production:**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

8. **Set proper permissions:**
   ```bash
   chmod -R 755 storage
   chmod -R 755 bootstrap/cache
   ```

#### Via FTP/SFTP:

1. Upload all changed files to the server
2. Connect via FTP/SFTP client (FileZilla, WinSCP, etc.)
3. Upload the modified files maintaining the directory structure
4. On the server, run the cache clearing commands via SSH

---

### Option B: Automated Deployment (GitHub Actions/CI/CD)

If you have CI/CD set up, pushing to `main` branch will automatically trigger deployment.

---

## 📝 Post-Deployment Checklist

After deploying to live:

1. ✅ **Test tax invoice emails:**
   ```bash
   php artisan tax:guaranteed-invoices 2025-10 --type=both --force --test
   ```

2. ✅ **Verify email templates are working**

3. ✅ **Check database migrations are applied**

4. ✅ **Test feedback form functionality**

5. ✅ **Clear browser cache** (for frontend changes)

---

## 🔒 Important Notes

1. **Always backup before deploying:**
   - Database backup
   - File backup
   - `.env` file backup

2. **Test locally first:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

3. **Check .env file on live:**
   - Ensure production environment variables are correct
   - Don't commit `.env` to GitHub

4. **Monitor logs after deployment:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

---

## 🛠️ Quick Deployment Script

Create a deployment script `deploy.sh` on your server:

```bash
#!/bin/bash
cd /path/to/as-home-dashboard-Admin
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "Deployment completed!"
```

Make it executable:
```bash
chmod +x deploy.sh
```

Then run:
```bash
./deploy.sh
```

---

## 📞 Support

If you encounter any issues during deployment:

1. Check server error logs
2. Verify file permissions
3. Ensure all environment variables are set
4. Check database connection
5. Review Laravel logs: `storage/logs/laravel.log`

