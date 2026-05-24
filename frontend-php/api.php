<?php
/**
 * Ardi-Agents PHP API Endpoint
 * Entry point for all API requests
 * 
 * This file handles CORS, routing, and dispatches requests to the Api class
 */

// Error reporting for development (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
ini_set('log_errors', 1);

// Set timezone
date_default_timezone_set('UTC');

// Include required classes
require_once __DIR__ . '/src/Config.php';
require_once __DIR__ . '/src/PromptLoader.php';
require_once __DIR__ . '/src/Agent.php';
require_once __DIR__ . '/src/Orchestrator.php';
require_once __DIR__ . '/src/Api.php';

// ==================== CORS Headers ====================
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Max-Age: 86400');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ==================== Request Handling ====================
try {
    // Get request method and URI
    $method = $_SERVER['REQUEST_METHOD'];
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    // Remove base path if present
    $basePath = '/php-api';
    if (strpos($uri, $basePath) === 0) {
        $uri = substr($uri, strlen($basePath));
    }
    
    // Ensure URI starts with /
    if ($uri === '' || $uri[0] !== '/') {
        $uri = '/' . $uri;
    }
    
    // Create API instance and handle request
    $api = new Api();
    $response = $api->handle($method, $uri);
    
    // Send response
    http_response_code($response['status']);
    
    if (isset($response['body'])) {
        echo json_encode($response['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    // Global error handler
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal Server Error',
        'message' => $e->getMessage(),
        'trace' => getenv('APP_ENV') === 'development' ? $e->getTraceAsString() : null
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
