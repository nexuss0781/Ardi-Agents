<?php
/**
 * Config Class
 * 
 * Loads and manages configuration from YAML file
 * Singleton pattern for global access
 */
class Config {
    private static $instance = null;
    private $config = [];
    private $configPath;

    private function __construct() {
        $this->configPath = __DIR__ . '/../agents_config.yaml';
        $this->loadConfig();
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadConfig(): void {
        if (!file_exists($this->configPath)) {
            throw new Exception("Configuration file not found: {$this->configPath}");
        }

        $yamlContent = file_get_contents($this->configPath);
        $this->config = $this->parseYaml($yamlContent);
    }

    /**
     * Simple YAML parser for our specific config format
     * Supports nested structures, lists, and key-value pairs
     */
    private function parseYaml(string $yaml): array {
        $lines = explode("\n", $yaml);
        $result = [];
        $stack = [0 => &$result];
        $indentStack = [-1];
        
        foreach ($lines as $line) {
            // Skip empty lines and comments
            if (trim($line) === '' || trim($line)[0] === '#') {
                continue;
            }

            // Calculate indentation
            preg_match('/^(\s*)/', $line, $matches);
            $indent = strlen($matches[1]);
            $content = trim($line);

            // Pop stack until we find the right parent level
            while (count($indentStack) > 1 && end($indentStack) >= $indent) {
                array_pop($stack);
                array_pop($indentStack);
            }

            $current = &$stack[count($stack) - 1];

            // Parse list item
            if (strpos($content, '- ') === 0) {
                $value = trim(substr($content, 2));
                if ($value !== '') {
                    $current[] = $value;
                }
                continue;
            }

            // Parse key-value pair
            if (strpos($content, ':') !== false) {
                $parts = explode(':', $content, 2);
                $key = trim($parts[0]);
                $value = isset($parts[1]) ? trim($parts[1]) : '';

                // Remove quotes if present
                if ((strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) ||
                    (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1)) {
                    $value = substr($value, 1, -1);
                }

                if ($value === '') {
                    // This is a parent key, create new nested array
                    $current[$key] = [];
                    $stack[] = &$current[$key];
                    $indentStack[] = $indent;
                } else {
                    // This is a leaf key-value pair
                    // Convert boolean and numeric strings
                    if ($value === 'true') {
                        $value = true;
                    } elseif ($value === 'false') {
                        $value = false;
                    } elseif (is_numeric($value)) {
                        $value = floatval($value);
                        if (floor($value) == $value) {
                            $value = intval($value);
                        }
                    }
                    $current[$key] = $value;
                }
            }
        }

        return $result;
    }

    public function getAgents(): array {
        return $this->config['agents'] ?? [];
    }

    public function getAgent(string $agentId): ?array {
        $agents = $this->getAgents();
        return $agents[$agentId] ?? null;
    }

    public function getWorkflowTemplates(): array {
        return $this->config['workflow_templates'] ?? [];
    }

    public function getWorkflowTemplate(string $templateName): ?array {
        $templates = $this->getWorkflowTemplates();
        return $templates[$templateName] ?? null;
    }

    public function getDefaultWorkflow(): array {
        return $this->config['default_workflow'] ?? [];
    }

    public function getAll(): array {
        return $this->config;
    }
}
