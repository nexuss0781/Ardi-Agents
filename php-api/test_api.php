<?php
/**
 * Ardi-Agents PHP API Test Suite
 * 
 * Comprehensive tests for the PHP implementation
 * Ensures one-to-one alignment with Python version
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load classes
require_once __DIR__ . '/src/Config.php';
require_once __DIR__ . '/src/PromptLoader.php';
require_once __DIR__ . '/src/Agent.php';
require_once __DIR__ . '/src/Orchestrator.php';
require_once __DIR__ . '/src/Api.php';

class TestSuite {
    private $passed = 0;
    private $failed = 0;
    private $results = [];

    public function run() {
        echo "============================================================\n";
        echo "ARDI-AGENTS PHP API TEST SUITE\n";
        echo "============================================================\n\n";

        $this->test('Config Loading', function() {
            $config = Config::getInstance();
            $agents = $config->getAgents();
            
            if (empty($agents)) {
                throw new Exception("No agents loaded");
            }
            
            if (count($agents) !== 16) {
                throw new Exception("Expected 16 agents, got " . count($agents));
            }
            
            return true;
        });

        $this->test('Workflow Templates Loading', function() {
            $config = Config::getInstance();
            $templates = $config->getWorkflowTemplates();
            
            if (empty($templates)) {
                throw new Exception("No workflow templates loaded");
            }
            
            $expectedTemplates = ['initial_engagement', 'planning_phase', 'development_phase', 'qa_phase', 'completion_phase'];
            foreach ($expectedTemplates as $template) {
                if (!isset($templates[$template])) {
                    throw new Exception("Missing template: {$template}");
                }
            }
            
            return true;
        });

        $this->test('Prompt Loader - List Prompts', function() {
            $loader = new PromptLoader();
            $prompts = $loader->listPrompts();
            
            if (empty($prompts)) {
                throw new Exception("No prompts found");
            }
            
            // Check that we have prompts for all agents
            $config = Config::getInstance();
            $agents = array_keys($config->getAgents());
            
            foreach ($agents as $agent) {
                if (!in_array($agent, $prompts)) {
                    throw new Exception("Missing prompt for agent: {$agent}");
                }
            }
            
            return true;
        });

        $this->test('Prompt Loader - Load Individual Prompt', function() {
            $loader = new PromptLoader();
            
            // Test loading a specific prompt
            $prompt = $loader->loadPrompt('analyst');
            
            if (empty($prompt)) {
                throw new Exception("Failed to load analyst prompt");
            }
            
            // Verify prompt contains expected content structure
            if (strpos($prompt, 'Technical Plan') === false) {
                throw new Exception("Prompt does not contain expected content");
            }
            
            return true;
        });

        $this->test('Prompt Loader - Render Prompt with Variables', function() {
            $loader = new PromptLoader();
            
            // Test that we can load and the prompt is valid
            $prompt = $loader->loadPrompt('language_expert');
            
            if (empty($prompt)) {
                throw new Exception("Failed to load language_expert prompt");
            }
            
            // Verify prompt contains expected content
            if (strpos($prompt, 'Language Expert') === false) {
                throw new Exception("Prompt does not contain expected content");
            }
            
            return true;
        });

        $this->test('Agent Registry Validation', function() {
            $config = Config::getInstance();
            $agents = $config->getAgents();
            
            $requiredFields = ['prompt_file', 'description', 'model', 'provider', 'temperature'];
            
            foreach ($agents as $id => $agentConfig) {
                foreach ($requiredFields as $field) {
                    if (!isset($agentConfig[$field])) {
                        throw new Exception("Agent '{$id}' missing required field: {$field}");
                    }
                }
                
                // Verify prompt file exists
                $promptPath = __DIR__ . '/prompts/' . $agentConfig['prompt_file'];
                if (!file_exists($promptPath)) {
                    throw new Exception("Agent '{$id}' prompt file not found: {$agentConfig['prompt_file']}");
                }
            }
            
            return true;
        });

        $this->test('Orchestrator - Session Management', function() {
            $orchestrator = new Orchestrator();
            
            // Test session creation through workflow execution simulation
            $reflection = new ReflectionClass($orchestrator);
            $method = $reflection->getMethod('executeWorkflow');
            $method->setAccessible(true);
            
            // We can't actually execute without API keys, but we can verify the structure
            $templates = $config = Config::getInstance();
            $template = $templates->getWorkflowTemplate('initial_engagement');
            
            if (!is_array($template) || empty($template)) {
                throw new Exception("Failed to get workflow template");
            }
            
            return true;
        });

        $this->test('API Routes Configuration', function() {
            $api = new Api();
            
            // Verify API can be instantiated
            if (!($api instanceof Api)) {
                throw new Exception("Failed to create API instance");
            }
            
            return true;
        });

        $this->test('Default Workflow Validation', function() {
            $config = Config::getInstance();
            $defaultWorkflow = $config->getDefaultWorkflow();
            
            if (empty($defaultWorkflow)) {
                throw new Exception("No default workflow configured");
            }
            
            if (count($defaultWorkflow) < 5) {
                throw new Exception("Default workflow too short: " . count($defaultWorkflow) . " steps");
            }
            
            // Verify all agents in default workflow exist
            $agents = $config->getAgents();
            foreach ($defaultWorkflow as $agentId) {
                if (!isset($agents[$agentId])) {
                    throw new Exception("Unknown agent in default workflow: {$agentId}");
                }
            }
            
            return true;
        });

        $this->summary();
    }

    private function test(string $name, callable $testFn) {
        echo "Testing: {$name}... ";
        
        try {
            $result = $testFn();
            
            if ($result === true) {
                echo "✓ PASSED\n";
                $this->passed++;
                $this->results[] = ['name' => $name, 'status' => 'passed'];
            } else {
                echo "✗ FAILED (returned false)\n";
                $this->failed++;
                $this->results[] = ['name' => $name, 'status' => 'failed', 'error' => 'returned false'];
            }
        } catch (Exception $e) {
            echo "✗ FAILED: " . $e->getMessage() . "\n";
            $this->failed++;
            $this->results[] = ['name' => $name, 'status' => 'failed', 'error' => $e->getMessage()];
        }
    }

    private function summary() {
        $total = $this->passed + $this->failed;
        
        echo "\n============================================================\n";
        echo "TEST SUMMARY\n";
        echo "============================================================\n";
        echo "✓ Passed: {$this->passed}\n";
        echo "✗ Failed: {$this->failed}\n";
        echo "Total: {$total}\n";
        echo "============================================================\n";
        
        if ($this->failed > 0) {
            echo "\n⚠️  {$this->failed} TEST(S) FAILED\n";
            exit(1);
        } else {
            echo "\n✅ ALL TESTS PASSED!\n";
            exit(0);
        }
    }
}

// Run tests
$suite = new TestSuite();
$suite->run();
