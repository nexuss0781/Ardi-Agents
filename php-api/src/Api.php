<?php
/**
 * Api Class
 * 
 * Business logic layer for the Ardi-Agents API
 * Handles routing and request processing
 */
class Api {
    private $orchestrator;
    private $config;

    public function __construct() {
        $this->orchestrator = new Orchestrator();
        $this->config = Config::getInstance();
    }

    /**
     * Handle incoming HTTP requests
     */
    public function handle(string $method, string $uri): array {
        // Route matching
        $routes = [
            ['GET', '/agents', 'listAgents'],
            ['GET', '/agents/', 'getAgent'],
            ['POST', '/agents', 'executeAgent'],
            ['GET', '/workflows', 'listWorkflows'],
            ['POST', '/workflows', 'runWorkflow'],
            ['GET', '/sessions', 'listSessions'],
            ['GET', '/sessions/', 'getSession'],
            ['DELETE', '/sessions/', 'clearSession'],
        ];

        foreach ($routes as $route) {
            [$routeMethod, $routePattern, $handler] = $route;
            
            if ($method !== $routeMethod) {
                continue;
            }

            // Check for exact match or parameterized match
            if ($routePattern === $uri) {
                return $this->$handler();
            }
            
            // Check for parameterized routes (ending with /)
            if (strpos($routePattern, '/') !== false && strrpos($routePattern, '/') === strlen($routePattern) - 1) {
                $basePattern = rtrim($routePattern, '/');
                if (strpos($uri, $basePattern . '/') === 0) {
                    $param = substr($uri, strlen($basePattern) + 1);
                    if (!empty($param)) {
                        return $this->$handler($param);
                    }
                }
            }
        }

        return [
            'status' => 404,
            'body' => ['error' => 'Not Found', 'message' => "Route {$method} {$uri} not found"]
        ];
    }

    /**
     * GET /api/agents - List all agents
     */
    private function listAgents(): array {
        $agents = $this->config->getAgents();
        $agentList = [];

        foreach ($agents as $id => $config) {
            $agentList[] = [
                'id' => $id,
                'description' => $config['description'] ?? '',
                'model' => $config['model'] ?? '',
                'provider' => $config['provider'] ?? '',
                'temperature' => $config['temperature'] ?? 0.7
            ];
        }

        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'count' => count($agentList),
                'agents' => $agentList
            ]
        ];
    }

    /**
     * GET /api/agents/{id} - Get agent details
     */
    private function getAgent(string $agentId): array {
        $agentConfig = $this->config->getAgent($agentId);
        
        if ($agentConfig === null) {
            return [
                'status' => 404,
                'body' => ['error' => 'Not Found', 'message' => "Agent '{$agentId}' not found"]
            ];
        }

        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'agent' => [
                    'id' => $agentId,
                    'description' => $agentConfig['description'] ?? '',
                    'prompt_file' => $agentConfig['prompt_file'] ?? '',
                    'model' => $agentConfig['model'] ?? '',
                    'provider' => $agentConfig['provider'] ?? '',
                    'temperature' => $agentConfig['temperature'] ?? 0.7,
                    'response_format' => $agentConfig['response_format'] ?? null
                ]
            ]
        ];
    }

    /**
     * POST /api/agents - Execute a single agent
     */
    private function executeAgent(): array {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if ($input === null) {
            return [
                'status' => 400,
                'body' => ['error' => 'Bad Request', 'message' => 'Invalid JSON input']
            ];
        }

        $agentId = $input['agent_id'] ?? null;
        $request = $input['request'] ?? $input['input'] ?? null;

        if (empty($agentId)) {
            return [
                'status' => 400,
                'body' => ['error' => 'Bad Request', 'message' => 'agent_id is required']
            ];
        }

        if (empty($request)) {
            return [
                'status' => 400,
                'body' => ['error' => 'Bad Request', 'message' => 'request/input is required']
            ];
        }

        try {
            $result = $this->orchestrator->executeAgent($agentId, $request);
            
            return [
                'status' => 200,
                'body' => [
                    'success' => true,
                    'result' => $result
                ]
            ];
        } catch (Exception $e) {
            return [
                'status' => 500,
                'body' => ['error' => 'Execution Failed', 'message' => $e->getMessage()]
            ];
        }
    }

    /**
     * GET /api/workflows - List workflow templates
     */
    private function listWorkflows(): array {
        $templates = $this->config->getWorkflowTemplates();
        $workflowList = [];

        foreach ($templates as $name => $agents) {
            $workflowList[] = [
                'name' => $name,
                'agents' => $agents,
                'step_count' => count($agents)
            ];
        }

        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'count' => count($workflowList),
                'workflows' => $workflowList
            ]
        ];
    }

    /**
     * POST /api/workflows - Run a workflow
     */
    private function runWorkflow(): array {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if ($input === null) {
            return [
                'status' => 400,
                'body' => ['error' => 'Bad Request', 'message' => 'Invalid JSON input']
            ];
        }

        $workflowName = $input['workflow_name'] ?? null;
        $initialRequest = $input['initial_request'] ?? $input['request'] ?? null;
        $customWorkflow = $input['workflow'] ?? null;

        if (empty($initialRequest)) {
            return [
                'status' => 400,
                'body' => ['error' => 'Bad Request', 'message' => 'initial_request is required']
            ];
        }

        try {
            if (!empty($customWorkflow) && is_array($customWorkflow)) {
                // Custom workflow
                $result = $this->orchestrator->executeWorkflow($customWorkflow, $initialRequest);
            } elseif (!empty($workflowName)) {
                if ($workflowName === 'default') {
                    $result = $this->orchestrator->executeDefault($initialRequest);
                } else {
                    $result = $this->orchestrator->executeTemplate($workflowName, $initialRequest);
                }
            } else {
                return [
                    'status' => 400,
                    'body' => ['error' => 'Bad Request', 'message' => 'workflow_name or workflow is required']
                ];
            }

            return [
                'status' => 200,
                'body' => [
                    'success' => true,
                    'result' => $result
                ]
            ];
        } catch (Exception $e) {
            return [
                'status' => 500,
                'body' => ['error' => 'Execution Failed', 'message' => $e->getMessage()]
            ];
        }
    }

    /**
     * GET /api/sessions - List all sessions
     */
    private function listSessions(): array {
        $sessions = $this->orchestrator->listSessions();
        
        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'count' => count($sessions),
                'sessions' => $sessions
            ]
        ];
    }

    /**
     * GET /api/sessions/{id} - Get session state
     */
    private function getSession(string $sessionId): array {
        try {
            $session = $this->orchestrator->getSessionState($sessionId);
            
            return [
                'status' => 200,
                'body' => [
                    'success' => true,
                    'session' => $session
                ]
            ];
        } catch (Exception $e) {
            return [
                'status' => 404,
                'body' => ['error' => 'Not Found', 'message' => $e->getMessage()]
            ];
        }
    }

    /**
     * DELETE /api/sessions/{id} - Clear session
     */
    private function clearSession(string $sessionId): array {
        $deleted = $this->orchestrator->clearSession($sessionId);
        
        if ($deleted) {
            return [
                'status' => 200,
                'body' => ['success' => true, 'message' => "Session '{$sessionId}' cleared"]
            ];
        } else {
            return [
                'status' => 404,
                'body' => ['error' => 'Not Found', 'message' => "Session '{$sessionId}' not found"]
            ];
        }
    }
}
