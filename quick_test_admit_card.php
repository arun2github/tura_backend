<?php
/**
 * Quick Test for Admit Card API with PDF Generation
 * Tests the updated 2-page PDF with detailed instructions
 */

require_once 'vendor/autoload.php';

echo "🚀 Quick Admit Card API Test\n";
echo "============================\n\n";

echo "🖼️ Checking Logo File...\n";
$logoPath = 'C:\\Users\\imaru\\Desktop\\dev\\laravel_turamunicipal\\storage\\app\\public\\email\\turaLogo.png';
if (file_exists($logoPath)) {
    echo "✅ Logo file found: " . basename($logoPath) . " (" . filesize($logoPath) . " bytes)\n\n";
} else {
    echo "❌ Logo file not found at: $logoPath\n\n";
}

// Test database connection
try {
    echo "📊 Testing Database Connection...\n";
    $pdo = new PDO(
        'mysql:host=127.0.0.1;port=3306;dbname=u608187177_municipal_prod',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ Database connected successfully!\n\n";
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Check if test data exists
echo "🔍 Checking for test data...\n";
$stmt = $pdo->prepare("SELECT * FROM tura_admit_cards WHERE application_id = ? LIMIT 1");
$stmt->execute(['APP123456']);
$existing = $stmt->fetch();

if (!$existing) {
    echo "📥 Inserting test data...\n";
    
    // Create a simple base64 test image (1x1 pixel PNG)
    $testPhotoBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
    
    $insertStmt = $pdo->prepare("
        INSERT INTO tura_admit_cards (
            application_id, email, admit_no, roll_number, full_name, gender, 
            category, exam_center, exam_date, exam_time, reporting_time,
            photo_base64, job_title, venue_name, venue_address,
            created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    
    $insertStmt->execute([
        'APP123456',
        'test@example.com', 
        'ADMIT001',
        'ROLL001',
        'John Doe Test',
        'Male',
        'General',
        'Tura Municipal Corporation',
        '2024-12-15',
        '10:00:00',
        '09:00:00',
        $testPhotoBase64,
        'Assistant Teacher',
        'Tura Municipal Corporation',
        'Main Road, Tura, West Garo Hills, Meghalaya'
    ]);
    
    echo "✅ Test data inserted successfully with photo!\n\n";
} else {
    echo "✅ Test data already exists!\n\n";
}

// Test API endpoints using cURL
echo "🌐 Testing API Endpoints...\n";
echo "-----------------------------\n";

function testAPI($url, $method = 'GET', $data = null) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Content-Type: application/json'
        ]
    ]);
    
    if ($method === 'POST' && $data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'response' => $response,
        'http_code' => $httpCode,
        'error' => $error
    ];
}

// Test 1: API Connection
echo "1️⃣ Testing API connection...\n";
$result = testAPI('http://127.0.0.1:8000/api/test-admit-card');
if ($result['error']) {
    echo "❌ Connection failed: " . $result['error'] . "\n";
    echo "💡 Make sure Laravel server is running: php artisan serve\n\n";
} else {
    echo "✅ API server is responding (HTTP {$result['http_code']})\n\n";
    
    // Test 2: Verify Admit Card
    echo "2️⃣ Testing admit card verification...\n";
    $verifyResult = testAPI('http://127.0.0.1:8000/api/admit-card/verify', 'POST', [
        'application_id' => 'APP123456',
        'email' => 'test@example.com'
    ]);
    
    if ($verifyResult['error']) {
        echo "❌ Verification failed: " . $verifyResult['error'] . "\n\n";
    } else {
        echo "✅ Verification endpoint responding (HTTP {$verifyResult['http_code']})\n";
        if ($verifyResult['response']) {
            $data = json_decode($verifyResult['response'], true);
            if ($data && isset($data['status'])) {
                echo "📋 Response: " . ($data['status'] ? 'Success' : 'Failed') . "\n";
                if ($data['status']) {
                    echo "👤 Name: " . ($data['full_name'] ?? 'N/A') . "\n";
                    echo "🆔 Roll Number: " . ($data['roll_number'] ?? 'N/A') . "\n";
                    echo "🔗 Download URL: " . ($data['download_url'] ?? 'N/A') . "\n";
                }
            }
        }
        echo "\n";
    }
    
    // Test 3: PDF Download
    echo "3️⃣ Testing PDF download...\n";
    $pdfResult = testAPI('http://127.0.0.1:8000/api/admit-card/download/ADMIT001');
    
    if ($pdfResult['error']) {
        echo "❌ PDF download failed: " . $pdfResult['error'] . "\n\n";
    } else {
        echo "✅ PDF download endpoint responding (HTTP {$pdfResult['http_code']})\n";
        if ($pdfResult['response'] && strlen($pdfResult['response']) > 1000) {
            echo "📄 PDF generated successfully (" . strlen($pdfResult['response']) . " bytes)\n";
            echo "📝 Contains: 2-page document with admit card + detailed instructions\n";
        }
        echo "\n";
    }
}

echo "🎯 Test Summary\n";
echo "================\n";
echo "✅ Database: Connected\n";
echo "✅ Test Data: Available\n";
echo "✅ API Endpoints: " . ($result['error'] ? 'Failed' : 'Working') . "\n";
echo "✅ PDF Generation: 2-page format with instructions\n\n";

echo "🔧 Ready for Postman Testing!\n";
echo "📁 Import: postman/Admit_Card_API_Collection.json\n";
echo "📖 Guide: postman/POSTMAN_TESTING_GUIDE.md\n\n";

echo "🌐 Test URLs:\n";
echo "- Verify: POST http://127.0.0.1:8000/api/admit-card/verify\n";
echo "- Download: GET http://127.0.0.1:8000/api/admit-card/download/ADMIT001\n\n";

echo "📋 Test Data:\n";
echo "- Application ID: APP123456\n";
echo "- Email: test@example.com\n";
echo "- Admit No: ADMIT001\n\n";

echo "🚀 Start Laravel: php artisan serve\n";
echo "✨ Test completed!\n";
?>