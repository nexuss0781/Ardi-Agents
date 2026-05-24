"""
Test Script for Agentic Workflow

This script tests individual agents and workflows to ensure they work correctly.
Run this before attempting full orchestration.
"""

import os
import sys
from pathlib import Path

# Add the workspace to path
sys.path.insert(0, str(Path(__file__).parent))

from agentic_workflow.core import (
    AgentRegistry, 
    Orchestrator, 
    PromptLoader,
    run_agent,
    run_workflow,
    WorkflowState
)


def test_prompt_loader():
    """Test that prompt loader can find and load all prompts."""
    print("\n" + "="*60)
    print("TEST 1: Prompt Loader")
    print("="*60)
    
    loader = PromptLoader()
    available = loader.list_available()
    
    print(f"\nAvailable prompts ({len(available)}):")
    for prompt in sorted(available):
        print(f"  - {prompt}")
    
    # Test loading each prompt
    print("\nLoading each prompt...")
    for prompt in available:
        try:
            content = loader.load(prompt)
            print(f"  ✓ {prompt} ({len(content)} chars)")
        except Exception as e:
            print(f"  ✗ {prompt}: {e}")
    
    return True


def test_agent_registry():
    """Test that agent registry loads all agents correctly."""
    print("\n" + "="*60)
    print("TEST 2: Agent Registry")
    print("="*60)
    
    config_path = Path(__file__).parent / "agents_config.yaml"
    print(f"\nConfig path: {config_path}")
    print(f"Config exists: {config_path.exists()}")
    
    registry = AgentRegistry(str(config_path))
    
    print(f"\nLoaded agents ({len(registry.agents)}):")
    for name in sorted(registry.list_agents()):
        agent = registry.get_agent(name)
        print(f"  - {name}")
        print(f"      Model: {agent.config.model}")
        print(f"      Provider: {agent.config.provider}")
        print(f"      Prompt: {agent.config.prompt_file}")
    
    # Verify all prompts exist
    print("\nVerifying all agent prompts exist...")
    loader = PromptLoader()
    available_prompts = loader.list_available()
    
    for name, agent in registry.agents.items():
        if agent.config.prompt_file in available_prompts:
            print(f"  ✓ {name}: {agent.config.prompt_file}")
        else:
            print(f"  ✗ {name}: {agent.config.prompt_file} NOT FOUND")
    
    return True


def test_single_agent(agent_name: str = "language_expert"):
    """Test a single agent execution."""
    print("\n" + "="*60)
    print(f"TEST 3: Single Agent Execution - {agent_name}")
    print("="*60)
    
    config_path = Path(__file__).parent / "agents_config.yaml"
    
    test_request = "Build a simple todo list web application with React frontend and Python backend"
    
    print(f"\nTest request: {test_request}")
    print(f"\nExecuting agent: {agent_name}...")
    
    try:
        result = run_agent(agent_name, test_request, str(config_path))
        
        if "error" in result:
            print(f"\n✗ Agent failed: {result['error']}")
            return False
        else:
            print(f"\n✓ Agent succeeded!")
            print(f"\nOutput preview (first 500 chars):")
            output = result.get('output', '')
            if isinstance(output, dict):
                output = str(output)
            print(output[:500])
            if len(output) > 500:
                print("...")
            return True
            
    except Exception as e:
        print(f"\n✗ Exception: {e}")
        import traceback
        traceback.print_exc()
        return False


def test_individual_agents():
    """Test each agent individually without calling LLM (just validation)."""
    print("\n" + "="*60)
    print("TEST 4: Individual Agent Validation")
    print("="*60)
    
    config_path = Path(__file__).parent / "agents_config.yaml"
    registry = AgentRegistry(str(config_path))
    
    print("\nValidating each agent configuration:")
    
    results = {}
    for name in sorted(registry.list_agents()):
        agent = registry.get_agent(name)
        
        # Check prompt file exists
        prompt_exists = Path("/workspace/prompts") / agent.config.prompt_file
        prompt_ok = prompt_exists.exists()
        
        # Check model format
        model_ok = bool(agent.config.model and len(agent.config.model) > 0)
        
        status = "✓" if (prompt_ok and model_ok) else "✗"
        results[name] = {"prompt_ok": prompt_ok, "model_ok": model_ok}
        
        print(f"  {status} {name}")
        if not prompt_ok:
            print(f"      - Prompt file missing: {agent.config.prompt_file}")
        if not model_ok:
            print(f"      - Model not configured")
    
    return all(r["prompt_ok"] and r["model_ok"] for r in results.values())


def test_workflow_template(template_name: str = "initial_engagement"):
    """Test a workflow template (without LLM calls)."""
    print("\n" + "="*60)
    print(f"TEST 5: Workflow Template - {template_name}")
    print("="*60)
    
    config_path = Path(__file__).parent / "agents_config.yaml"
    registry = AgentRegistry(str(config_path))
    
    try:
        workflow = registry.get_workflow(template_name)
        print(f"\nWorkflow '{template_name}' has {len(workflow)} steps:")
        for i, agent_name in enumerate(workflow, 1):
            agent_exists = agent_name in registry.agents
            status = "✓" if agent_exists else "✗"
            print(f"  {i}. {status} {agent_name}")
        
        return True
    except Exception as e:
        print(f"\n✗ Failed to load workflow: {e}")
        return False


def test_orchestrator_state():
    """Test orchestrator state management."""
    print("\n" + "="*60)
    print("TEST 6: Orchestrator State Management")
    print("="*60)
    
    config_path = Path(__file__).parent / "agents_config.yaml"
    registry = AgentRegistry(str(config_path))
    orchestrator = Orchestrator(registry)
    
    # Test initial state
    print("\nInitial state:")
    print(f"  initial_request: '{orchestrator.state.initial_request}'")
    print(f"  history_log: {orchestrator.state.history_log}")
    
    # Test reset
    test_request = "Test project"
    orchestrator.reset(test_request)
    print(f"\nAfter reset with '{test_request}':")
    print(f"  initial_request: '{orchestrator.state.initial_request}'")
    
    # Test state updates
    orchestrator.state.refined_query = "Refined test query"
    orchestrator.state.history_log.append("Test action")
    
    state_dict = orchestrator.state.to_dict()
    print(f"\nState as dict keys: {list(state_dict.keys())}")
    
    return True


def test_all_workflows():
    """List all available workflows."""
    print("\n" + "="*60)
    print("TEST 7: All Available Workflows")
    print("="*60)
    
    config_path = Path(__file__).parent / "agents_config.yaml"
    registry = AgentRegistry(str(config_path))
    
    print("\nWorkflow templates:")
    for name, workflow in registry.workflow_templates.items():
        print(f"\n  {name}:")
        for i, agent in enumerate(workflow, 1):
            print(f"    {i}. {agent}")
    
    print(f"\nDefault workflow ({len(registry.default_workflow)} steps):")
    for i, agent in enumerate(registry.default_workflow, 1):
        print(f"  {i}. {agent}")
    
    return True


def main():
    """Run all tests."""
    print("\n" + "#"*60)
    print("# AGENTIC WORKFLOW TEST SUITE")
    print("#"*60)
    
    tests = [
        ("Prompt Loader", test_prompt_loader),
        ("Agent Registry", test_agent_registry),
        ("Individual Agent Validation", test_individual_agents),
        ("Orchestrator State", test_orchestrator_state),
        ("All Workflows", test_all_workflows),
        ("Workflow Template: initial_engagement", lambda: test_workflow_template("initial_engagement")),
        ("Workflow Template: planning_phase", lambda: test_workflow_template("planning_phase")),
        ("Workflow Template: qa_phase", lambda: test_workflow_template("qa_phase")),
    ]
    
    results = {}
    
    for test_name, test_func in tests:
        try:
            success = test_func()
            results[test_name] = success
        except Exception as e:
            print(f"\n✗ Test '{test_name}' raised exception: {e}")
            import traceback
            traceback.print_exc()
            results[test_name] = False
    
    # Summary
    print("\n" + "="*60)
    print("TEST SUMMARY")
    print("="*60)
    
    passed = sum(1 for v in results.values() if v)
    total = len(results)
    
    for name, success in results.items():
        status = "✓ PASS" if success else "✗ FAIL"
        print(f"  {status}: {name}")
    
    print(f"\nTotal: {passed}/{total} tests passed")
    
    if passed == total:
        print("\n✓ All tests passed! The workflow system is ready.")
        return 0
    else:
        print(f"\n✗ {total - passed} test(s) failed. Please fix issues before proceeding.")
        return 1


if __name__ == "__main__":
    # Set environment for litellm if needed
    # You may need to set API keys here or in .env
    sys.exit(main())
