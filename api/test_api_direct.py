#!/usr/bin/env python3
"""
Test API Endpoints Without Server
Tests the AgenticAPI class directly to ensure all methods work correctly
"""

import sys
from pathlib import Path

# Add workspace to path
sys.path.insert(0, str(Path(__file__).parent))

from agentic_workflow.api import AgenticAPI


def test_list_agents():
    """Test listing all available agents"""
    print("\n=== Test 1: List Agents ===")
    api = AgenticAPI()
    agents = api.list_agents()
    
    assert len(agents) > 0, "No agents found"
    print(f"✓ Found {len(agents)} agents")
    print(f"  Sample: {agents[:5]}")
    return True


def test_get_agent_info():
    """Test getting detailed agent information"""
    print("\n=== Test 2: Get Agent Info ===")
    api = AgenticAPI()
    
    # Test with first agent
    agents = api.list_agents()
    agent_name = agents[0]
    
    info = api.get_agent_info(agent_name)
    
    assert "name" in info, "Missing name field"
    assert "model" in info, "Missing model field"
    assert "provider" in info, "Missing provider field"
    assert "prompt_file" in info, "Missing prompt_file field"
    
    print(f"✓ Agent info retrieved for '{agent_name}'")
    print(f"  Model: {info['model']}")
    print(f"  Provider: {info['provider']}")
    print(f"  Prompt: {info['prompt_file']}")
    return True


def test_validate_all_agents():
    """Test that all agents are properly configured"""
    print("\n=== Test 3: Validate All Agents ===")
    api = AgenticAPI()
    agents = api.list_agents()
    
    valid_count = 0
    for agent_name in agents:
        try:
            agent = api.registry.get_agent(agent_name)
            assert agent.config.prompt_file is not None
            assert agent.config.model is not None
            valid_count += 1
        except Exception as e:
            print(f"  ✗ {agent_name}: {e}")
    
    print(f"✓ {valid_count}/{len(agents)} agents validated successfully")
    return valid_count == len(agents)


def test_list_workflows():
    """Test listing workflow templates"""
    print("\n=== Test 4: List Workflows ===")
    api = AgenticAPI()
    workflows = api.list_workflows()
    
    assert isinstance(workflows, dict), "Workflows should be a dict"
    assert len(workflows) > 0, "No workflows found"
    
    print(f"✓ Found {len(workflows)} workflow templates")
    for name, steps in workflows.items():
        print(f"  - {name}: {len(steps)} steps")
    
    return True


def test_get_default_workflow():
    """Test getting the default workflow"""
    print("\n=== Test 5: Get Default Workflow ===")
    api = AgenticAPI()
    workflow = api.get_default_workflow()
    
    assert isinstance(workflow, list), "Default workflow should be a list"
    assert len(workflow) > 0, "Default workflow is empty"
    
    print(f"✓ Default workflow has {len(workflow)} steps")
    print(f"  Steps: {workflow}")
    return True


def test_get_workflow_template():
    """Test getting a specific workflow template"""
    print("\n=== Test 6: Get Workflow Template ===")
    api = AgenticAPI()
    
    templates = list(api.list_workflows().keys())
    template_name = templates[0]
    
    workflow = api.registry.get_workflow(template_name)
    
    assert isinstance(workflow, list), "Workflow should be a list"
    assert len(workflow) > 0, "Workflow is empty"
    
    print(f"✓ Template '{template_name}' has {len(workflow)} steps")
    for i, step in enumerate(workflow, 1):
        print(f"  {i}. {step}")
    
    return True


def test_create_custom_workflow():
    """Test creating a custom workflow"""
    print("\n=== Test 7: Create Custom Workflow ===")
    api = AgenticAPI()
    
    # Create a simple 2-step workflow
    agents = api.list_agents()[:2]
    workflow_def = api.create_custom_workflow(
        name="test_workflow",
        agents=agents,
        description="Test workflow for validation"
    )
    
    assert workflow_def["name"] == "test_workflow"
    assert workflow_def["step_count"] == len(agents)
    assert workflow_def["agents"] == agents
    
    print(f"✓ Custom workflow created: {workflow_def['name']}")
    print(f"  Steps: {workflow_def['step_count']}")
    return True


def test_validate_workflow():
    """Test workflow validation"""
    print("\n=== Test 8: Validate Workflow ===")
    api = AgenticAPI()
    
    # Test valid workflow
    agents = api.list_agents()[:3]
    result = api.validate_workflow(agents)
    
    assert result["valid"] is True, f"Valid workflow marked as invalid: {result['errors']}"
    
    # Test invalid workflow
    result = api.validate_workflow(["nonexistent_agent"])
    assert result["valid"] is False, "Invalid workflow marked as valid"
    assert len(result["errors"]) > 0, "No errors for invalid workflow"
    
    print(f"✓ Workflow validation working correctly")
    print(f"  Valid workflow: ✓")
    print(f"  Invalid workflow detected: ✓")
    return True


def test_state_management():
    """Test session state management"""
    print("\n=== Test 9: State Management ===")
    api = AgenticAPI()
    session_id = "test_session_123"
    
    # Reset session
    api.reset_session(session_id)
    
    # Get initial state
    state = api.get_state(session_id)
    assert isinstance(state, dict), "State should be a dict"
    
    print(f"✓ Session state management working")
    print(f"  Session ID: {session_id}")
    print(f"  State keys: {list(state.keys())[:5]}...")
    
    return True


def test_execution_history():
    """Test execution history retrieval"""
    print("\n=== Test 10: Execution History ===")
    api = AgenticAPI()
    session_id = "test_session_history"
    
    # Get history (should be empty initially)
    history = api.get_execution_history(session_id)
    assert isinstance(history, list), "History should be a list"
    
    print(f"✓ Execution history accessible")
    print(f"  Current history length: {len(history)}")
    
    return True


def run_all_tests():
    """Run all API tests"""
    print("=" * 60)
    print("ARDI-AGENTS API TEST SUITE")
    print("=" * 60)
    
    tests = [
        ("List Agents", test_list_agents),
        ("Get Agent Info", test_get_agent_info),
        ("Validate All Agents", test_validate_all_agents),
        ("List Workflows", test_list_workflows),
        ("Get Default Workflow", test_get_default_workflow),
        ("Get Workflow Template", test_get_workflow_template),
        ("Create Custom Workflow", test_create_custom_workflow),
        ("Validate Workflow", test_validate_workflow),
        ("State Management", test_state_management),
        ("Execution History", test_execution_history),
    ]
    
    results = {}
    for test_name, test_func in tests:
        try:
            success = test_func()
            results[test_name] = success
        except Exception as e:
            print(f"\n✗ Test '{test_name}' failed with exception: {e}")
            import traceback
            traceback.print_exc()
            results[test_name] = False
    
    # Summary
    print("\n" + "=" * 60)
    print("TEST SUMMARY")
    print("=" * 60)
    
    passed = sum(1 for v in results.values() if v)
    total = len(results)
    
    for name, success in results.items():
        status = "✓ PASS" if success else "✗ FAIL"
        print(f"  {status}: {name}")
    
    print(f"\nTotal: {passed}/{total} tests passed")
    
    if passed == total:
        print("\n🎉 ALL API TESTS PASSED!")
        return 0
    else:
        print(f"\n⚠️  {total - passed} test(s) failed")
        return 1


if __name__ == "__main__":
    sys.exit(run_all_tests())
