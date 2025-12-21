# Production Deployment Checklist - API 500 Errors

## 🎯 Status
**Local Code:** ✅ **Fixed** - All three endpoints have been updated with proper error handling
**Production Server:** ⚠️ **Still Failing** - Changes need to be deployed

---

## 📋 Endpoints Fixed

### **1. `/api/get_categories`**
- ✅ Added try-catch block
- ✅ Fixed empty string handling
- ✅ Added error logging
- ✅ Fixed map() function return

### **2. `/api/homepage-data`**
- ✅ Fixed empty string handling for latitude/longitude/radius
- ✅ Added numeric validation
- ✅ Enhanced error logging

### **3. `/api/web-settings`**
- ✅ Fixed file existence checks
- ✅ Fixed hardcoded user ID
- ✅ Added database query error handling

---

## 🚀 Deployment Steps

### **Step 1: Deploy Code to Production**

```bash
# On production server
cd /path/to/project
git pull origin main  # or your branch name

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run migrations if any
php artisan migrate

# Restart services if needed
sudo service php8.1-fpm restart  # Adjust PHP version
sudo service nginx restart       # or apache2
```

### **Step 2: Verify Files Updated**

Check that these files are updated on production:
- `app/Http/Controllers/ApiController.php`
  - `get_categories()` method (around line 407)
  - `homepageData()` method (around line 5804)
  - `getWebSettings()` method (around line 6167)

### **Step 3: Check File Permissions**

```bash
# Ensure proper permissions
chmod -R 755 storage
chmod -R 755 bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### **Step 4: Check Laravel Logs**

```bash
# View recent errors
tail -f storage/logs/laravel.log | grep "ERROR"

# Check specific endpoint errors
tail -f storage/logs/laravel.log | grep "get_categories failed"
tail -f storage/logs/laravel.log | grep "homepageData failed"
tail -f storage/logs/laravel.log | grep "getWebSettings failed"
```

---

## 🔍 Additional Checks

### **1. Database Connection**
```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();
```

### **2. Environment Variables**
Check `.env` file on production:
```bash
# Verify these are set correctly
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

APP_DEBUG=false  # Should be false in production
APP_ENV=production
```

### **3. Required Files**
Check if these files exist:
```bash
# Check public_key.pem (for web-settings)
ls -la public_key.pem

# Check storage directories
ls -la storage/logs/
ls -la storage/framework/
```

### **4. PHP Errors**
Check PHP error logs:
```bash
# Location depends on your setup
tail -f /var/log/php8.1-fpm.log  # Adjust version
tail -f /var/log/nginx/error.log
tail -f /var/log/apache2/error.log
```

---

## 🧪 Testing After Deployment

### **Test 1: get_categories**
```bash
curl -X GET "https://maroon-fox-767665.hostingersite.com/api/get_categories" \
  -H "Accept: application/json"
```

**Expected:** ✅ 200 OK with categories data

### **Test 2: homepage-data**
```bash
curl -X GET "https://maroon-fox-767665.hostingersite.com/api/homepage-data?latitude=&longitude=&radius=" \
  -H "Accept: application/json"
```

**Expected:** ✅ 200 OK with homepage data

### **Test 3: web-settings**
```bash
curl -X GET "https://maroon-fox-767665.hostingersite.com/api/web-settings" \
  -H "Accept: application/json"
```

**Expected:** ✅ 200 OK with settings data

---

## 🐛 If Still Failing After Deployment

### **Check 1: Laravel Logs**
```bash
# The new error logging will show specific errors
tail -100 storage/logs/laravel.log | grep -A 10 "failed"
```

### **Check 2: Common Issues**

#### **Issue: Database Connection**
**Symptoms:** All endpoints return 500
**Fix:**
```bash
# Check database is running
sudo service mysql status

# Test connection
php artisan db:show
```

#### **Issue: Missing Tables**
**Symptoms:** Specific errors about missing tables
**Fix:**
```bash
# Run migrations
php artisan migrate

# Check table exists
php artisan tinker
>>> Schema::hasTable('categories');
>>> Schema::hasTable('propertys');
>>> Schema::hasTable('settings');
```

#### **Issue: File Permissions**
**Symptoms:** Cannot write to logs
**Fix:**
```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

#### **Issue: Memory Limit**
**Symptoms:** Timeout or memory errors
**Fix:**
```bash
# Check PHP memory limit
php -i | grep memory_limit

# Increase in php.ini or .htaccess
memory_limit = 256M
```

---

## 📊 Error Response Format

After deployment, errors will return specific messages:

**Before (Generic):**
```json
{
  "error": true,
  "message": "Server Error"
}
```

**After (Specific):**
```json
{
  "error": true,
  "message": "Failed to fetch categories: [Specific error message]"
}
```

---

## ✅ Verification Checklist

- [ ] Code deployed to production server
- [ ] Cache cleared (`php artisan cache:clear`)
- [ ] File permissions set correctly
- [ ] Database connection working
- [ ] Environment variables set correctly
- [ ] Laravel logs accessible and writable
- [ ] All three endpoints tested
- [ ] Error logs checked for specific errors
- [ ] Frontend no longer shows 500 errors

---

## 🔧 Quick Debug Commands

```bash
# Check if endpoints are accessible
curl -I https://maroon-fox-767665.hostingersite.com/api/get_categories

# Check Laravel version
php artisan --version

# Check route exists
php artisan route:list | grep get_categories
php artisan route:list | grep homepage-data
php artisan route:list | grep web-settings

# Check for syntax errors
php -l app/Http/Controllers/ApiController.php

# Test database
php artisan tinker
>>> \App\Models\Category::count();
>>> \App\Models\Setting::count();
```

---

## 📝 Notes

1. **Deployment Method:** If using Git, ensure you're pulling the correct branch
2. **Server Restart:** Some changes require PHP-FPM or web server restart
3. **Cache:** Always clear cache after deployment
4. **Logs:** Check logs immediately after deployment to catch any new errors
5. **Rollback:** Keep a backup of the previous version in case of issues

---

**Last Updated:** After fixing all three endpoints
**Status:** ✅ **Code Fixed** - Awaiting Production Deployment

