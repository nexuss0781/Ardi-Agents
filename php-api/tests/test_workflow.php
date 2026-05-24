<?php

require_once __DIR__ . '/src/Config.php';
require_once __DIR__ . '/src/PromptLoader.php';
require_once __DIR__ . '/src/Agent.php';
require_once __DIR__ . '/src/Orchestrator.php';
require_once __DIR__ . '/src/Api.php';

use ArdiAgents\Config;
use ArdiAgents\PromptLoader;
use ArdiAgents\Agent;
use ArdiAgents\Orchestrator;
use ArdiAgents\Api;

class WorkflowTester
{
    private Api $api;
    private int $passed = 0;
    private int $failed = 0;

    public function __construct()
    {
        $this->api = new Api();
    }

    public function runAllTests(): void
    {
        echo "============================================================\n";
        echo "ARDI AGENTS PHP - WORKFLOW TEST SUITE\n";
        echo "============================================================\n\n";

        $this->testConfigLoading();
        $this->testPromptLoader();
        $this->testAgentRegistry();
        $this->testListAgents();
        $this->testListWorkflows();
        $this->testExecuteAgent();
        $this->testWorkflowExecution();

        $this->printSummary();
    }

    private function testConfigLoading(): void
    {
        echo "=== Testing Config Loading ===\n";
        try {
            $config = Config::getInstance();
            $agents = $config->getAgents();
            
            if (count($agents) > 0) {
                $this->pass("Config loaded with " . count($agents) . " agents");
            } else {
                $this->fail("No agents found in config");
            }
        } catch (\Exception $e) {
            $this->fail("Config loading failed: " . $e->getMessage());
        }
        echo "\n";
    }

    private function testPromptLoader(): void
    {
        echo "=== Testing Prompt Loader ===\n";
        try {
            $loader = new PromptLoader();
            $prompt = $loader->loadPrompt('analyst.txt');
            
            if (!empty($prompt)) {
                $this->pass("Prompt loaded successfully");
            } else {
                $this->fail("Empty prompt loaded");
            }
        } catch (\Exception $e) {
            $this->fail("Prompt loading failed: " . $e->getMessage());
        }
        echo "\n";
    }

    private function testAgentRegistry(): void
    {
        echo "=== Testing Agent Registry ===\n";
        try {
            $config = Config::getInstance();
            $agents = $config->getAgents();
            
            $expectedAgents = ['analyst', 'architect', 'backend_dev', 'frontend_dev', 'qa_engineer'];
            $found = 0;
            
            foreach ($expectedAgents as $agentId) {
                if ($config->getAgent($agentId)) {
                    $found++;
                }
            }
            
            if ($found === count($expectedAgents)) {
                $this->pass("All expected agents found ($found/" . count($expectedAgents) . ")");
            } else {
                $this->fail("Missing agents ($found/" . count($expectedAgents) . ")");
            }
        } catch (\Exception $e) {
            $this->fail("Agent registry test failed: " . $e->getMessage());
        }
        echo "\n";
    }

    private function testListAgents(): void
    {
        echo "=== Testing List Agents API ===\n";
        try {
            $agents = $this->api->listAgents();
            
            if (count($agents) > 0) {
                $this->pass("Listed " . count($agents) . " agents via API");
            } else {
                $this->fail("No agents returned from API");
            }
        } catch (\Exception $e) {
            $this->fail("List agents API failed: " . $e->getMessage());
        }
        echo "\n";
    }

    private function testListWorkflows(): void
    {
        echo "=== Testing List Workflows API ===\n";
        try {
            $workflows = $this->api->listWorkflows();
            
            if (count($workflows) > 0) {
                $this->pass("Listed " . count($workflows) . " workflow templates");
                foreach ($workflows as $workflow) {
                    echo "   - {$workflow['name']}: {$workflow['step_count']} steps\n";
                }
            } else {
                $this->fail("No workflows returned from API");
            }
        } catch (\Exception $e) {
            $this->fail("List workflows API failed: " . $e->getMessage());
        }
        echo "\n";
    }

    private function testExecuteAgent(): void
    {
        echo "=== Testing Execute Agent: analyst ===\n";
        try {
            $result = $this->api->executeAgent('analyst', [
                'user_request' => 'Build a simple todo app'
            ]);
            
            if ($result['success']) {
                $this->pass("Agent executed successfully");
                echo "   Response preview: " . substr($result['response'], 0, 100) . "...\n";
            } else {
                $this->fail("Agent execution failed: " . ($result['error'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            $this->fail("Execute agent failed: " . $e->getMessage());
        }
        echo "\n";
    }

    private function testWorkflowExecution(): void
    {
        echo "=== Testing Workflow Execution: initial_engagement ===\n";
        try {
            $result = $this->api->runWorkflow(
                'initial_engagement',
                'Build a REST API for a blog platform'
            );
            
            if (isset($result['status'])) {
                $this->pass("Workflow executed with status: {$result['status']}");
                echo "   Steps completed: " . count($result['steps'] ?? []) . "\n";
                echo "   Session ID: {$result['session_id']}\n";
            } else {
                $this->fail("Workflow execution failed: " . ($result['error'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            $this->fail("Workflow execution failed: " . $e->getMessage());
        }
        echo "\n";
    }

    private function pass(string $message): void
    {
        echo "✓ PASS: {$message}\n";
        $this->passed++;
    }

    private function fail(string $message): void
    {
        echo "✗ FAIL: {$message}\n";
        $this->failed++;
    }

    private function printSummary(): void
    {
        echo "============================================================\n";
        echo "TEST SUMMARY\n";
        echo "============================================================\n";
        echo "✓ Passed: {$this->passed}\n";
        echo "✗ Failed: {$this->failed}\n";
        echo "Total: " . ($this->passed + $this->failed) . "\n";
        echo "============================================================\n";
        
        if ($this->failed > 0) {
            echo "⚠️  {$this->failed} TEST(S) FAILED\n";
            exit(1);
        } else {
            echo "✅ ALL TESTS PASSED\n";
            exit(0);
        }
    }
}

// Run tests
$tester = new WorkflowTester();
$tester->runAllTests();
