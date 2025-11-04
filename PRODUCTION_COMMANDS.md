# Tura Municipal Board - Payment System Deployment Commands
# Execute these commands on your production server after uploading files

# ====================================================================
# STEP 1: NAVIGATE TO PROJECT DIRECTORY
# ====================================================================
cd /path/to/your/laravel/project

# ====================================================================
# STEP 2: RUN THE PAYMENT MIGRATION
# ====================================================================
php artisan migrate --path=database/migrations/2025_11_03_131707_add_payment_order_id_to_tura_job_applied_status_table.php

# ====================================================================
# STEP 3: CLEAR ALL CACHES
# ====================================================================
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# ====================================================================
# STEP 4: CACHE CONFIGURATION FOR PRODUCTION
# ====================================================================
php artisan config:cache
php artisan route:cache
php artisan view:cache

# ====================================================================
# STEP 5: SET PROPER PERMISSIONS
# ====================================================================
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# ====================================================================
# STEP 6: VERIFY DEPLOYMENT
# ====================================================================
php artisan route:list --path=job-payment

# ====================================================================
# STEP 7: TEST DATABASE CONNECTION
# ====================================================================
php artisan tinker --execute="
try {
    \DB::connection()->getPdo();
    echo 'Database connection: SUCCESS' . PHP_EOL;
} catch (Exception \$e) {
    echo 'Database connection FAILED: ' . \$e->getMessage() . PHP_EOL;
}
"

# ====================================================================
# STEP 8: TEST PAYMENT ENDPOINTS
# ====================================================================
# Test payment status (replace with your domain)
curl -X GET "https://laravelv2.turamunicipalboard.com/api/job-payment/status/TMB-2025-JOB3-0001" \
  -H "Accept: application/json"

# Test payment initiation (requires JWT token)
curl -X POST "https://laravelv2.turamunicipalboard.com/api/job-payment/initiate" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "user_id": 10,
    "job_id": 3,
    "application_id": "TMB-2025-JOB3-0001"
  }'

# ====================================================================
# IMPORTANT: UPDATE YOUR .ENV FILE WITH THESE VALUES
# ====================================================================
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=u608187177_municipal_prod
# DB_USERNAME=u608187177_municipal_prod
# DB_PASSWORD=Municipal@1468
# APP_URL=https://laravelv2.turamunicipalboard.com
# PAYMENT_KEY=YOUR_PRODUCTION_SBI_EPAY_KEY
# MAIL_USERNAME=your_email@gmail.com
# MAIL_PASSWORD=your_gmail_app_password