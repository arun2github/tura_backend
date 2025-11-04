<?php
/**
 * Base64 Document Upload Debug Script
 * Test base64 encoding and decoding for document uploads
 */

echo "📄 Document Base64 Upload/Download Debug Tool\n";
echo "=============================================\n\n";

// Test 1: Check document upload table structure
echo "1. Checking document upload table structure...\n";
try {
    $columns = DB::select("DESCRIBE tura_job_documents_upload");
    echo "✅ Table exists with columns:\n";
    foreach ($columns as $col) {
        echo "   - {$col->Field} ({$col->Type})\n";
    }
    
    // Check if file_base64 column has proper type
    $base64Column = collect($columns)->firstWhere('Field', 'file_base64');
    if ($base64Column) {
        echo "✅ file_base64 column type: {$base64Column->Type}\n";
        if (stripos($base64Column->Type, 'longtext') !== false) {
            echo "✅ Column type is suitable for large base64 data\n";
        } else {
            echo "⚠️  Column type may be too small for large base64 data\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error checking table: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Check sample document
echo "2. Checking sample uploaded document...\n";
try {
    $sampleDoc = DB::table('tura_job_documents_upload')->first();
    if ($sampleDoc) {
        echo "✅ Found sample document:\n";
        echo "   - ID: {$sampleDoc->id}\n";
        echo "   - File Name: {$sampleDoc->file_name}\n";
        echo "   - File Extension: {$sampleDoc->file_extension}\n";
        echo "   - File Size: {$sampleDoc->file_size} bytes\n";
        
        // Check base64 data
        if ($sampleDoc->file_base64) {
            $base64Length = strlen($sampleDoc->file_base64);
            echo "   - Base64 Data Length: {$base64Length} characters\n";
            
            // Check if it has proper data URL format
            if (strpos($sampleDoc->file_base64, 'data:') === 0) {
                echo "   ✅ Base64 has proper data URL format\n";
                
                // Extract mime type
                preg_match('/data:([^;]+);base64,/', $sampleDoc->file_base64, $matches);
                if (isset($matches[1])) {
                    echo "   - Detected MIME type: {$matches[1]}\n";
                }
                
                // Get actual base64 data (after the prefix)
                $base64Data = substr($sampleDoc->file_base64, strpos($sampleDoc->file_base64, ',') + 1);
                
                // Test if base64 is valid
                $decoded = base64_decode($base64Data, true);
                if ($decoded !== false) {
                    echo "   ✅ Base64 data is valid\n";
                    echo "   - Decoded size: " . strlen($decoded) . " bytes\n";
                    
                    // Check if decoded size matches original file size
                    if (strlen($decoded) == $sampleDoc->file_size) {
                        echo "   ✅ Decoded size matches original file size\n";
                    } else {
                        echo "   ⚠️  Decoded size doesn't match original ({$sampleDoc->file_size} bytes)\n";
                    }
                } else {
                    echo "   ❌ Base64 data is corrupted or invalid\n";
                }
            } else {
                echo "   ❌ Base64 doesn't have proper data URL format\n";
                echo "   - First 100 chars: " . substr($sampleDoc->file_base64, 0, 100) . "\n";
            }
        } else {
            echo "   ❌ No base64 data found\n";
        }
    } else {
        echo "❌ No documents found in database\n";
    }
} catch (Exception $e) {
    echo "❌ Error checking sample document: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Create a test base64 encoding/decoding
echo "3. Testing base64 encoding/decoding process...\n";

// Create a simple test image (1x1 pixel PNG)
$testImageData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==');
$testMimeType = 'image/png';

echo "✅ Created test image data (" . strlen($testImageData) . " bytes)\n";

// Encode as data URL
$testBase64 = base64_encode($testImageData);
$testDataUrl = 'data:' . $testMimeType . ';base64,' . $testBase64;

echo "✅ Encoded as data URL (" . strlen($testDataUrl) . " characters)\n";
echo "   - Data URL: " . substr($testDataUrl, 0, 50) . "...\n";

// Decode back
$extractedBase64 = substr($testDataUrl, strpos($testDataUrl, ',') + 1);
$decodedData = base64_decode($extractedBase64);

if ($decodedData === $testImageData) {
    echo "✅ Encoding/decoding test passed\n";
} else {
    echo "❌ Encoding/decoding test failed\n";
}

echo "\n";

// Test 4: Recommendations
echo "4. Recommendations for fixing base64 issues:\n";
echo "==========================================\n";

echo "✅ Database Structure:\n";
echo "   - Ensure file_base64 column is LONGTEXT (can store up to 4GB)\n";
echo "   - Current recommended migration:\n";
echo "   ALTER TABLE tura_job_documents_upload MODIFY file_base64 LONGTEXT;\n\n";

echo "✅ Encoding Process (Upload):\n";
echo "   1. Read file content: file_get_contents(\$file->getRealPath())\n";
echo "   2. Encode to base64: base64_encode(\$fileContent)\n";
echo "   3. Add data URL prefix: 'data:' . \$mimeType . ';base64,' . \$base64\n";
echo "   4. Store complete data URL in database\n\n";

echo "✅ Decoding Process (Download):\n";
echo "   1. Get data URL from database\n";
echo "   2. Extract base64 part: substr(\$dataUrl, strpos(\$dataUrl, ',') + 1)\n";
echo "   3. Decode: base64_decode(\$base64Data)\n";
echo "   4. Return with proper headers\n\n";

echo "✅ Frontend Display:\n";
echo "   - For images: <img src=\"data:image/jpeg;base64,{base64data}\" />\n";
echo "   - For PDFs: Use object/embed tag or convert to blob URL\n";
echo "   - For downloads: Create blob and trigger download\n\n";

// Test 5: Generate sample HTML for testing
echo "5. Sample HTML for testing image display:\n";
echo "========================================\n";

$sampleHtml = '
<!DOCTYPE html>
<html>
<head>
    <title>Base64 Image Test</title>
</head>
<body>
    <h1>Base64 Image Test</h1>
    
    <!-- Test with the sample document from database -->
    <div>
        <h2>From Database:</h2>
        <img src="' . ($sampleDoc->file_base64 ?? $testDataUrl) . '" style="max-width: 300px; border: 1px solid #ccc;" alt="Test Image" />
    </div>
    
    <!-- Test with manually created base64 -->
    <div>
        <h2>Manual Test:</h2>
        <img src="' . $testDataUrl . '" style="max-width: 300px; border: 1px solid #ccc;" alt="Manual Test" />
    </div>
    
    <script>
        // JavaScript function to test base64 decoding
        function testBase64(base64String) {
            try {
                // For data URLs, extract the base64 part
                const base64Data = base64String.includes(",") 
                    ? base64String.split(",")[1] 
                    : base64String;
                
                // Test if valid base64
                const decoded = atob(base64Data);
                console.log("Base64 is valid, decoded length:", decoded.length);
                return true;
            } catch (e) {
                console.error("Invalid base64:", e);
                return false;
            }
        }
        
        // Test the base64 from database
        console.log("Testing base64 from database...");
        testBase64("' . ($sampleDoc->file_base64 ?? $testDataUrl) . '");
    </script>
</body>
</html>';

// Save sample HTML file
file_put_contents('base64_test.html', $sampleHtml);
echo "✅ Created base64_test.html for testing image display\n";
echo "   Open this file in browser to test base64 image rendering\n\n";

echo "🎉 Debug completed!\n";
echo "Check the recommendations above to fix base64 image display issues.\n";
?>