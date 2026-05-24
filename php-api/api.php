<?php

namespace ArdiAgents;

require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/PromptLoader.php';
require_once __DIR__ . '/Agent.php';
require_once __DIR__ . '/Orchestrator.php';
require_once __DIR__ . '/Api.php';

use ArdiAgents\Api;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$api = new Api();

try {
    $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $basePath = '/api';
    
    $path = str_starts_with($requestUri, $basePath) 
        ? substr($requestUri, strlen($basePath)) 
        : $requestUri;
    
    $path = trim($path, '/');
    $segments = explode('/', $path);
    $endpoint = $segments[0] ?? '';
    $subEndpoint = $segments[1] ?? null;

    switch ($endpoint) {
        case 'agents':
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                if ($subEndpoint) {
                    $agent = $api->getAgent($subEndpoint);
                    if ($agent) {
                        echo json_encode(['success' => true, 'data' => $agent]);
                    } else {
                        http_response_code(404);
                        echo json_encode(['success' => false, 'error' => "Agent '{$subEndpoint}' not found"]);
                    }
                } else {
                    $agents = $api->listAgents();
                    echo json_encode(['success' => true, 'data' => $agents]);
                }
            } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);
                $agentId = $input['agent_id'] ?? null;
                $context = $input['context'] ?? [];

                if (!$agentId) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'agent_id is required']);
                    exit();
                }

                $result = $api->executeAgent($agentId, $context);
                echo json_encode($result);
            }
            break;

        case 'workflows':
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                if ($subEndpoint === 'templates') {
                    $workflows = $api->listWorkflows();
                    echo json_encode(['success' => true, 'data' => $workflows]);
                } else {
                    $workflows = $api->listWorkflows();
                    echo json_encode(['success' => true, 'data' => $workflows]);
                }
            } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);
                
                if (isset($input['workflow_name'])) {
                    $workflowName = $input['workflow_name'];
                    $initialRequest = $input['initial_request'] ?? '';
                    $sessionId = $input['session_id'] ?? null;

                    $result = $api->runWorkflow($workflowName, $initialRequest, $sessionId);
                    echo json_encode($result);
                } elseif (isset($input['agent_sequence'])) {
                    $agentSequence = $input['agent_sequence'];
                    $initialRequest = $input['initial_request'] ?? '';
                    $sessionId = $input['session_id'] ?? null;

                    $result = $api->runCustomWorkflow($agentSequence, $initialRequest, $sessionId);
                    echo json_encode($result);
                } else {
                    $initialRequest = $input['initial_request'] ?? '';
                    $sessionId = $input['session_id'] ?? null;

                    $result = $api->runDefaultWorkflow($initialRequest, $sessionId);
                    echo json_encode($result);
                }
            }
            break;

        case 'sessions':
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                if ($subEndpoint) {
                    $state = $api->getSessionState($subEndpoint);
                    if ($state) {
                        echo json_encode(['success' => true, 'data' => $state]);
                    } else {
                        http_response_code(404);
                        echo json_encode(['success' => false, 'error' => "Session '{$subEndpoint}' not found"]);
                    }
                } else {
                    $sessions = $api->listSessions();
                    echo json_encode(['success' => true, 'data' => $sessions]);
                }
            } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE' && $subEndpoint) {
                $result = $api->clearSession($subEndpoint);
                echo json_encode(['success' => $result]);
            }
            break;

        default:
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Endpoint not found',
                'available_endpoints' => [
                    'GET /api/agents - List all agents',
                    'GET /api/agents/{id} - Get specific agent',
                    'POST /api/agents - Execute agent',
                    'GET /api/workflows - List workflows',
                    'POST /api/workflows - Run workflow',
                    'GET /api/sessions - List sessions',
                    'GET /api/sessions/{id} - Get session state',
                    'DELETE /api/sessions/{id} - Clear session'
                ]
            ]);
    }
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
