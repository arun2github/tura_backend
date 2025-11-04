# Database Migration Guide
## Tura Municipal Board - Clean Migration Strategy

### 🎯 **Migration Options**

Choose the appropriate migration strategy based on your needs:

---

## 📋 **Option 1: Fresh Migration (Recommended for Development)**

⚠️ **WARNING: This will DELETE ALL existing data**

### Steps:
```bash
# 1. Drop all tables and recreate
php artisan migrate:fresh

# 2. Run all migrations from scratch
php artisan migrate

# 3. Seed with sample data (if you have seeders)
php artisan db:seed
```

### What this does:
- Drops ALL existing tables
- Recreates migration table
- Runs ALL migrations from the beginning
- Results in a clean, consistent database structure

---

## 🔄 **Option 2: Reset and Migrate (Safer)**

### Steps:
```bash
# 1. Rollback all migrations
php artisan migrate:reset

# 2. Run all migrations fresh
php artisan migrate

# 3. Check migration status
php artisan migrate:status
```

### What this does:
- Rolls back all migrations in reverse order
- Preserves migration history
- Re-runs all migrations

---

## 🎯 **Option 3: Selective Migration (Production Safe)**

### Steps:
```bash
# 1. Check current migration status
php artisan migrate:status

# 2. Run only pending migrations
php artisan migrate

# 3. Run specific migration if needed
php artisan migrate --path=database/migrations/2025_11_03_131707_add_payment_order_id_to_tura_job_applied_status_table.php
```

---

## 🗄️ **Option 4: Production Database Migration**

For production with existing data:

### Steps:
```bash
# 1. Backup existing database first!
mysqldump -u u608187177_municipal_prod -p u608187177_municipal_prod > backup_$(date +%Y%m%d_%H%M%S).sql

# 2. Check what migrations need to run
php artisan migrate:status

# 3. Run pending migrations only
php artisan migrate

# 4. Verify the new column was added
php artisan tinker --execute="
use Illuminate\Support\Facades\Schema;
if (Schema::hasColumn('tura_job_applied_status', 'payment_order_id')) {
    echo 'payment_order_id column exists - Migration successful!' . PHP_EOL;
} else {
    echo 'payment_order_id column missing - Migration failed!' . PHP_EOL;
}
"
```

---

## 🛠️ **Complete Fresh Setup (Recommended)**

Here's a complete script to set up everything fresh:

### Local Development:
```bash
# 1. Fresh migration (deletes all data)
php artisan migrate:fresh

# 2. Create sample job posting
php artisan tinker --execute="
use App\Models\TuraJobPosting;
\$job = TuraJobPosting::create([
    'job_title_department' => 'Assistant Engineer - Hydrologist',
    'fee_general' => 230.00,
    'fee_obc' => 230.00,
    'fee_sc_st' => 115.00,
    'category' => 'Technical',
    'status' => 'active'
]);
echo 'Job created with ID: ' . \$job->id . PHP_EOL;
"

# 3. Create sample application
php artisan tinker --execute="
use App\Models\JobAppliedStatus;
use App\Models\JobPersonalDetail;

\$application = JobAppliedStatus::create([
    'job_id' => 1,
    'user_id' => 10,
    'status' => 'in_progress',
    'stage' => 5,
    'application_id' => 'TMB-2025-JOB1-0001',
    'email' => 'test@example.com',
    'payment_amount' => 230.00,
    'payment_status' => 'pending',
    'category_applied' => 'UR',
    'inserted_at' => now(),
    'updated_at' => now()
]);

\$personal = JobPersonalDetail::create([
    'user_id' => 10,
    'job_id' => 1,
    'full_name' => 'Test User',
    'email' => 'test@example.com',
    'category' => 'UR',
    'phone' => '9876543210'
]);

echo 'Sample application created: ' . \$application->application_id . PHP_EOL;
"

# 4. Verify everything is working
php artisan route:list --path=job-payment
```

---

## 🔧 **Migration Troubleshooting**

### Common Issues:

1. **Migration stuck or failed:**
```bash
# Reset migration state
php artisan migrate:reset
php artisan migrate
```

2. **Foreign key constraints:**
```bash
# Disable foreign key checks (MySQL)
php artisan tinker --execute="
DB::statement('SET FOREIGN_KEY_CHECKS=0;');
echo 'Foreign key checks disabled' . PHP_EOL;
"

# Run migration
php artisan migrate:fresh

# Re-enable foreign key checks
php artisan tinker --execute="
DB::statement('SET FOREIGN_KEY_CHECKS=1;');
echo 'Foreign key checks enabled' . PHP_EOL;
"
```

3. **Column already exists error:**
```bash
# Check if column exists before migration
php artisan tinker --execute="
use Illuminate\Support\Facades\Schema;
if (Schema::hasColumn('tura_job_applied_status', 'payment_order_id')) {
    echo 'Column already exists - skipping migration' . PHP_EOL;
} else {
    echo 'Column does not exist - migration needed' . PHP_EOL;
}
"
```

---

## 📊 **Database Structure Verification**

After migration, verify the structure:

```bash
# Check all tables
php artisan tinker --execute="
\$tables = DB::select('SHOW TABLES');
echo 'Database tables:' . PHP_EOL;
foreach(\$tables as \$table) {
    \$tableName = array_values((array)\$table)[0];
    echo '- ' . \$tableName . PHP_EOL;
}
"

# Check specific table structure
php artisan tinker --execute="
\$columns = DB::select('DESCRIBE tura_job_applied_status');
echo 'tura_job_applied_status columns:' . PHP_EOL;
foreach(\$columns as \$col) {
    echo '- ' . \$col->Field . ' (' . \$col->Type . ')' . PHP_EOL;
}
"
```

---

## 🎯 **Recommended Migration Strategy**

### For Development:
1. **Fresh Migration**: `php artisan migrate:fresh`
2. **Create Sample Data**: Use the sample data script above
3. **Test Payment System**: Verify all endpoints work

### For Production:
1. **Backup Database**: Always backup first!
2. **Run Selective Migration**: `php artisan migrate`
3. **Verify Changes**: Check that payment_order_id column exists
4. **Test Critical Functions**: Ensure existing data is intact

---

## 🚨 **Safety Checklist**

Before running any migration:

- [ ] **Backup database** (especially for production)
- [ ] **Test migration on development copy first**
- [ ] **Verify all required environment variables are set**
- [ ] **Ensure application is in maintenance mode** (production)
- [ ] **Have rollback plan ready**

### Backup Commands:
```bash
# MySQL backup
mysqldump -u username -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql

# Laravel backup (if using backup package)
php artisan backup:run
```

### Maintenance Mode:
```bash
# Enable maintenance mode
php artisan down --message="Database migration in progress"

# Run migrations
php artisan migrate

# Disable maintenance mode
php artisan up
```

---

## ✅ **Post-Migration Verification**

After successful migration:

1. **Check Migration Status:**
```bash
php artisan migrate:status
```

2. **Test Database Connection:**
```bash
php artisan tinker --execute="
try {
    DB::connection()->getPdo();
    echo 'Database connection: SUCCESS' . PHP_EOL;
} catch (Exception \$e) {
    echo 'Database connection FAILED: ' . \$e->getMessage() . PHP_EOL;
}
"
```

3. **Verify Payment System:**
```bash
# Test payment status endpoint
curl -X GET "http://127.0.0.1:8000/api/job-payment/status/TMB-2025-JOB1-0001" \
  -H "Accept: application/json"
```

4. **Check Application Data:**
```bash
php artisan tinker --execute="
use App\Models\JobAppliedStatus;
\$count = JobAppliedStatus::count();
echo 'Total applications: ' . \$count . PHP_EOL;
if(\$count > 0) {
    \$latest = JobAppliedStatus::latest()->first();
    echo 'Latest application: ' . \$latest->application_id . PHP_EOL;
}
"
```

Choose the migration strategy that best fits your needs and environment!