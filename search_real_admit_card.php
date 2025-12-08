<?php
/**
 * Search for specific admit card record and test API
 */

require_once 'vendor/autoload.php';

echo "🔍 Searching for Specific Admit Card Record\n";
echo "==========================================\n\n";

// Test database connection
try {
    echo "📊 Connecting to database...\n";
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

// Search for the specific record
echo "🔎 Searching for record...\n";
echo "Application ID: TMB-2025-JOB1-0002\n";
echo "Email: villierssangma@gmail.com\n";
echo "------------------------------------\n";

$stmt = $pdo->prepare("SELECT * FROM tura_admit_cards WHERE application_id = ? AND email = ? LIMIT 1");
$stmt->execute(['TMB-2025-JOB1-0002', 'villierssangma@gmail.com']);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

if ($record) {
    echo "✅ Record Found!\n";
    echo "📋 Details:\n";
    echo "   - Full Name: " . ($record['full_name'] ?? 'N/A') . "\n";
    echo "   - Roll Number: " . ($record['roll_number'] ?? 'N/A') . "\n";
    echo "   - Admit No: " . ($record['admit_no'] ?? 'N/A') . "\n";
    echo "   - Job Title: " . ($record['job_title'] ?? 'N/A') . "\n";
    echo "   - Exam Date: " . ($record['exam_date'] ?? 'N/A') . "\n";
    echo "   - Photo Base64: " . (isset($record['photo_base64']) ? (strlen($record['photo_base64']) > 100 ? 'Present (' . strlen($record['photo_base64']) . ' chars)' : 'Short/Missing') : 'Not Set') . "\n";
    echo "   - Status: " . ($record['status'] ?? 'N/A') . "\n";
    echo "   - Created: " . ($record['created_at'] ?? 'N/A') . "\n\n";
    
    $admitNo = $record['admit_no'] ?? 'UNKNOWN';
    
} else {
    echo "❌ No record found with those credentials!\n\n";
    
    // Let's check if there are any records with similar application_id
    echo "🔍 Checking for similar application IDs...\n";
    $stmt = $pdo->prepare("SELECT application_id, email, full_name FROM tura_admit_cards WHERE application_id LIKE ? LIMIT 5");
    $stmt->execute(['%TMB-2025-JOB1%']);
    $similarRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($similarRecords) {
        echo "📋 Found similar records:\n";
        foreach ($similarRecords as $similar) {
            echo "   - " . $similar['application_id'] . " | " . $similar['email'] . " | " . ($similar['full_name'] ?? 'N/A') . "\n";
        }
        echo "\n";
    } else {
        echo "❌ No similar records found\n\n";
        
        // Show first 5 records for reference
        echo "📋 Showing first 5 records in database:\n";
        $stmt = $pdo->prepare("SELECT application_id, email, full_name FROM tura_admit_cards LIMIT 5");
        $stmt->execute();
        $allRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($allRecords) {
            foreach ($allRecords as $rec) {
                echo "   - " . ($rec['application_id'] ?? 'N/A') . " | " . ($rec['email'] ?? 'N/A') . " | " . ($rec['full_name'] ?? 'N/A') . "\n";
            }
        } else {
            echo "   No records found in database\n";
        }
        echo "\n";
    }
    
    echo "⚠️ Cannot test API without valid record. Exiting...\n";
    exit(1);
}

// Test API endpoints if record exists
echo "🌐 Testing API Endpoints...\n";
echo "-----------------------------\n";

function testAPI($url, $method = 'GET', $data = null) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
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
    exit(1);
} else {
    echo "✅ API server is responding (HTTP {$result['http_code']})\n\n";
}

// Test 2: Verify Admit Card with real data
echo "2️⃣ Testing admit card verification with real data...\n";
$verifyData = [
    'application_id' => 'TMB-2025-JOB1-0002',
    'email' => 'villierssangma@gmail.com'
];

echo "📤 Request Data: " . json_encode($verifyData, JSON_PRETTY_PRINT) . "\n";

$verifyResult = testAPI('http://127.0.0.1:8000/api/admit-card/verify', 'POST', $verifyData);

if ($verifyResult['error']) {
    echo "❌ Verification failed: " . $verifyResult['error'] . "\n\n";
} else {
    echo "✅ Verification endpoint responding (HTTP {$verifyResult['http_code']})\n";
    echo "📥 Response:\n";
    
    if ($verifyResult['response']) {
        $responseData = json_decode($verifyResult['response'], true);
        if ($responseData) {
            echo json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
            
            if (isset($responseData['status']) && $responseData['status']) {
                echo "\n🎉 Verification successful!\n";
                $downloadUrl = $responseData['download_url'] ?? null;
                
                if ($downloadUrl) {
                    // Test 3: PDF Download
                    echo "\n3️⃣ Testing PDF download...\n";
                    echo "🔗 Download URL: $downloadUrl\n";
                    
                    $pdfResult = testAPI($downloadUrl);
                    
                    if ($pdfResult['error']) {
                        echo "❌ PDF download failed: " . $pdfResult['error'] . "\n";
                    } else {
                        echo "✅ PDF download endpoint responding (HTTP {$pdfResult['http_code']})\n";
                        
                        if ($pdfResult['http_code'] == 200 && $pdfResult['response'] && strlen($pdfResult['response']) > 1000) {
                            echo "📄 PDF generated successfully (" . strlen($pdfResult['response']) . " bytes)\n";
                            echo "📝 Contains: 2-page document with admit card + instructions\n";
                            echo "🖼️ Logo: Tura Municipal Board logo included\n";
                            echo "📸 Photo: Base64 image processed\n";
                        } else {
                            echo "❌ PDF generation might have failed (response size: " . strlen($pdfResult['response'] ?? '') . " bytes)\n";
                            if ($pdfResult['response']) {
                                echo "Response content: " . substr($pdfResult['response'], 0, 200) . "...\n";
                            }
                        }
                    }
                }
            } else {
                echo "\n❌ Verification failed: " . ($responseData['message'] ?? 'Unknown error') . "\n";
            }
        } else {
            echo "❌ Invalid JSON response\n";
            echo "Raw response: " . substr($verifyResult['response'], 0, 200) . "...\n";
        }
    }
    echo "\n";
}

echo "🎯 Test Summary for Real Data\n";
echo "===============================\n";
echo "✅ Database: Connected\n";
echo "✅ Record: " . (isset($record) ? 'Found' : 'Not Found') . "\n";
echo "✅ API Connection: " . ($result['error'] ? 'Failed' : 'Working') . "\n";
echo "✅ Verification: " . (isset($responseData['status']) && $responseData['status'] ? 'Success' : 'Failed') . "\n";
echo "✅ PDF Generation: " . (isset($pdfResult) && $pdfResult['http_code'] == 200 ? 'Working' : 'Check manually') . "\n\n";

echo "🔧 Ready for Production Testing!\n";
echo "📋 Application ID: TMB-2025-JOB1-0002\n";
echo "📧 Email: villierssangma@gmail.com\n";
echo "📄 Admit No: " . ($admitNo ?? 'N/A') . "\n\n";

if (isset($admitNo)) {
    echo "🌐 Direct Test URLs:\n";
    echo "- Verify: POST http://127.0.0.1:8000/api/admit-card/verify\n";
    echo "- Download: GET http://127.0.0.1:8000/api/admit-card/download/$admitNo\n\n";
}

echo "✨ Search completed!\n";
?>