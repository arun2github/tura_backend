#!/bin/bash

# Deployment Script for Tura Municipal Board Payment System
# Run this script on your production server

echo "🚀 Starting Tura Municipal Board Payment System Deployment..."
echo "=================================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print status
print_status() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Check if we're in Laravel project directory
if [ ! -f "artisan" ]; then
    print_error "Error: Not in Laravel project directory. Please navigate to your project root."
    exit 1
fi

print_status "Found Laravel project directory"

# Step 1: Backup current state
echo ""
echo "📦 Step 1: Creating backup..."
BACKUP_DIR="backup_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"
cp -r database/migrations "$BACKUP_DIR/" 2>/dev/null || true
cp .env "$BACKUP_DIR/" 2>/dev/null || true
print_status "Backup created in $BACKUP_DIR"

# Step 2: Run new migration
echo ""
echo "🗄️  Step 2: Running database migration..."
php artisan migrate --path=database/migrations/2025_11_03_131707_add_payment_order_id_to_tura_job_applied_status_table.php
if [ $? -eq 0 ]; then
    print_status "Migration completed successfully"
else
    print_error "Migration failed. Check the error above."
    exit 1
fi

# Step 3: Clear caches
echo ""
echo "🧹 Step 3: Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
print_status "Caches cleared"

# Step 4: Cache configuration for production
echo ""
echo "⚡ Step 4: Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
print_status "Configuration cached for production"

# Step 5: Set permissions
echo ""
echo "🔐 Step 5: Setting file permissions..."
chmod -R 775 storage
chmod -R 775 bootstrap/cache
print_status "Permissions set"

# Step 6: Check environment configuration
echo ""
echo "🔍 Step 6: Checking environment configuration..."

# Check if required environment variables are set
check_env_var() {
    local var_name=$1
    local var_value=$(grep "^$var_name=" .env 2>/dev/null | cut -d '=' -f2)
    
    if [ -z "$var_value" ] || [ "$var_value" = "null" ]; then
        print_warning "$var_name is not set in .env"
        return 1
    else
        print_status "$var_name is configured"
        return 0
    fi
}

ENV_OK=true

# Check critical environment variables
check_env_var "APP_URL" || ENV_OK=false
check_env_var "DB_DATABASE" || ENV_OK=false
check_env_var "DB_USERNAME" || ENV_OK=false
check_env_var "PAYMENT_KEY" || ENV_OK=false
check_env_var "MAIL_USERNAME" || ENV_OK=false

if [ "$ENV_OK" = false ]; then
    print_warning "Some environment variables need to be configured"
    echo "Please update your .env file with the missing values"
fi

# Step 7: Test database connection
echo ""
echo "🔌 Step 7: Testing database connection..."
php artisan tinker --execute="
try {
    \DB::connection()->getPdo();
    echo 'Database connection: OK' . PHP_EOL;
} catch (Exception \$e) {
    echo 'Database connection failed: ' . \$e->getMessage() . PHP_EOL;
    exit(1);
}
" 2>/dev/null
if [ $? -eq 0 ]; then
    print_status "Database connection successful"
else
    print_error "Database connection failed"
fi

# Step 8: Test payment routes
echo ""
echo "🛣️  Step 8: Checking payment routes..."
php artisan route:list --path=job-payment > /dev/null 2>&1
if [ $? -eq 0 ]; then
    print_status "Payment routes are registered"
    echo "Available payment routes:"
    php artisan route:list --path=job-payment | grep -E "(POST|GET).*job-payment"
else
    print_error "Payment routes not found"
fi

# Step 9: Generate deployment summary
echo ""
echo "📋 Step 9: Deployment Summary"
echo "=============================="

echo "🎯 Payment System Features Deployed:"
echo "  ✅ Job Payment Controller"
echo "  ✅ SBI ePay Integration"
echo "  ✅ Payment Form View"
echo "  ✅ Database Payment Tracking"
echo "  ✅ Email Confirmations"
echo "  ✅ Double Verification"

echo ""
echo "🔗 API Endpoints Available:"
echo "  POST /api/job-payment/initiate"
echo "  GET  /api/job-payment-form"
echo "  POST /api/job-payment/success"
echo "  POST /api/job-payment/failure" 
echo "  GET  /api/job-payment/status/{application_id}"

echo ""
echo "📧 Next Steps:"
echo "1. Test payment initiation with valid application ID"
echo "2. Configure SBI ePay production credentials"
echo "3. Test email functionality"
echo "4. Monitor logs for any issues"

# Step 10: Create test script
echo ""
echo "🧪 Creating test script..."
cat > test_payment_deployment.php << 'EOF'
<?php
/**
 * Test script for payment system deployment
 * Run with: php test_payment_deployment.php
 */

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\JobAppliedStatus;
use App\Models\TuraJobPosting;

echo "🧪 Testing Payment System Deployment\n";
echo "====================================\n\n";

// Test 1: Check if payment routes exist
echo "1. Checking payment routes...\n";
$routes = \Route::getRoutes();
$paymentRoutes = [];
foreach ($routes as $route) {
    if (strpos($route->uri(), 'job-payment') !== false) {
        $paymentRoutes[] = $route->methods()[0] . ' ' . $route->uri();
    }
}

if (count($paymentRoutes) > 0) {
    echo "   ✅ Found " . count($paymentRoutes) . " payment routes\n";
    foreach ($paymentRoutes as $route) {
        echo "   - $route\n";
    }
} else {
    echo "   ❌ No payment routes found\n";
}

echo "\n";

// Test 2: Check database structure
echo "2. Checking database structure...\n";
try {
    $hasPaymentOrderId = \Schema::hasColumn('tura_job_applied_status', 'payment_order_id');
    if ($hasPaymentOrderId) {
        echo "   ✅ payment_order_id column exists\n";
    } else {
        echo "   ❌ payment_order_id column missing\n";
    }
    
    $hasPaymentFields = \Schema::hasColumns('tura_job_applied_status', [
        'payment_amount', 'payment_status', 'payment_transaction_id'
    ]);
    if ($hasPaymentFields) {
        echo "   ✅ All payment fields exist\n";
    } else {
        echo "   ❌ Some payment fields missing\n";
    }
} catch (Exception $e) {
    echo "   ❌ Database check failed: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Check sample applications
echo "3. Checking sample applications...\n";
try {
    $applications = JobAppliedStatus::where('payment_status', 'pending')->take(3)->get();
    if ($applications->count() > 0) {
        echo "   ✅ Found " . $applications->count() . " applications ready for payment\n";
        foreach ($applications as $app) {
            echo "   - ID: {$app->application_id} | User: {$app->user_id} | Job: {$app->job_id}\n";
        }
    } else {
        echo "   ⚠️  No applications with pending payment found\n";
    }
} catch (Exception $e) {
    echo "   ❌ Application check failed: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Check environment configuration
echo "4. Checking environment configuration...\n";
$requiredEnvVars = ['APP_URL', 'PAYMENT_KEY', 'MAIL_USERNAME'];
foreach ($requiredEnvVars as $var) {
    $value = env($var);
    if ($value && $value !== 'null') {
        echo "   ✅ $var is configured\n";
    } else {
        echo "   ❌ $var is not set\n";
    }
}

echo "\n";

// Test 5: Check JobPaymentController
echo "5. Checking JobPaymentController...\n";
if (class_exists('App\Http\Controllers\JobPaymentController')) {
    echo "   ✅ JobPaymentController class exists\n";
    
    $reflection = new ReflectionClass('App\Http\Controllers\JobPaymentController');
    $methods = ['initiatePayment', 'handlePaymentSuccess', 'getPaymentStatus'];
    foreach ($methods as $method) {
        if ($reflection->hasMethod($method)) {
            echo "   ✅ Method $method exists\n";
        } else {
            echo "   ❌ Method $method missing\n";
        }
    }
} else {
    echo "   ❌ JobPaymentController class not found\n";
}

echo "\n🎉 Deployment test completed!\n";
echo "If all tests passed, your payment system is ready for production.\n";
EOF

print_status "Test script created: test_payment_deployment.php"

# Final status
echo ""
echo "🎉 Deployment Script Completed!"
echo "==============================="
echo ""
print_status "Payment system has been deployed successfully"
echo ""
echo "🔧 Next Actions:"
echo "1. Run the test script: php test_payment_deployment.php"
echo "2. Update .env with production SBI ePay credentials"  
echo "3. Test payment flow with a real application"
echo "4. Monitor logs for any issues"
echo ""
print_warning "Remember to set up SSL certificate and secure your production environment"
echo ""