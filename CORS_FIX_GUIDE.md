# CORS Fix Deployment Guide
## Tura Municipal Board - Production CORS Configuration

### 🚨 **CORS Issue Resolution**

Your CORS issue affects all API endpoints, including Flutter web development servers. Here's the complete fix:

### ⚡ **Latest Update: localhost Dynamic Ports**
Added support for Flutter web development servers that use dynamic ports (like `http://localhost:52053`).

---

## 📝 **Step 1: Upload Updated Files**

Upload this file to your production server:
- `config/cors.php` (updated with comprehensive CORS settings)

---

## 🔧 **Step 2: Update Production .env**

Add these lines to your production `.env` file:

```env
# CORS Configuration
FRONTEND_URL=https://turamunicipalboard.com
CORS_SUPPORTS_CREDENTIALS=true
SANCTUM_STATEFUL_DOMAINS=turamunicipalboard.com,www.turamunicipalboard.com,laravelv2.turamunicipalboard.com
SESSION_DOMAIN=.turamunicipalboard.com
```

---

## 🔄 **Step 3: Clear and Cache Configuration**

Run these commands on your production server:

```bash
# Navigate to your Laravel project directory
cd /path/to/your/laravel/project

# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Cache configuration for production
php artisan config:cache
php artisan route:cache
```

---

## 🧪 **Step 4: Test CORS**

Test your CORS configuration with these commands:

### Test OPTIONS Preflight Request:
```bash
curl -i -X OPTIONS "https://laravelv2.turamunicipalboard.com/api/logout" \
  -H "Origin: https://turamunicipalboard.com" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Authorization,Content-Type"
```

### Test Actual API Request:
```bash
curl -i -X GET "https://laravelv2.turamunicipalboard.com/api/job-payment/status/TMB-2025-JOB3-0001" \
  -H "Origin: https://turamunicipalboard.com" \
  -H "Accept: application/json"
```

### Expected Response Headers:
```
Access-Control-Allow-Origin: https://turamunicipalboard.com
Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS
Access-Control-Allow-Headers: Accept, Authorization, Content-Type, X-Requested-With
Access-Control-Allow-Credentials: true
Access-Control-Max-Age: 86400
```

---

## 🎯 **What Changed**

### Old Configuration Issues:
- ❌ Limited allowed origins
- ❌ Missing important headers
- ❌ Short cache duration
- ❌ Limited path coverage

### New Configuration:
- ✅ **Comprehensive Origins**: All your domains included
- ✅ **All API Paths**: `['api/*', 'sanctum/csrf-cookie', 'logout', '*']`
- ✅ **Complete Headers**: Authorization, Content-Type, X-Requested-With, etc.
- ✅ **Credentials Support**: Enabled for authenticated requests
- ✅ **24-hour Cache**: Reduces preflight requests
- ✅ **Pattern Matching**: Supports all turamunicipalboard.com subdomains

---

## 🔍 **Troubleshooting**

If CORS issues persist:

### 1. Check Web Server Configuration
Some servers (Apache/Nginx) may override Laravel CORS headers.

**Apache (.htaccess):**
```apache
<IfModule mod_headers.c>
    Header always set Access-Control-Allow-Origin "https://turamunicipalboard.com"
    Header always set Access-Control-Allow-Methods "GET, POST, PUT, PATCH, DELETE, OPTIONS"
    Header always set Access-Control-Allow-Headers "Authorization, Content-Type, Accept, X-Requested-With"
    Header always set Access-Control-Allow-Credentials "true"
    Header always set Access-Control-Max-Age "86400"
</IfModule>
```

**Nginx:**
```nginx
location /api/ {
    add_header Access-Control-Allow-Origin "https://turamunicipalboard.com" always;
    add_header Access-Control-Allow-Methods "GET, POST, PUT, PATCH, DELETE, OPTIONS" always;
    add_header Access-Control-Allow-Headers "Authorization, Content-Type, Accept, X-Requested-With" always;
    add_header Access-Control-Allow-Credentials "true" always;
    add_header Access-Control-Max-Age "86400" always;
    
    if ($request_method = 'OPTIONS') {
        return 204;
    }
    
    try_files $uri $uri/ /index.php?$query_string;
}
```

### 2. Browser Developer Tools
Check Network tab for:
- Preflight OPTIONS requests
- Response headers
- CORS error messages in Console

### 3. Laravel Logs
Check `storage/logs/laravel.log` for any CORS-related errors.

---

## ⚡ **Quick Verification**

After deployment, verify CORS is working:

1. **Open browser Developer Tools**
2. **Go to your frontend application**
3. **Make an API request to any endpoint**
4. **Check Network tab** for proper CORS headers
5. **Console should show no CORS errors**

---

## 🚀 **Production Deployment Checklist**

- [ ] Upload updated `config/cors.php`
- [ ] Update `.env` with CORS settings
- [ ] Clear and cache Laravel configuration
- [ ] Test OPTIONS preflight requests
- [ ] Test actual API requests
- [ ] Check browser console for CORS errors
- [ ] Verify all API endpoints work from frontend

---

## 📞 **Support**

If CORS issues persist after following this guide:

1. Check browser Network tab for exact error messages
2. Test with different browsers
3. Verify your frontend is using the correct API URL
4. Check if any proxy/CDN is interfering with headers

The updated CORS configuration should resolve all CORS issues across your entire API!