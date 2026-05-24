<?php
/**
 * Tools API - PHP Version
 * Provides REST API endpoints to list and call all available tools.
 * Compatible with InfinityFree hosting (uses only built-in functions).
 */

// Include tool files
require_once __DIR__ . '/file_manager.php';
require_once __DIR__ . '/web_search.php';
require_once __DIR__ . '/text_processor.php';

// Tool definitions with descriptions and modern icons
$TOOLS_REGISTRY = array(
    "file_manager" => array(
        "name" => "File Manager",
        "description" => "Full CRUD operations for files and folders with move, copy, and line number display",
        "version" => "1.0.0",
        "language" => "php",
        "icon" => "📁",
        "icon_emoji" => "folder",
        "icon_svg" => "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z'/></svg>",
        "tab_id" => "file-manager-tab",
        "color" => "#3B82F6",
        "methods" => array(
            array("name" => "createFile", "description" => "Create a new file with optional content", "params" => array("path", "content"), "icon" => "📄"),
            array("name" => "readFile", "description" => "Read file content with optional line numbers", "params" => array("path", "showLineNumbers"), "icon" => "👁️"),
            array("name" => "updateFile", "description" => "Update existing file content", "params" => array("path", "content"), "icon" => "✏️"),
            array("name" => "deleteFile", "description" => "Delete a file", "params" => array("path"), "icon" => "🗑️"),
            array("name" => "createFolder", "description" => "Create a new folder/directory", "params" => array("path"), "icon" => "📂"),
            array("name" => "listFolder", "description" => "List contents of a folder", "params" => array("path", "showLineNumbers"), "icon" => "📋"),
            array("name" => "deleteFolder", "description" => "Delete a folder", "params" => array("path", "recursive"), "icon" => "🗑️"),
            array("name" => "moveItem", "description" => "Move a file or folder to a new location", "params" => array("source", "destination"), "icon" => "➡️"),
            array("name" => "copyItem", "description" => "Copy a file or folder to a new location", "params" => array("source", "destination"), "icon" => "📑"),
            array("name" => "getInfo", "description" => "Get detailed information about a file or folder", "params" => array("path"), "icon" => "ℹ️")
        )
    ),
    "web_search" => array(
        "name" => "Web Search",
        "description" => "Search and fetch content from the internet",
        "version" => "1.0.0",
        "language" => "php",
        "icon" => "🌐",
        "icon_emoji" => "globe",
        "icon_svg" => "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><circle cx='12' cy='12' r='10'/><line x1='2' y1='12' x2='22' y2='12'/><path d='M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z'/></svg>",
        "tab_id" => "web-search-tab",
        "color" => "#10B981",
        "methods" => array(
            array("name" => "fetchUrl", "description" => "Fetch content from a URL", "params" => array("url", "headers"), "icon" => "📥"),
            array("name" => "searchDuckDuckGo", "description" => "Search using DuckDuckGo", "params" => array("query", "numResults"), "icon" => "🔍"),
            array("name" => "searchWikipedia", "description" => "Search Wikipedia", "params" => array("query", "numResults"), "icon" => "📚"),
            array("name" => "getWebpageSummary", "description" => "Get webpage summary", "params" => array("url", "maxLength"), "icon" => "📝"),
            array("name" => "checkUrlStatus", "description" => "Check if a URL is accessible", "params" => array("url"), "icon" => "✅")
        )
    ),
    "text_processor" => array(
        "name" => "Text Processor",
        "description" => "Text manipulation, analysis, and transformation utilities",
        "version" => "1.0.0",
        "language" => "php",
        "icon" => "⚙️",
        "icon_emoji" => "gear",
        "icon_svg" => "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><circle cx='12' cy='12' r='3'/><path d='M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z'/></svg>",
        "tab_id" => "text-processor-tab",
        "color" => "#F59E0B",
        "methods" => array(
            array("name" => "countWords", "description" => "Count words, characters, and lines", "params" => array("text"), "icon" => "📊"),
            array("name" => "findAndReplace", "description" => "Find and replace text", "params" => array("text", "find", "replace", "caseSensitive"), "icon" => "🔄"),
            array("name" => "extractEmails", "description" => "Extract email addresses from text", "params" => array("text"), "icon" => "📧"),
            array("name" => "extractUrls", "description" => "Extract URLs from text", "params" => array("text"), "icon" => "🔗"),
            array("name" => "toCase", "description" => "Convert text to different cases", "params" => array("text", "caseType"), "icon" => "🔤"),
            array("name" => "hashText", "description" => "Generate hash of text", "params" => array("text", "algorithm"), "icon" => "🔐"),
            array("name" => "removeDuplicates", "description" => "Remove duplicate words or lines", "params" => array("text", "byLine"), "icon" => "✂️"),
            array("name" => "reverseText", "description" => "Reverse text", "params" => array("text", "byWord"), "icon" => "🔃"),
            array("name" => "truncate", "description" => "Truncate text to maximum length", "params" => array("text", "maxLength", "suffix"), "icon" => "📏")
        )
    )
);

/**
 * Send JSON response
 */
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header("Content-Type: application/json");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

/**
 * Get request URI path
 */
function getRequestPath() {
    $uri = $_SERVER['REQUEST_URI'];
    $path = parse_url($uri, PHP_URL_PATH);
    return rtrim($path, '/');
}

/**
 * Get request method
 */
function getRequestMethod() {
    return $_SERVER['REQUEST_METHOD'];
}

/**
 * Get POST data as associative array
 */
function getPostData() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    return is_array($data) ? $data : array();
}

/**
 * Handle API routing
 */
function handleRequest() {
    global $TOOLS_REGISTRY;
    
    $path = getRequestPath();
    $method = getRequestMethod();
    
    // Handle OPTIONS preflight
    if ($method === 'OPTIONS') {
        sendJsonResponse(array("success" => true), 200);
    }
    
    // Welcome endpoint
    if ($path === '' || $path === '/api') {
        sendJsonResponse(array(
            "message" => "Welcome to Tools API",
            "version" => "1.0.0",
            "endpoints" => array(
                "GET /api/tools" => "List all available tools",
                "GET /api/tools/{tool_name}" => "Get tool information",
                "POST /api/call/{tool_name}/{method}" => "Call a tool method",
                "POST /api/call/{tool_name}" => "Call a tool (auto-detect method)"
            )
        ));
    }
    
    // List tools endpoint
    if ($path === '/api/tools' || $path === '/tools') {
        $toolsList = array();
        foreach ($TOOLS_REGISTRY as $name => $info) {
            $toolsList[] = array(
                "name" => $name,
                "description" => $info["description"],
                "version" => $info["version"],
                "language" => $info["language"],
                "methods_count" => count($info["methods"])
            );
        }
        
        sendJsonResponse(array(
            "success" => true,
            "tools" => $toolsList,
            "total" => count($toolsList)
        ));
    }
    
    // Get tool info endpoint
    if (preg_match('#^/api/tools/([^/]+)$#', $path, $matches) || preg_match('#^/tools/([^/]+)$#', $path, $matches)) {
        $toolName = $matches[1];
        
        if (!isset($TOOLS_REGISTRY[$toolName])) {
            sendJsonResponse(array(
                "success" => false,
                "error" => "Tool '{$toolName}' not found",
                "code" => 404
            ), 404);
        }
        
        sendJsonResponse(array(
            "success" => true,
            "tool" => $TOOLS_REGISTRY[$toolName]
        ));
    }
    
    // Call tool endpoint
    if (preg_match('#^/api/call/([^/]+)(?:/([^/]+))?$#', $path, $matches) || 
        preg_match('#^/call/([^/]+)(?:/([^/]+))?$#', $path, $matches)) {
        
        if ($method !== 'POST') {
            sendJsonResponse(array(
                "success" => false,
                "error" => "Method not allowed. Use POST.",
                "code" => 405
            ), 405);
        }
        
        $toolName = $matches[1];
        $methodName = isset($matches[2]) ? $matches[2] : null;
        $params = getPostData();
        
        callTool($toolName, $methodName, $params, $TOOLS_REGISTRY);
    }
    
    // Not found
    sendJsonResponse(array(
        "success" => false,
        "error" => "Not Found",
        "code" => 404
    ), 404);
}

/**
 * Call a tool method
 */
function callTool($toolName, $methodName, $params, $registry) {
    if (!isset($registry[$toolName])) {
        sendJsonResponse(array(
            "success" => false,
            "error" => "Tool '{$toolName}' not found",
            "code" => 404
        ), 404);
    }
    
    try {
        // Auto-detect method from params if not provided
        if (!$methodName) {
            $methodName = isset($params['method']) ? $params['method'] : '';
        }
        
        // Get the appropriate class
        $className = '';
        switch ($toolName) {
            case 'file_manager':
                $className = 'FileManager';
                break;
            case 'web_search':
                $className = 'WebSearcher';
                break;
            case 'text_processor':
                $className = 'TextProcessor';
                break;
            default:
                sendJsonResponse(array(
                    "success" => false,
                    "error" => "Tool '{$toolName}' not implemented",
                    "code" => 404
                ), 404);
        }
        
        // Check if method exists
        if (!method_exists($className, $methodName)) {
            sendJsonResponse(array(
                "success" => false,
                "error" => "Method '{$methodName}' not found in tool '{$toolName}'",
                "code" => 400
            ), 400);
        }
        
        // Filter params based on method signature
        $reflection = new ReflectionMethod($className, $methodName);
        $validParams = array();
        
        foreach ($reflection->getParameters() as $param) {
            $paramName = $param->getName();
            if (array_key_exists($paramName, $params)) {
                $validParams[$paramName] = $params[$paramName];
            } elseif ($param->isDefaultValueAvailable()) {
                $validParams[$paramName] = $param->getDefaultValue();
            }
        }
        
        // Call the method
        $result = call_user_func_array(array($className, $methodName), $validParams);
        
        sendJsonResponse(array(
            "success" => true,
            "tool" => $toolName,
            "method" => $methodName,
            "result" => $result
        ));
        
    } catch (Exception $e) {
        sendJsonResponse(array(
            "success" => false,
            "error" => "Error executing method: " . $e->getMessage(),
            "code" => 500
        ), 500);
    }
}

// Run the API
handleRequest();
?>
