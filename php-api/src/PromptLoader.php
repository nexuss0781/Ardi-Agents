<?php
/**
 * PromptLoader Class
 * 
 * Loads and renders prompt templates from markdown files
 */
class PromptLoader {
    private $promptsDir;

    public function __construct() {
        $this->promptsDir = __DIR__ . '/../prompts';
    }

    /**
     * Load a prompt template by agent name
     */
    public function loadPrompt(string $agentName): string {
        $filePath = $this->promptsDir . '/' . $agentName . '.md';
        
        if (!file_exists($filePath)) {
            throw new Exception("Prompt file not found: {$filePath}");
        }

        return file_get_contents($filePath);
    }

    /**
     * Render a prompt with variables
     */
    public function renderPrompt(string $agentName, array $variables = []): string {
        $prompt = $this->loadPrompt($agentName);

        // Replace {{variable}} placeholders
        foreach ($variables as $key => $value) {
            $prompt = str_replace('{{' . $key . '}}', $value, $prompt);
        }

        return $prompt;
    }

    /**
     * Check if a prompt exists
     */
    public function promptExists(string $agentName): bool {
        $filePath = $this->promptsDir . '/' . $agentName . '.md';
        return file_exists($filePath);
    }

    /**
     * List all available prompts
     */
    public function listPrompts(): array {
        $prompts = [];
        
        if (!is_dir($this->promptsDir)) {
            return $prompts;
        }

        $files = scandir($this->promptsDir);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'md') {
                $prompts[] = pathinfo($file, PATHINFO_FILENAME);
            }
        }

        return $prompts;
    }
}
