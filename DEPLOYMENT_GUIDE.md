# Production Deployment Guide
## Tura Municipal Board - Job Payment System

### 🚀 **Pre-Deployment Checklist**

#### 1. **Environment Configuration**
- [ ] Update `.env` for production
- [ ] Set correct database credentials
- [ ] Configure SBI ePay production keys
- [ ] Set proper APP_URL
- [ ] Enable proper error logging

#### 2. **Database Migrations**
- [ ] Run all pending migrations
- [ ] Verify payment table structure
- [ ] Test with sample data

#### 3. **File Uploads & Permissions**
- [ ] Upload all new files
- [ ] Set proper file permissions
- [ ] Clear cache and config

#### 4. **Testing**
- [ ] Test payment initiation
- [ ] Verify SBI ePay integration
- [ ] Test email functionality

---

### 📝 **Step 1: Update Production Environment File**

Create/update `.env` file on production server:

```env
APP_NAME="Tura Municipal Board"
APP_ENV=production
APP_KEY=base64:YOUR_PRODUCTION_APP_KEY
APP_DEBUG=false
APP_URL=https://laravelv2.turamunicipalboard.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_production_database_name
DB_USERNAME=your_production_db_user
DB_PASSWORD=your_production_db_password

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@turamunicipalboard.com
MAIL_FROM_NAME="Tura Municipal Board"

# SBI ePay Configuration (Production)
PAYMENT_KEY=YOUR_PRODUCTION_SBI_EPAY_KEY
SBIEPAY_MERCHANT_ID=YOUR_PRODUCTION_MERCHANT_ID

# Frontend URL for redirects
FRONTEND_URL=https://turamunicipalboard.com

# Session & Cache
SESSION_DRIVER=file
SESSION_LIFETIME=120
CACHE_DRIVER=file

# Security
SANCTUM_STATEFUL_DOMAINS=turamunicipalboard.com,laravelv2.turamunicipalboard.com
```

---

### 📤 **Step 2: Files to Upload**

Upload these new/modified files to production:

#### **New Controllers**
```
app/Http/Controllers/JobPaymentController.php
```

#### **New Views**
```
resources/views/job-payment-form.blade.php
```

#### **Modified Routes**
```
routes/api.php (updated with payment routes)
```

#### **New Migration**
```
database/migrations/2025_11_03_131707_add_payment_order_id_to_tura_job_applied_status_table.php
```

#### **Updated Models**
```
app/Models/JobAppliedStatus.php (if modified)
```

#### **Configuration**
```
config/services.php (updated with SBI ePay config)
```

---

### 🗄️ **Step 3: Database Migration Commands**

Run these commands on production server:

```bash
# Navigate to project directory
cd /path/to/your/project

# Run the new migration
php artisan migrate --path=database/migrations/2025_11_03_131707_add_payment_order_id_to_tura_job_applied_status_table.php

# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Cache configuration for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

### 🔧 **Step 4: File Permissions**

Set proper permissions on production server:

```bash
# Set ownership (adjust user/group as needed)
sudo chown -R www-data:www-data /path/to/your/project

# Set directory permissions
sudo find /path/to/your/project -type d -exec chmod 755 {} \;

# Set file permissions
sudo find /path/to/your/project -type f -exec chmod 644 {} \;

# Set storage and bootstrap/cache writable
sudo chmod -R 775 /path/to/your/project/storage
sudo chmod -R 775 /path/to/your/project/bootstrap/cache
```

---

### 🧪 **Step 5: Production Testing**

#### **Test Payment API Endpoints**

```bash
# Test payment initiation
curl -X POST "https://laravelv2.turamunicipalboard.com/api/job-payment/initiate" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "user_id": 10,
    "job_id": 3,
    "application_id": "TMB-2025-JOB3-0001"
  }'

# Test payment status
curl -X GET "https://laravelv2.turamunicipalboard.com/api/job-payment/status/TMB-2025-JOB3-0001" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

#### **Test Payment Form URL**
```
https://laravelv2.turamunicipalboard.com/api/job-payment-form?order_id=TEST_ORDER_ID
```

---

### 📧 **Step 6: Email Configuration**

Ensure email functionality works:

1. **Gmail App Password Setup** (if using Gmail):
   - Go to Google Account settings
   - Enable 2-Factor Authentication
   - Generate App Password for Laravel
   - Use App Password in MAIL_PASSWORD

2. **Test Email Sending**:
```bash
php artisan tinker
# In tinker:
Mail::raw('Test email from production', function($message) {
    $message->to('test@example.com')->subject('Production Test');
});
```

---

### 🔐 **Step 7: SBI ePay Production Configuration**

1. **Get Production Credentials** from SBI ePay:
   - Production Merchant ID
   - Production Encryption Key
   - Production Gateway URLs

2. **Update Services Configuration**:
```php
// config/services.php
'sbiepay' => [
    'merchant_id' => env('SBIEPAY_MERCHANT_ID', 'YOUR_PROD_MERCHANT_ID'),
    'key' => env('PAYMENT_KEY'),
    'gateway_url' => env('SBIEPAY_GATEWAY_URL', 'https://www.sbiepay.sbi/secure/'),
],
```

---

### 🔍 **Step 8: Monitoring & Logs**

#### **Log Files to Monitor**
```bash
# Application logs
tail -f storage/logs/laravel.log

# Web server logs (Apache/Nginx)
tail -f /var/log/apache2/error.log
# OR
tail -f /var/log/nginx/error.log
```

#### **Payment-Specific Logs**
The JobPaymentController logs all payment activities. Monitor for:
- Payment initiation requests
- SBI ePay responses
- Double verification results
- Email sending status

---

### 🚨 **Step 9: Troubleshooting**

#### **Common Issues & Solutions**

1. **"Invalid application ID" Error**:
   - Verify application exists in database
   - Check JWT token validity
   - Verify user permissions

2. **Payment Form Not Loading**:
   - Check .env PAYMENT_KEY is set
   - Verify SBI ePay credentials
   - Check Laravel logs for errors

3. **Email Not Sending**:
   - Verify MAIL_* configuration
   - Test SMTP credentials
   - Check firewall/port restrictions

4. **Database Connection Issues**:
   - Verify DB credentials in .env
   - Check database server status
   - Verify network connectivity

---

### 🎯 **Step 10: Post-Deployment Verification**

#### **Checklist**
- [ ] Payment initiation API works
- [ ] Payment form displays correctly
- [ ] SBI ePay integration functional
- [ ] Email confirmations sending
- [ ] Database updates correctly
- [ ] Error logging working
- [ ] SSL certificate valid
- [ ] All routes accessible

#### **Sample Test Flow**
1. Create test job application
2. Initiate payment via API
3. Access payment form URL
4. Verify SBI ePay redirection
5. Test success/failure callbacks
6. Confirm email notifications
7. Check database updates

---

### 📞 **Support & Maintenance**

#### **Key Files for Updates**
- `JobPaymentController.php` - Payment logic
- `job-payment-form.blade.php` - Payment form UI
- `routes/api.php` - API endpoints
- `.env` - Configuration

#### **Regular Maintenance**
- Monitor payment success rates
- Review error logs weekly
- Test email functionality monthly
- Backup database regularly
- Update SSL certificates as needed

---

## 🎉 **Deployment Complete!**

Your job application payment system is now ready for production use with:
- ✅ SBI ePay integration
- ✅ Secure payment processing
- ✅ Email confirmations
- ✅ Database tracking
- ✅ Error handling & logging
- ✅ Production-ready configuration

Remember to keep your SBI ePay credentials secure and monitor the system regularly for optimal performance.