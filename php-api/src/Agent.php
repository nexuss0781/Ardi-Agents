<?php

namespace ArdiAgents;

class Agent
{
    private Config $config;
    private PromptLoader $promptLoader;

    public function __construct(Config $config, PromptLoader $promptLoader)
    {
        $this->config = $config;
        $this->promptLoader = $promptLoader;
    }

    public function execute(string $agentId, array $context = [], int $maxRetries = 3): array
    {
        $agentConfig = $this->config->getAgent($agentId);

        if (!$agentConfig) {
            throw new \InvalidArgumentException("Agent not found: {$agentId}");
        }

        $prompt = $this->promptLoader->getPromptForAgent($agentId, $this->config, $context);

        $messages = [
            ['role' => 'system', 'content' => "You are {$agentConfig['name']}. {$agentConfig['role']}"],
            ['role' => 'user', 'content' => $prompt]
        ];

        $retryCount = 0;
        $lastError = null;

        while ($retryCount < $maxRetries) {
            try {
                $response = $this->callLLM(
                    $agentConfig['model'],
                    $agentConfig['provider'],
                    $messages,
                    $agentConfig['temperature'] ?? 0.3
                );

                return [
                    'success' => true,
                    'agent_id' => $agentId,
                    'agent_name' => $agentConfig['name'],
                    'response' => $response,
                    'context' => $context,
                    'timestamp' => date('c')
                ];
            } catch (\Exception $e) {
                $lastError = $e;
                $retryCount++;

                if ($retryCount < $maxRetries) {
                    sleep(pow(2, $retryCount)); // Exponential backoff
                }
            }
        }

        return [
            'success' => false,
            'agent_id' => $agentId,
            'error' => $lastError ? $lastError->getMessage() : 'Unknown error',
            'retries' => $retryCount,
            'timestamp' => date('c')
        ];
    }

    private function callLLM(string $model, string $provider, array $messages, float $temperature): string
    {
        $apiKey = $this->config->getApiKey();

        $endpoint = $this->getEndpoint($provider);
        $headers = $this->getHeaders($provider, $apiKey);
        $body = $this->getBody($provider, $model, $messages, $temperature);

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            throw new \RuntimeException(
                "LLM API call failed (HTTP {$httpCode}): " . ($error ?: $response)
            );
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Invalid JSON response from LLM API");
        }

        return $this->extractContent($provider, $data);
    }

    private function getEndpoint(string $provider): string
    {
        return match ($provider) {
            'openai' => 'https://api.openai.com/v1/chat/completions',
            'anthropic' => 'https://api.anthropic.com/v1/messages',
            default => throw new \InvalidArgumentException("Unsupported provider: {$provider}")
        };
    }

    private function getHeaders(string $provider, string $apiKey): array
    {
        return match ($provider) {
            'openai' => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ],
            'anthropic' => [
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01'
            ],
            default => throw new \InvalidArgumentException("Unsupported provider: {$provider}")
        };
    }

    private function getBody(string $provider, string $model, array $messages, float $temperature): array
    {
        return match ($provider) {
            'openai' => [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
                'max_tokens' => 4096
            ],
            'anthropic' => [
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => 4096
            ],
            default => throw new \InvalidArgumentException("Unsupported provider: {$provider}")
        };
    }

    private function extractContent(string $provider, array $data): string
    {
        return match ($provider) {
            'openai' => $data['choices'][0]['message']['content'] ?? '',
            'anthropic' => $data['content'][0]['text'] ?? '',
            default => ''
        };
    }
}
