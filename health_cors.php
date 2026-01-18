<?php
// Simple CORS-enabled health check
// This bypasses Laravel and works directly with PHP

// Set CORS headers first
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Origin, Accept');
header('Access-Control-Max-Age: 86400');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    echo json_encode(['message' => 'CORS preflight successful']);
    exit;
}

// Simple health response
$response = [
    'status' => true,
    'message' => 'Server is working with CORS enabled',
    'method' => $_SERVER['REQUEST_METHOD'],
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION,
    'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'cors_enabled' => true,
    'request_origin' => $_SERVER['HTTP_ORIGIN'] ?? 'No origin header'
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>