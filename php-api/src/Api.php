<?php

namespace ArdiAgents;

class Api
{
    private Config $config;
    private PromptLoader $promptLoader;
    private Agent $agent;
    private Orchestrator $orchestrator;

    public function __construct()
    {
        $this->config = Config::getInstance();
        $this->promptLoader = new PromptLoader();
        $this->agent = new Agent($this->config, $this->promptLoader);
        $this->orchestrator = new Orchestrator($this->config, $this->agent);
    }

    public function listAgents(): array
    {
        $agents = $this->config->getAgents();
        $result = [];

        foreach ($agents as $id => $agent) {
            $result[] = [
                'id' => $id,
                'name' => $agent['name'],
                'role' => $agent['role'],
                'model' => $agent['model'],
                'provider' => $agent['provider'],
                'temperature' => $agent['temperature']
            ];
        }

        return $result;
    }

    public function getAgent(string $agentId): ?array
    {
        $agent = $this->config->getAgent($agentId);

        if (!$agent) {
            return null;
        }

        return [
            'id' => $agentId,
            'name' => $agent['name'],
            'role' => $agent['role'],
            'model' => $agent['model'],
            'provider' => $agent['provider'],
            'temperature' => $agent['temperature'],
            'prompt_file' => $agent['prompt_file']
        ];
    }

    public function executeAgent(string $agentId, array $context = []): array
    {
        try {
            return $this->agent->execute($agentId, $context);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function listWorkflows(): array
    {
        $workflows = $this->config->getWorkflows();
        $result = [];

        foreach ($workflows as $name => $steps) {
            $result[] = [
                'name' => $name,
                'steps' => $steps,
                'step_count' => count($steps)
            ];
        }

        return $result;
    }

    public function runWorkflow(string $workflowName, string $initialRequest, ?string $sessionId = null): array
    {
        try {
            return $this->orchestrator->executeWorkflow($workflowName, $initialRequest, $sessionId);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'workflow_name' => $workflowName
            ];
        }
    }

    public function runCustomWorkflow(array $agentSequence, string $initialRequest, ?string $sessionId = null): array
    {
        try {
            return $this->orchestrator->executeCustomWorkflow($agentSequence, $initialRequest, $sessionId);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function runDefaultWorkflow(string $initialRequest, ?string $sessionId = null): array
    {
        try {
            return $this->orchestrator->executeDefaultWorkflow($initialRequest, $sessionId);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getSessionState(string $sessionId): ?array
    {
        return $this->orchestrator->getSessionState($sessionId);
    }

    public function listSessions(): array
    {
        return $this->orchestrator->listSessions();
    }

    public function clearSession(string $sessionId): bool
    {
        return $this->orchestrator->clearSession($sessionId);
    }
}
