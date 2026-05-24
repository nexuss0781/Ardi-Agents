<?php
/**
 * Orchestrator Class
 * 
 * Manages workflow orchestration and agent execution sequences
 * Handles state management, error handling, and retry logic
 */
class Orchestrator {
    private $config;
    private $promptLoader;
    private $sessions = [];

    public function __construct() {
        $this->config = Config::getInstance();
        $this->promptLoader = new PromptLoader();
    }

    /**
     * Execute a single agent
     */
    public function executeAgent(string $agentId, string $input, array $context = []): array {
        $agentConfig = $this->config->getAgent($agentId);
        
        if ($agentConfig === null) {
            throw new Exception("Agent '{$agentId}' not found");
        }

        if (!$this->promptLoader->promptExists($agentId)) {
            throw new Exception("Prompt file not found for agent '{$agentId}'");
        }

        $agent = new Agent($agentId, $agentConfig);
        return $agent->execute($input, $context);
    }

    /**
     * Execute a workflow (sequence of agents)
     */
    public function executeWorkflow(array $agentSequence, string $initialRequest, ?string $sessionId = null): array {
        if (empty($agentSequence)) {
            throw new Exception("Workflow agent sequence is empty");
        }

        // Create or retrieve session
        if ($sessionId === null) {
            $sessionId = $this->createSession();
        }

        $session = $this->getSession($sessionId);
        $session['status'] = 'running';
        $session['started_at'] = date('c');
        $session['workflow'] = $agentSequence;
        $session['initial_request'] = $initialRequest;

        $currentInput = $initialRequest;
        $results = [];
        $errors = [];

        foreach ($agentSequence as $index => $agentId) {
            try {
                $agentConfig = $this->config->getAgent($agentId);
                
                if ($agentConfig === null) {
                    throw new Exception("Agent '{$agentId}' not found in configuration");
                }

                if (!$this->promptLoader->promptExists($agentId)) {
                    throw new Exception("Prompt file not found for agent '{$agentId}'");
                }

                $agent = new Agent($agentId, $agentConfig);
                $result = $agent->execute($currentInput, [
                    'step' => (string)($index + 1),
                    'total_steps' => (string)count($agentSequence),
                    'previous_output' => $currentInput
                ]);

                $results[] = [
                    'step' => $index + 1,
                    'agent_id' => $agentId,
                    'status' => 'success',
                    'output' => $result['output'],
                    'model' => $result['model'],
                    'usage' => $result['usage']
                ];

                // Pass output to next agent as input
                $currentInput = $result['output'];

                // Update session state
                $session['current_step'] = $index + 1;
                $session['total_steps'] = count($agentSequence);
                $session['last_output'] = $currentInput;
                $this->saveSession($sessionId, $session);

            } catch (Exception $e) {
                $errors[] = [
                    'step' => $index + 1,
                    'agent_id' => $agentId,
                    'error' => $e->getMessage()
                ];

                $session['status'] = 'failed';
                $session['error'] = $e->getMessage();
                $this->saveSession($sessionId, $session);

                throw new Exception("Workflow failed at step " . ($index + 1) . " ({$agentId}): " . $e->getMessage());
            }
        }

        // Mark session as completed
        $session['status'] = 'completed';
        $session['completed_at'] = date('c');
        $session['final_output'] = $currentInput;
        $session['results'] = $results;
        $this->saveSession($sessionId, $session);

        return [
            'session_id' => $sessionId,
            'status' => 'completed',
            'workflow' => $agentSequence,
            'initial_request' => $initialRequest,
            'final_output' => $currentInput,
            'steps' => $results,
            'errors' => $errors
        ];
    }

    /**
     * Execute a workflow template
     */
    public function executeTemplate(string $templateName, string $initialRequest): array {
        $template = $this->config->getWorkflowTemplate($templateName);
        
        if ($template === null) {
            throw new Exception("Workflow template '{$templateName}' not found");
        }

        return $this->executeWorkflow($template, $initialRequest);
    }

    /**
     * Execute default workflow
     */
    public function executeDefault(string $initialRequest): array {
        $defaultWorkflow = $this->config->getDefaultWorkflow();
        
        if (empty($defaultWorkflow)) {
            throw new Exception("No default workflow configured");
        }

        return $this->executeWorkflow($defaultWorkflow, $initialRequest);
    }

    /**
     * Session Management
     */

    private function createSession(): string {
        $sessionId = uniqid('session_', true);
        $this->sessions[$sessionId] = [
            'id' => $sessionId,
            'status' => 'pending',
            'created_at' => date('c'),
            'started_at' => null,
            'completed_at' => null,
            'workflow' => [],
            'initial_request' => '',
            'current_step' => 0,
            'total_steps' => 0,
            'last_output' => '',
            'final_output' => '',
            'results' => [],
            'error' => null
        ];
        return $sessionId;
    }

    private function getSession(string $sessionId): array {
        if (!isset($this->sessions[$sessionId])) {
            throw new Exception("Session '{$sessionId}' not found");
        }
        return $this->sessions[$sessionId];
    }

    private function saveSession(string $sessionId, array $session): void {
        $this->sessions[$sessionId] = $session;
    }

    public function getSessionState(string $sessionId): array {
        return $this->getSession($sessionId);
    }

    public function listSessions(): array {
        return array_values($this->sessions);
    }

    public function clearSession(string $sessionId): bool {
        if (isset($this->sessions[$sessionId])) {
            unset($this->sessions[$sessionId]);
            return true;
        }
        return false;
    }

    /**
     * Get available workflow templates
     */
    public function getWorkflowTemplates(): array {
        return array_keys($this->config->getWorkflowTemplates());
    }

    /**
     * Get workflow template details
     */
    public function getWorkflowTemplateDetails(string $templateName): ?array {
        $template = $this->config->getWorkflowTemplate($templateName);
        if ($template === null) {
            return null;
        }

        $details = [
            'name' => $templateName,
            'agents' => $template,
            'step_count' => count($template),
            'agent_details' => []
        ];

        foreach ($template as $agentId) {
            $agentConfig = $this->config->getAgent($agentId);
            if ($agentConfig !== null) {
                $details['agent_details'][$agentId] = [
                    'description' => $agentConfig['description'] ?? '',
                    'model' => $agentConfig['model'] ?? '',
                    'provider' => $agentConfig['provider'] ?? ''
                ];
            }
        }

        return $details;
    }
}
