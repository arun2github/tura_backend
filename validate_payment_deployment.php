<?php
/**
 * Quick Payment System Validation Script
 * Run this after deployment to verify everything is working
 */

echo "🧪 Payment System Validation\n";
echo "============================\n\n";

// Test the payment initiation API endpoint
echo "Testing Payment Initiation API...\n";

$baseUrl = 'https://laravelv2.turamunicipalboard.com/api';
$testData = [
    'user_id' => 10,
    'job_id' => 3,
    'application_id' => 'TMB-2025-JOB3-0001'
];

// Initialize cURL
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $baseUrl . '/job-payment/initiate',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($testData),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json',
        // 'Authorization: Bearer YOUR_JWT_TOKEN_HERE' // Uncomment and add real token
    ],
    CURLOPT_SSL_VERIFYPEER => false, // Only for testing
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ CURL Error: $error\n";
} else {
    echo "📡 HTTP Response Code: $httpCode\n";
    echo "📄 Response Body:\n";
    
    if ($response) {
        $decodedResponse = json_decode($response, true);
        if ($decodedResponse) {
            echo json_encode($decodedResponse, JSON_PRETTY_PRINT) . "\n";
            
            // Check if successful
            if (isset($decodedResponse['success']) && $decodedResponse['success']) {
                echo "✅ Payment initiation API is working!\n";
            } else {
                echo "⚠️ Payment initiation returned an error\n";
            }
        } else {
            echo $response . "\n";
        }
    } else {
        echo "Empty response\n";
    }
}

echo "\n";

// Test payment status endpoint
echo "Testing Payment Status API...\n";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $baseUrl . '/job-payment/status/TMB-2025-JOB3-0001',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        // 'Authorization: Bearer YOUR_JWT_TOKEN_HERE' // Uncomment and add real token
    ],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ CURL Error: $error\n";
} else {
    echo "📡 HTTP Response Code: $httpCode\n";
    echo "📄 Response Body:\n";
    
    if ($response) {
        $decodedResponse = json_decode($response, true);
        if ($decodedResponse) {
            echo json_encode($decodedResponse, JSON_PRETTY_PRINT) . "\n";
            
            if (isset($decodedResponse['success']) && $decodedResponse['success']) {
                echo "✅ Payment status API is working!\n";
            } else {
                echo "⚠️ Payment status API returned an error\n";
            }
        } else {
            echo $response . "\n";
        }
    } else {
        echo "Empty response\n";
    }
}

echo "\n";

// Test payment form URL
echo "Testing Payment Form URL...\n";
$formUrl = $baseUrl . '/job-payment-form?order_id=test123';
echo "🔗 Form URL: $formUrl\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $formUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; PaymentTest/1.0)'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ CURL Error: $error\n";
} else {
    echo "📡 HTTP Response Code: $httpCode\n";
    
    if ($httpCode >= 200 && $httpCode < 400) {
        if (strpos($response, 'payment') !== false || strpos($response, 'form') !== false) {
            echo "✅ Payment form URL is accessible!\n";
        } else {
            echo "⚠️ Payment form URL accessible but content may be incorrect\n";
        }
    } else {
        echo "❌ Payment form URL returned error code: $httpCode\n";
    }
}

echo "\n";
echo "🎯 Validation Summary\n";
echo "===================\n";
echo "1. Test payment initiation API with valid JWT token\n";
echo "2. Verify SBI ePay credentials in production .env\n";  
echo "3. Test email functionality\n";
echo "4. Monitor application logs for any issues\n";
echo "\n";
echo "📞 If you see errors, check:\n";
echo "- JWT authentication is working\n";
echo "- Database connection is established\n";
echo "- .env file has correct values\n";
echo "- Laravel caches are cleared\n";
echo "\n";