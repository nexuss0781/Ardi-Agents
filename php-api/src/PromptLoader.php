<?php

namespace ArdiAgents;

class PromptLoader
{
    private string $promptsPath;

    public function __construct()
    {
        $this->promptsPath = __DIR__ . '/../prompts/';
    }

    public function loadPrompt(string $promptFile): string
    {
        $filePath = $this->promptsPath . $promptFile;

        if (!file_exists($filePath)) {
            throw new \RuntimeException("Prompt file not found: {$filePath}");
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException("Failed to read prompt file: {$filePath}");
        }

        return trim($content);
    }

    public function renderPrompt(string $promptTemplate, array $variables): string
    {
        $rendered = $promptTemplate;

        foreach ($variables as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $rendered = str_replace($placeholder, (string)$value, $rendered);
        }

        return $rendered;
    }

    public function getPromptForAgent(string $agentId, Config $config, array $context = []): string
    {
        $agent = $config->getAgent($agentId);

        if (!$agent) {
            throw new \InvalidArgumentException("Agent not found: {$agentId}");
        }

        $promptFile = $agent['prompt_file'];
        $template = $this->loadPrompt($promptFile);

        $variables = [
            'user_request' => $context['user_request'] ?? '',
            'context' => $context['context'] ?? json_encode($context, JSON_PRETTY_PRINT)
        ];

        return $this->renderPrompt($template, $variables);
    }
}
