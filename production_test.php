<?php
/**
 * Production Deployment Test - Tura Municipal Board
 * Test script for payment system with actual production database
 */

echo "🏛️  Tura Municipal Board - Payment System Production Test\n";
echo "========================================================\n\n";

// Production database configuration
$dbConfig = [
    'host' => '127.0.0.1',
    'port' => 3306,
    'database' => 'u608187177_municipal_prod',
    'username' => 'u608187177_municipal_prod',
    'password' => 'Municipal@1468'
];

// Test 1: Database Connection
echo "🔌 Test 1: Database Connection\n";
echo "------------------------------\n";
try {
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connection successful\n";
    echo "📊 Database: {$dbConfig['database']}\n";
    echo "🏠 Host: {$dbConfig['host']}:{$dbConfig['port']}\n";
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

// Test 2: Check if payment migration is needed
echo "🗄️  Test 2: Database Structure Check\n";
echo "-----------------------------------\n";
try {
    // Check if payment_order_id column exists
    $stmt = $pdo->prepare("SHOW COLUMNS FROM tura_job_applied_status LIKE 'payment_order_id'");
    $stmt->execute();
    $column = $stmt->fetch();
    
    if ($column) {
        echo "✅ payment_order_id column already exists\n";
    } else {
        echo "⚠️  payment_order_id column missing - migration needed\n";
        echo "📋 Run this command on production:\n";
        echo "   php artisan migrate --path=database/migrations/2025_11_03_131707_add_payment_order_id_to_tura_job_applied_status_table.php\n";
    }
    
    // Check other payment columns
    $paymentColumns = ['payment_amount', 'payment_status', 'payment_transaction_id', 'payment_date'];
    $existingColumns = [];
    
    foreach ($paymentColumns as $col) {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM tura_job_applied_status LIKE '$col'");
        $stmt->execute();
        if ($stmt->fetch()) {
            $existingColumns[] = $col;
        }
    }
    
    echo "✅ Existing payment columns: " . implode(', ', $existingColumns) . "\n";
    
} catch (PDOException $e) {
    echo "❌ Database structure check failed: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Check existing applications
echo "📋 Test 3: Existing Applications Check\n";
echo "------------------------------------\n";
try {
    // Count total applications
    $stmt = $pdo->query("SELECT COUNT(*) FROM tura_job_applied_status");
    $totalApps = $stmt->fetchColumn();
    echo "📊 Total applications: $totalApps\n";
    
    // Check applications ready for payment
    $stmt = $pdo->prepare("
        SELECT application_id, user_id, job_id, stage, payment_status, payment_amount 
        FROM tura_job_applied_status 
        WHERE stage >= 5 AND payment_status IN ('pending', 'null', '') 
        LIMIT 5
    ");
    $stmt->execute();
    $readyApps = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($readyApps) {
        echo "✅ Applications ready for payment:\n";
        foreach ($readyApps as $app) {
            $paymentStatus = $app['payment_status'] ?: 'null';
            echo "   - {$app['application_id']} | User: {$app['user_id']} | Job: {$app['job_id']} | Stage: {$app['stage']} | Payment: $paymentStatus\n";
        }
    } else {
        echo "⚠️  No applications ready for payment found\n";
    }
    
    // Check specific test application
    $stmt = $pdo->prepare("SELECT * FROM tura_job_applied_status WHERE application_id = 'TMB-2025-JOB3-0001'");
    $stmt->execute();
    $testApp = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($testApp) {
        echo "🎯 Test application TMB-2025-JOB3-0001 found:\n";
        echo "   User ID: {$testApp['user_id']}\n";
        echo "   Job ID: {$testApp['job_id']}\n";
        echo "   Stage: {$testApp['stage']}\n";
        echo "   Payment Status: " . ($testApp['payment_status'] ?: 'null') . "\n";
        echo "   Payment Amount: " . ($testApp['payment_amount'] ?: 'null') . "\n";
    } else {
        echo "⚠️  Test application TMB-2025-JOB3-0001 not found\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Applications check failed: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Production API Endpoints Test
echo "🌐 Test 4: Production API Test\n";
echo "-----------------------------\n";

$baseUrl = 'https://laravelv2.turamunicipalboard.com/api';

// Test payment status endpoint (no auth required)
echo "Testing payment status endpoint...\n";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $baseUrl . '/job-payment/status/TMB-2025-JOB3-0001',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'User-Agent: Mozilla/5.0 (Production Test)'
    ],
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_FOLLOWLOCATION => true
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ API Connection Error: $error\n";
} else {
    echo "📡 HTTP Response Code: $httpCode\n";
    if ($response) {
        $decodedResponse = json_decode($response, true);
        if ($decodedResponse) {
            echo "📄 API Response:\n";
            echo json_encode($decodedResponse, JSON_PRETTY_PRINT) . "\n";
            
            if ($httpCode == 200) {
                echo "✅ Payment API is responding correctly!\n";
            } elseif ($httpCode == 404) {
                echo "⚠️  Payment routes may not be deployed yet\n";
            } else {
                echo "⚠️  API returned unexpected status code\n";
            }
        } else {
            echo "📄 Raw Response: " . substr($response, 0, 200) . "\n";
        }
    }
}

echo "\n";

// Test 5: Generate Migration Command
echo "📋 Test 5: Deployment Commands\n";
echo "-----------------------------\n";

echo "🚀 Production deployment commands:\n\n";

echo "1. Upload these files to production:\n";
echo "   - app/Http/Controllers/JobPaymentController.php\n";
echo "   - resources/views/job-payment-form.blade.php\n";
echo "   - database/migrations/2025_11_03_131707_add_payment_order_id_to_tura_job_applied_status_table.php\n";
echo "   - Updated routes/api.php\n";
echo "   - Updated config/services.php\n\n";

echo "2. Update .env file with:\n";
echo "   DB_CONNECTION=mysql\n";
echo "   DB_HOST=127.0.0.1\n";
echo "   DB_PORT=3306\n";
echo "   DB_DATABASE=u608187177_municipal_prod\n";
echo "   DB_USERNAME=u608187177_municipal_prod\n";
echo "   DB_PASSWORD=Municipal@1468\n";
echo "   PAYMENT_KEY=YOUR_PRODUCTION_SBI_EPAY_KEY\n";
echo "   APP_URL=https://laravelv2.turamunicipalboard.com\n\n";

echo "3. Run these commands on production server:\n";
echo "   php artisan migrate --path=database/migrations/2025_11_03_131707_add_payment_order_id_to_tura_job_applied_status_table.php\n";
echo "   php artisan config:clear\n";
echo "   php artisan cache:clear\n";
echo "   php artisan route:clear\n";
echo "   php artisan config:cache\n";
echo "   php artisan route:cache\n\n";

echo "4. Test payment initiation:\n";
echo "   curl -X POST 'https://laravelv2.turamunicipalboard.com/api/job-payment/initiate' \\\n";
echo "     -H 'Content-Type: application/json' \\\n";
echo "     -H 'Authorization: Bearer YOUR_JWT_TOKEN' \\\n";
echo "     -d '{\"user_id\":10,\"job_id\":3,\"application_id\":\"TMB-2025-JOB3-0001\"}'\n\n";

// Final checklist
echo "✅ Pre-deployment Checklist:\n";
echo "□ Database credentials configured\n";
echo "□ Payment controller uploaded\n";
echo "□ Payment form view uploaded\n";
echo "□ Migration file uploaded\n";
echo "□ API routes updated\n";
echo "□ SBI ePay production keys added to .env\n";
echo "□ SSL certificate active\n";
echo "□ Email SMTP configured\n";

echo "\n🎉 Production deployment test completed!\n";
echo "Database connection verified and ready for payment system deployment.\n\n";
?>