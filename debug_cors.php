<?php
/**
 * CORS Debugging Script for Tura Municipal Board
 * This script helps debug CORS issues with your API endpoints
 */

echo "🔍 CORS Configuration Debug Tool\n";
echo "================================\n\n";

// Test CORS configuration
echo "1. Current CORS Configuration:\n";
echo "-----------------------------\n";

$corsConfig = config('cors');
echo "Paths: " . json_encode($corsConfig['paths']) . "\n";
echo "Allowed Methods: " . json_encode($corsConfig['allowed_methods']) . "\n";
echo "Allowed Origins: " . json_encode($corsConfig['allowed_origins']) . "\n";
echo "Allowed Headers: " . json_encode($corsConfig['allowed_headers']) . "\n";
echo "Supports Credentials: " . ($corsConfig['supports_credentials'] ? 'Yes' : 'No') . "\n";
echo "Max Age: " . $corsConfig['max_age'] . " seconds\n";

echo "\n";

// Test environment variables
echo "2. Environment Variables:\n";
echo "------------------------\n";
echo "APP_URL: " . env('APP_URL') . "\n";
echo "FRONTEND_URL: " . env('FRONTEND_URL', 'Not set') . "\n";
echo "APP_ENV: " . env('APP_ENV') . "\n";
echo "APP_DEBUG: " . (env('APP_DEBUG') ? 'true' : 'false') . "\n";

echo "\n";

// Test middleware stack
echo "3. Middleware Check:\n";
echo "-------------------\n";
$kernel = app(\App\Http\Kernel::class);
$globalMiddleware = $kernel->getGlobalMiddleware();

$hasCorsMiddleware = false;
foreach ($globalMiddleware as $middleware) {
    if (strpos($middleware, 'HandleCors') !== false) {
        $hasCorsMiddleware = true;
        echo "✅ CORS Middleware found: $middleware\n";
        break;
    }
}

if (!$hasCorsMiddleware) {
    echo "❌ CORS Middleware not found in global middleware stack\n";
}

echo "\n";

// Test routes
echo "4. API Routes Check:\n";
echo "-------------------\n";
$routes = \Route::getRoutes();
$apiRoutes = [];
foreach ($routes as $route) {
    if (strpos($route->uri(), 'api/') === 0) {
        $apiRoutes[] = $route->methods()[0] . ' ' . $route->uri();
    }
}

echo "Found " . count($apiRoutes) . " API routes:\n";
foreach (array_slice($apiRoutes, 0, 5) as $route) {
    echo "- $route\n";
}
if (count($apiRoutes) > 5) {
    echo "- ... and " . (count($apiRoutes) - 5) . " more routes\n";
}

echo "\n";

// Generate test commands
echo "5. CORS Testing Commands:\n";
echo "------------------------\n";

$baseUrl = env('APP_URL', 'http://127.0.0.1:8000');
$frontendUrl = env('FRONTEND_URL', 'https://turamunicipalboard.com');

echo "Test OPTIONS preflight request:\n";
echo "curl -i -X OPTIONS '{$baseUrl}/api/logout' \\\n";
echo "  -H 'Origin: {$frontendUrl}' \\\n";
echo "  -H 'Access-Control-Request-Method: POST' \\\n";
echo "  -H 'Access-Control-Request-Headers: Authorization,Content-Type'\n\n";

echo "Test actual API request:\n";
echo "curl -i -X GET '{$baseUrl}/api/job-payment/status/TMB-2025-JOB3-0001' \\\n";
echo "  -H 'Origin: {$frontendUrl}' \\\n";
echo "  -H 'Accept: application/json'\n\n";

// Check for common CORS issues
echo "6. Common CORS Issues Check:\n";
echo "---------------------------\n";

$issues = [];

// Check if using wildcard with credentials
if (in_array('*', $corsConfig['allowed_origins']) && $corsConfig['supports_credentials']) {
    $issues[] = "❌ Using '*' for allowed_origins with supports_credentials=true (not allowed)";
}

// Check if localhost is allowed for development
if (!in_array('http://localhost:3000', $corsConfig['allowed_origins']) && 
    !in_array('http://127.0.0.1:3000', $corsConfig['allowed_origins'])) {
    $issues[] = "⚠️  Localhost origins not allowed (may affect local development)";
}

// Check if production domains are allowed
$productionDomains = ['https://turamunicipalboard.com', 'https://laravelv2.turamunicipalboard.com'];
foreach ($productionDomains as $domain) {
    if (!in_array($domain, $corsConfig['allowed_origins'])) {
        $issues[] = "❌ Production domain not allowed: $domain";
    }
}

if (empty($issues)) {
    echo "✅ No obvious CORS configuration issues found\n";
} else {
    foreach ($issues as $issue) {
        echo "$issue\n";
    }
}

echo "\n";

// Deployment instructions
echo "7. Deployment Instructions:\n";
echo "--------------------------\n";
echo "1. Upload updated config/cors.php to production server\n";
echo "2. Add CORS environment variables to .env:\n";
echo "   FRONTEND_URL=https://turamunicipalboard.com\n";
echo "   CORS_SUPPORTS_CREDENTIALS=true\n";
echo "   SANCTUM_STATEFUL_DOMAINS=turamunicipalboard.com,laravelv2.turamunicipalboard.com\n";
echo "3. Clear and cache configuration:\n";
echo "   php artisan config:clear\n";
echo "   php artisan config:cache\n";
echo "4. Test CORS with the commands above\n";

echo "\n🎉 CORS Debug completed!\n";
?>