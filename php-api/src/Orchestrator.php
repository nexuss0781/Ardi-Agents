<?php

namespace ArdiAgents;

class Orchestrator
{
    private Config $config;
    private Agent $agent;
    private array $sessionStates = [];

    public function __construct(Config $config, Agent $agent)
    {
        $this->config = $config;
        $this->agent = $agent;
    }

    public function executeWorkflow(
        string $workflowName,
        string $initialRequest,
        ?string $sessionId = null
    ): array {
        $workflow = $this->config->getWorkflow($workflowName);

        if (!$workflow) {
            throw new \InvalidArgumentException("Workflow not found: {$workflowName}");
        }

        return $this->executeAgentSequence($workflow, $initialRequest, $sessionId);
    }

    public function executeCustomWorkflow(
        array $agentSequence,
        string $initialRequest,
        ?string $sessionId = null
    ): array {
        return $this->executeAgentSequence($agentSequence, $initialRequest, $sessionId);
    }

    public function executeDefaultWorkflow(string $initialRequest, ?string $sessionId = null): array
    {
        $workflow = $this->config->getDefaultWorkflow();
        return $this->executeAgentSequence($workflow, $initialRequest, $sessionId);
    }

    private function executeAgentSequence(
        array $agentSequence,
        string $initialRequest,
        ?string $sessionId
    ): array {
        $sessionId = $sessionId ?? uniqid('session_', true);

        $state = [
            'session_id' => $sessionId,
            'initial_request' => $initialRequest,
            'steps' => [],
            'context_history' => [],
            'started_at' => date('c'),
            'status' => 'running'
        ];

        $currentContext = ['user_request' => $initialRequest];

        foreach ($agentSequence as $index => $agentId) {
            $stepResult = $this->executeAgentStep($agentId, $currentContext, $index);

            $state['steps'][] = $stepResult;
            $state['context_history'][] = [
                'agent_id' => $agentId,
                'response' => $stepResult['response'] ?? null,
                'error' => $stepResult['error'] ?? null
            ];

            if (!$stepResult['success']) {
                $state['status'] = 'failed';
                $state['failed_at_step'] = $index;
                break;
            }

            // Update context with agent response for next agent
            $currentContext['context'] = $stepResult['response'] ?? '';
            $currentContext['previous_agents'] = array_slice($agentSequence, 0, $index + 1);
        }

        if ($state['status'] === 'running') {
            $state['status'] = 'completed';
        }

        $state['completed_at'] = date('c');
        $this->sessionStates[$sessionId] = $state;

        return $state;
    }

    private function executeAgentStep(string $agentId, array $context, int $stepIndex): array
    {
        try {
            $result = $this->agent->execute($agentId, $context);

            return array_merge([
                'step_index' => $stepIndex,
                'agent_id' => $agentId
            ], $result);
        } catch (\Exception $e) {
            return [
                'step_index' => $stepIndex,
                'agent_id' => $agentId,
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => date('c')
            ];
        }
    }

    public function getSessionState(string $sessionId): ?array
    {
        return $this->sessionStates[$sessionId] ?? null;
    }

    public function listSessions(): array
    {
        return array_keys($this->sessionStates);
    }

    public function clearSession(string $sessionId): bool
    {
        if (isset($this->sessionStates[$sessionId])) {
            unset($this->sessionStates[$sessionId]);
            return true;
        }
        return false;
    }
}
