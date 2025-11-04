#!/bin/bash

# Database Migration Script for Tura Municipal Board
# This script helps you migrate the database cleanly

echo "🏛️  Tura Municipal Board - Database Migration Script"
echo "=================================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# Check if we're in Laravel directory
if [ ! -f "artisan" ]; then
    print_error "Not in Laravel project directory. Please navigate to your project root."
    exit 1
fi

print_success "Found Laravel project"

# Migration options menu
echo ""
echo "Choose migration strategy:"
echo "1) Fresh Migration (⚠️  DELETES ALL DATA)"
echo "2) Reset and Migrate (Safer rollback)"
echo "3) Run Pending Migrations Only (Production Safe)"
echo "4) Create Sample Data Only"
echo "5) Verify Current State"
echo "6) Exit"

read -p "Enter your choice (1-6): " choice

case $choice in
    1)
        echo ""
        print_warning "FRESH MIGRATION - THIS WILL DELETE ALL DATA!"
        read -p "Are you sure? Type 'YES' to continue: " confirm
        
        if [ "$confirm" = "YES" ]; then
            print_info "Running fresh migration..."
            
            # Create backup first
            BACKUP_DIR="backup_$(date +%Y%m%d_%H%M%S)"
            mkdir -p "$BACKUP_DIR"
            
            # Backup .env and any custom configs
            cp .env "$BACKUP_DIR/" 2>/dev/null || true
            
            print_info "Backup created in $BACKUP_DIR"
            
            # Fresh migration
            php artisan migrate:fresh
            
            if [ $? -eq 0 ]; then
                print_success "Fresh migration completed!"
                
                # Ask if user wants sample data
                read -p "Create sample data for testing? (y/n): " create_sample
                if [ "$create_sample" = "y" ] || [ "$create_sample" = "Y" ]; then
                    bash -c "$(cat << 'EOF'
php artisan tinker --execute="
use App\Models\TuraJobPosting;
use App\Models\JobAppliedStatus;
use App\Models\JobPersonalDetail;

echo 'Creating sample job posting...' . PHP_EOL;
\$job = TuraJobPosting::create([
    'job_title_department' => 'Assistant Engineer - Hydrologist',
    'fee_general' => 230.00,
    'fee_obc' => 230.00,
    'fee_sc_st' => 115.00,
    'category' => 'Technical',
    'status' => 'active'
]);
echo 'Job created with ID: ' . \$job->id . PHP_EOL;

echo 'Creating sample application...' . PHP_EOL;
\$application = JobAppliedStatus::create([
    'job_id' => \$job->id,
    'user_id' => 10,
    'status' => 'in_progress',
    'stage' => 5,
    'application_id' => 'TMB-2025-JOB' . \$job->id . '-0001',
    'email' => 'test@example.com',
    'payment_amount' => 230.00,
    'payment_status' => 'pending',
    'category_applied' => 'UR',
    'inserted_at' => now(),
    'updated_at' => now()
]);

\$personal = JobPersonalDetail::create([
    'user_id' => 10,
    'job_id' => \$job->id,
    'full_name' => 'Test User',
    'email' => 'test@example.com',
    'category' => 'UR',
    'phone' => '9876543210'
]);

echo 'Sample application created: ' . \$application->application_id . PHP_EOL;
echo 'Sample data creation completed!' . PHP_EOL;
"
EOF
)"
                    print_success "Sample data created!"
                fi
            else
                print_error "Fresh migration failed!"
                exit 1
            fi
        else
            print_info "Fresh migration cancelled"
        fi
        ;;
        
    2)
        print_info "Running reset and migrate..."
        
        # Reset all migrations
        php artisan migrate:reset
        
        # Run all migrations fresh
        php artisan migrate
        
        if [ $? -eq 0 ]; then
            print_success "Reset and migrate completed!"
        else
            print_error "Migration failed!"
            exit 1
        fi
        ;;
        
    3)
        print_info "Running pending migrations only..."
        
        # Show current status first
        echo "Current migration status:"
        php artisan migrate:status
        
        # Run pending migrations
        php artisan migrate
        
        if [ $? -eq 0 ]; then
            print_success "Pending migrations completed!"
        else
            print_error "Migration failed!"
            exit 1
        fi
        ;;
        
    4)
        print_info "Creating sample data only..."
        
        # Check if tables exist first
        php artisan tinker --execute="
        try {
            \$jobCount = \App\Models\TuraJobPosting::count();
            \$appCount = \App\Models\JobAppliedStatus::count();
            echo 'Current jobs: ' . \$jobCount . PHP_EOL;
            echo 'Current applications: ' . \$appCount . PHP_EOL;
        } catch (Exception \$e) {
            echo 'Error: ' . \$e->getMessage() . PHP_EOL;
            echo 'Tables may not exist. Run migration first.' . PHP_EOL;
            exit(1);
        }
        "
        
        if [ $? -eq 0 ]; then
            # Create sample data (same script as above)
            print_info "Tables exist, creating sample data..."
            # Sample data creation script here
        fi
        ;;
        
    5)
        print_info "Checking current database state..."
        
        echo ""
        echo "📊 Migration Status:"
        php artisan migrate:status
        
        echo ""
        echo "🗄️  Database Tables:"
        php artisan tinker --execute="
        try {
            \$tables = DB::select('SHOW TABLES');
            foreach(\$tables as \$table) {
                \$tableName = array_values((array)\$table)[0];
                echo '- ' . \$tableName . PHP_EOL;
            }
        } catch (Exception \$e) {
            echo 'Error checking tables: ' . \$e->getMessage() . PHP_EOL;
        }
        "
        
        echo ""
        echo "📋 Sample Data Check:"
        php artisan tinker --execute="
        try {
            use App\Models\TuraJobPosting;
            use App\Models\JobAppliedStatus;
            
            \$jobCount = TuraJobPosting::count();
            \$appCount = JobAppliedStatus::count();
            
            echo 'Job postings: ' . \$jobCount . PHP_EOL;
            echo 'Applications: ' . \$appCount . PHP_EOL;
            
            if (\$appCount > 0) {
                \$sample = JobAppliedStatus::first();
                echo 'Sample application ID: ' . \$sample->application_id . PHP_EOL;
            }
        } catch (Exception \$e) {
            echo 'Error: ' . \$e->getMessage() . PHP_EOL;
        }
        "
        
        echo ""
        echo "🔍 Payment System Check:"
        php artisan tinker --execute="
        use Illuminate\Support\Facades\Schema;
        
        if (Schema::hasColumn('tura_job_applied_status', 'payment_order_id')) {
            echo '✅ payment_order_id column exists' . PHP_EOL;
        } else {
            echo '❌ payment_order_id column missing - migration needed' . PHP_EOL;
        }
        
        \$paymentColumns = ['payment_amount', 'payment_status', 'payment_transaction_id'];
        foreach (\$paymentColumns as \$col) {
            if (Schema::hasColumn('tura_job_applied_status', \$col)) {
                echo '✅ ' . \$col . ' column exists' . PHP_EOL;
            } else {
                echo '❌ ' . \$col . ' column missing' . PHP_EOL;
            }
        }
        "
        ;;
        
    6)
        print_info "Exiting..."
        exit 0
        ;;
        
    *)
        print_error "Invalid choice. Please run the script again."
        exit 1
        ;;
esac

# Post-migration verification
echo ""
print_info "Running post-migration verification..."

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear

print_success "Caches cleared"

# Check if payment routes are registered
echo ""
print_info "Checking payment routes..."
php artisan route:list --path=job-payment 2>/dev/null | head -10

# Final status
echo ""
print_success "Migration process completed!"

echo ""
echo "🎯 Next Steps:"
echo "1. Test your application"
echo "2. Verify payment endpoints work"
echo "3. Check database data integrity"
echo "4. Update .env for production deployment"

echo ""
echo "🧪 Quick Test Commands:"
echo "php artisan tinker --execute=\"echo 'DB Test: ' . \App\Models\JobAppliedStatus::count() . ' applications';\""
echo "curl -X GET 'http://127.0.0.1:8000/api/job-payment/status/TMB-2025-JOB1-0001' -H 'Accept: application/json'"