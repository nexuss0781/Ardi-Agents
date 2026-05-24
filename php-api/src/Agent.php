<?php
/**
 * Agent Class
 * 
 * Executes individual AI agents using LiteLLM-compatible API
 * Supports multiple providers (OpenAI, Anthropic, Google, OpenRouter, etc.)
 */
class Agent {
    private $agentId;
    private $config;
    private $promptLoader;
    private $apiKey;

    public function __construct(string $agentId, array $config) {
        $this->agentId = $agentId;
        $this->config = $config;
        $this->promptLoader = new PromptLoader();
        
        // Get API key from environment
        $provider = $config['provider'] ?? 'openai';
        $this->apiKey = $this->getApiKey($provider);
    }

    /**
     * Get API key based on provider
     */
    private function getApiKey(string $provider): string {
        switch ($provider) {
            case 'openai':
            case 'openrouter':
                $key = getenv('OPENAI_API_KEY');
                break;
            case 'anthropic':
                $key = getenv('ANTHROPIC_API_KEY');
                break;
            case 'google':
                $key = getenv('GOOGLE_API_KEY');
                break;
            case 'groq':
                $key = getenv('GROQ_API_KEY');
                break;
            case 'together_ai':
                $key = getenv('TOGETHER_API_KEY');
                break;
            case 'huggingface':
                $key = getenv('HUGGINGFACE_API_KEY');
                break;
            default:
                $key = getenv('OPENAI_API_KEY');
        }

        if (empty($key)) {
            throw new Exception("API key not found for provider: {$provider}");
        }

        return $key;
    }

    /**
     * Execute the agent with given input
     */
    public function execute(string $input, array $context = []): array {
        $maxRetries = 3;
        $retryCount = 0;
        $lastError = null;

        while ($retryCount < $maxRetries) {
            try {
                return $this->executeWithRetry($input, $context);
            } catch (Exception $e) {
                $lastError = $e;
                $retryCount++;
                
                if ($retryCount < $maxRetries) {
                    // Exponential backoff
                    $delay = pow(2, $retryCount);
                    usleep($delay * 1000000); // Convert to microseconds
                }
            }
        }

        throw new Exception("Agent execution failed after {$maxRetries} retries: " . $lastError->getMessage());
    }

    /**
     * Internal execution logic
     */
    private function executeWithRetry(string $input, array $context = []): array {
        // Render prompt
        $variables = array_merge(['input' => $input], $context);
        $prompt = $this->promptLoader->renderPrompt($this->agentId, $variables);

        // Prepare request
        $model = $this->config['model'] ?? 'gpt-3.5-turbo';
        $temperature = $this->config['temperature'] ?? 0.7;
        $responseFormat = $this->config['response_format'] ?? null;

        $requestBody = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => $temperature
        ];

        // Add response format if specified (for JSON output)
        if ($responseFormat === 'json_object') {
            $requestBody['response_format'] = ['type' => 'json_object'];
        }

        // Determine API endpoint based on provider
        $endpoint = $this->getApiEndpoint($this->config['provider']);

        // Make API call
        $response = $this->makeApiCall($endpoint, $requestBody);

        // Parse response
        $content = $response['choices'][0]['message']['content'] ?? '';
        $usage = $response['usage'] ?? [];

        return [
            'agent_id' => $this->agentId,
            'output' => $content,
            'usage' => $usage,
            'model' => $model
        ];
    }

    /**
     * Get API endpoint based on provider
     */
    private function getApiEndpoint(string $provider): string {
        $endpoints = [
            'openai' => 'https://api.openai.com/v1/chat/completions',
            'openrouter' => 'https://openrouter.ai/api/v1/chat/completions',
            'anthropic' => 'https://api.anthropic.com/v1/messages',
            'google' => 'https://generativelanguage.googleapis.com/v1beta/openai/chat/completions',
            'groq' => 'https://api.groq.com/openai/v1/chat/completions',
            'together_ai' => 'https://api.together.xyz/v1/chat/completions',
            'huggingface' => 'https://api-inference.huggingface.co/models/deepseek-ai/DeepSeek-Coder-V2-Instruct/v1/chat/completions'
        ];

        return $endpoints[$provider] ?? $endpoints['openai'];
    }

    /**
     * Make HTTP API call
     */
    private function makeApiCall(string $endpoint, array $requestBody): array {
        $ch = curl_init($endpoint);

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ];

        // Add special headers for specific providers
        if ($this->config['provider'] === 'openrouter') {
            $headers[] = 'HTTP-Referer: https://github.com/nexuss0781/Ardi-Agents';
            $headers[] = 'X-Title: Ardi-Agents';
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($response === false) {
            throw new Exception("API call failed: {$error}");
        }

        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMsg = $errorData['error']['message'] ?? 'Unknown error';
            throw new Exception("API error ({$httpCode}): {$errorMsg}");
        }

        $responseData = json_decode($response, true);
        if ($responseData === null) {
            throw new Exception("Invalid JSON response from API");
        }

        return $responseData;
    }

    /**
     * Get agent configuration
     */
    public function getConfig(): array {
        return $this->config;
    }

    /**
     * Get agent ID
     */
    public function getId(): string {
        return $this->agentId;
    }
}
