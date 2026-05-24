<?php

namespace ArdiAgents;

class Config
{
    private static ?Config $instance = null;
    private array $config;
    private string $configPath;

    private function __construct()
    {
        $this->configPath = __DIR__ . '/../config/agents_config.json';
        $this->loadConfig();
    }

    public static function getInstance(): Config
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadConfig(): void
    {
        if (!file_exists($this->configPath)) {
            throw new \RuntimeException("Configuration file not found: {$this->configPath}");
        }

        $content = file_get_contents($this->configPath);
        $this->config = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Invalid JSON in config file: " . json_last_error_msg());
        }
    }

    public function getAgents(): array
    {
        return $this->config['agents'] ?? [];
    }

    public function getAgent(string $agentId): ?array
    {
        return $this->config['agents'][$agentId] ?? null;
    }

    public function getWorkflows(): array
    {
        return $this->config['workflows'] ?? [];
    }

    public function getWorkflow(string $workflowName): ?array
    {
        return $this->config['workflows'][$workflowName] ?? null;
    }

    public function getDefaultWorkflow(): array
    {
        return $this->config['default_workflow'] ?? [];
    }

    public function getApiKey(): string
    {
        $key = getenv('OPENAI_API_KEY');
        if (!$key) {
            throw new \RuntimeException('OPENAI_API_KEY environment variable not set');
        }
        return $key;
    }
}
