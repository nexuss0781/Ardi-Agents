"""
Comprehensive API Test Suite
Tests all API endpoints to ensure they work correctly
"""

import requests
import json
import sys
import time
from typing import Dict, Any

BASE_URL = "http://localhost:8000"


def test_health():
    """Test health endpoint"""
    print("\n=== Testing Health Endpoint ===")
    response = requests.get(f"{BASE_URL}/health")
    assert response.status_code == 200, f"Health check failed: {response.status_code}"
    data = response.json()
    print(f"✓ Status: {data['status']}")
    print(f"✓ Components: {json.dumps(data['components'], indent=2)}")
    return True


def test_root():
    """Test root endpoint"""
    print("\n=== Testing Root Endpoint ===")
    response = requests.get(f"{BASE_URL}/")
    assert response.status_code == 200, f"Root endpoint failed: {response.status_code}"
    data = response.json()
    print(f"✓ API Name: {data['name']}")
    print(f"✓ Version: {data['version']}")
    print(f"✓ Available Endpoints: {list(data['endpoints'].keys())}")
    return True


def test_list_agents():
    """Test list agents endpoint"""
    print("\n=== Testing List Agents Endpoint ===")
    response = requests.get(f"{BASE_URL}/agents")
    assert response.status_code == 200, f"List agents failed: {response.status_code}"
    agents = response.json()
    print(f"✓ Total Agents: {len(agents)}")
    for agent in agents[:3]:
        print(f"  - {agent['name']} ({agent['model']})")
    if len(agents) > 3:
        print(f"  ... and {len(agents) - 3} more")
    return True


def test_get_agent(agent_name="analyst"):
    """Test get specific agent endpoint"""
    print(f"\n=== Testing Get Agent: {agent_name} ===")
    response = requests.get(f"{BASE_URL}/agents/{agent_name}")
    assert response.status_code == 200, f"Get agent failed: {response.status_code}"
    agent = response.json()
    print(f"✓ Name: {agent['name']}")
    print(f"✓ Description: {agent['description'][:100]}...")
    print(f"✓ Model: {agent['model']}")
    print(f"✓ Temperature: {agent['temperature']}")
    return True


def test_list_workflows():
    """Test list workflows endpoint"""
    print("\n=== Testing List Workflows Endpoint ===")
    response = requests.get(f"{BASE_URL}/workflows")
    assert response.status_code == 200, f"List workflows failed: {response.status_code}"
    data = response.json()
    print(f"✓ Templates: {data['templates']}")
    print(f"✓ Default Workflow: {data['default_workflow']}")
    print(f"✓ Total Templates: {data['total_templates']}")
    return True


def test_get_workflow_template(template_name="initial_engagement"):
    """Test get workflow template endpoint"""
    print(f"\n=== Testing Get Workflow Template: {template_name} ===")
    response = requests.get(f"{BASE_URL}/workflows/{template_name}")
    assert response.status_code == 200, f"Get workflow failed: {response.status_code}"
    data = response.json()
    print(f"✓ Template: {data['template_name']}")
    print(f"✓ Steps: {data['steps']}")
    print(f"✓ Total Steps: {data['total_steps']}")
    print(f"✓ Agents Involved: {data['agents_involved']}")
    return True


def test_execute_agent(agent_name="analyst"):
    """Test execute single agent endpoint"""
    print(f"\n=== Testing Execute Agent: {agent_name} ===")
    
    payload = {
        "agent_name": agent_name,
        "input_text": "Build a simple todo application with React",
        "session_id": None
    }
    
    response = requests.post(f"{BASE_URL}/execute/agent", json=payload)
    assert response.status_code == 200, f"Execute agent failed: {response.status_code}\n{response.text}"
    result = response.json()
    print(f"✓ Success: {result['success']}")
    print(f"✓ Agent: {result['agent_name']}")
    print(f"✓ Model Used: {result['model_used']}")
    print(f"✓ Execution Time: {result['execution_time']:.2f}s")
    print(f"✓ Session ID: {result['session_id']}")
    print(f"✓ Output Preview: {result['output'][:200]}...")
    return result.get('session_id')


def test_execute_workflow_template(template_name="initial_engagement"):
    """Test execute workflow template endpoint"""
    print(f"\n=== Testing Execute Workflow Template: {template_name} ===")
    
    payload = {
        "template_name": template_name,
        "initial_request": "Create a weather dashboard application",
        "session_id": None,
        "max_retries": 2
    }
    
    response = requests.post(f"{BASE_URL}/execute/workflow", json=payload)
    assert response.status_code == 200, f"Execute workflow failed: {response.status_code}\n{response.text}"
    result = response.json()
    print(f"✓ Success: {result['success']}")
    print(f"✓ Total Steps: {result['total_steps']}")
    print(f"✓ Successful Steps: {result['successful_steps']}")
    print(f"✓ Failed Steps: {result['failed_steps']}")
    print(f"✓ Execution Time: {result['execution_time']:.2f}s")
    print(f"✓ Session ID: {result['session_id']}")
    
    if result['steps_executed']:
        print(f"✓ Steps Executed:")
        for step in result['steps_executed'][:2]:
            print(f"  - {step['agent']}: {'✓' if step['success'] else '✗'}")
        if len(result['steps_executed']) > 2:
            print(f"  ... and {len(result['steps_executed']) - 2} more")
    
    return result.get('session_id')


def test_execute_custom_workflow():
    """Test execute custom workflow endpoint"""
    print(f"\n=== Testing Execute Custom Workflow ===")
    
    payload = {
        "custom_steps": ["analyst", "innovator"],
        "initial_request": "Design a REST API for user management",
        "session_id": None,
        "max_retries": 2
    }
    
    response = requests.post(f"{BASE_URL}/execute/workflow", json=payload)
    assert response.status_code == 200, f"Execute custom workflow failed: {response.status_code}\n{response.text}"
    result = response.json()
    print(f"✓ Success: {result['success']}")
    print(f"✓ Total Steps: {result['total_steps']}")
    print(f"✓ Successful Steps: {result['successful_steps']}")
    print(f"✓ Execution Time: {result['execution_time']:.2f}s")
    return result.get('session_id')


def test_list_sessions():
    """Test list sessions endpoint"""
    print("\n=== Testing List Sessions Endpoint ===")
    response = requests.get(f"{BASE_URL}/sessions")
    assert response.status_code == 200, f"List sessions failed: {response.status_code}"
    data = response.json()
    print(f"✓ Total Sessions: {data['total']}")
    if data['sessions']:
        for session in data['sessions'][:3]:
            print(f"  - {session['session_id'][:8]}... ({session['status']})")
    return True


def test_get_session(session_id):
    """Test get specific session endpoint"""
    if not session_id:
        print("\n=== Skipping Get Session (no session ID) ===")
        return True
    
    print(f"\n=== Testing Get Session: {session_id[:8]}... ===")
    response = requests.get(f"{BASE_URL}/sessions/{session_id}")
    assert response.status_code == 200, f"Get session failed: {response.status_code}"
    session = response.json()
    print(f"✓ Session ID: {session['session_id'][:8]}...")
    print(f"✓ Status: {session['status']}")
    print(f"✓ Current Step: {session['current_step']}")
    print(f"✓ Total Steps: {session['total_steps']}")
    return True


def test_delete_session(session_id):
    """Test delete session endpoint"""
    if not session_id:
        print("\n=== Skipping Delete Session (no session ID) ===")
        return True
    
    print(f"\n=== Testing Delete Session: {session_id[:8]}... ===")
    response = requests.delete(f"{BASE_URL}/sessions/{session_id}")
    assert response.status_code == 200, f"Delete session failed: {response.status_code}"
    result = response.json()
    print(f"✓ Success: {result['success']}")
    print(f"✓ Message: {result['message']}")
    return True


def test_plan_dynamic_workflow():
    """Test dynamic workflow planning endpoint"""
    print(f"\n=== Testing Dynamic Workflow Planning ===")
    
    payload = {
        "initial_request": "Build a full-stack e-commerce platform with payment integration",
        "context": {},
        "max_agents": 5
    }
    
    response = requests.post(f"{BASE_URL}/plan/dynamic", json=payload)
    assert response.status_code == 200, f"Plan dynamic workflow failed: {response.status_code}\n{response.text}"
    result = response.json()
    print(f"✓ Plan: {result['plan']}")
    print(f"✓ Reasoning: {result['reasoning']}")
    print(f"✓ Estimated Steps: {result['estimated_steps']}")
    return True


def test_clear_sessions():
    """Test clear all sessions endpoint"""
    print("\n=== Testing Clear Sessions Endpoint ===")
    response = requests.post(f"{BASE_URL}/sessions/clear")
    assert response.status_code == 200, f"Clear sessions failed: {response.status_code}"
    result = response.json()
    print(f"✓ Success: {result['success']}")
    print(f"✓ Message: {result['message']}")
    return True


def run_all_tests():
    """Run all API tests"""
    print("=" * 60)
    print("ARDI-AGENTS API COMPREHENSIVE TEST SUITE")
    print("=" * 60)
    
    tests_passed = 0
    tests_failed = 0
    session_ids = []
    
    try:
        # Health & Info Tests
        if test_root(): tests_passed += 1
        else: tests_failed += 1
        
        if test_health(): tests_passed += 1
        else: tests_failed += 1
        
        # Agent Tests
        if test_list_agents(): tests_passed += 1
        else: tests_failed += 1
        
        if test_get_agent(): tests_passed += 1
        else: tests_failed += 1
        
        # Workflow Tests
        if test_list_workflows(): tests_passed += 1
        else: tests_failed += 1
        
        if test_get_workflow_template(): tests_passed += 1
        else: tests_failed += 1
        
        # Execution Tests
        if test_execute_agent(): tests_passed += 1
        else: tests_failed += 1
        
        session_id_1 = test_execute_workflow_template()
        if session_id_1: 
            tests_passed += 1
            session_ids.append(session_id_1)
        else: 
            tests_failed += 1
        
        session_id_2 = test_execute_custom_workflow()
        if session_id_2: 
            tests_passed += 1
            session_ids.append(session_id_2)
        else: 
            tests_failed += 1
        
        # Session Tests
        if test_list_sessions(): tests_passed += 1
        else: tests_failed += 1
        
        if session_ids:
            if test_get_session(session_ids[0]): tests_passed += 1
            else: tests_failed += 1
            
            if test_delete_session(session_ids[0]): tests_passed += 1
            else: tests_failed += 1
        
        # Planning Tests
        if test_plan_dynamic_workflow(): tests_passed += 1
        else: tests_failed += 1
        
        # Cleanup
        if test_clear_sessions(): tests_passed += 1
        else: tests_failed += 1
        
    except AssertionError as e:
        print(f"\n❌ TEST FAILED: {str(e)}")
        tests_failed += 1
    except requests.exceptions.ConnectionError as e:
        print(f"\n❌ CONNECTION ERROR: Could not connect to API server")
        print(f"   Make sure the server is running: python api/main.py")
        print(f"   Or: uvicorn api.main:app --reload")
        sys.exit(1)
    except Exception as e:
        print(f"\n❌ UNEXPECTED ERROR: {str(e)}")
        tests_failed += 1
    
    # Summary
    print("\n" + "=" * 60)
    print("TEST SUMMARY")
    print("=" * 60)
    print(f"✓ Passed: {tests_passed}")
    print(f"✗ Failed: {tests_failed}")
    print(f"Total: {tests_passed + tests_failed}")
    print("=" * 60)
    
    if tests_failed == 0:
        print("\n🎉 ALL TESTS PASSED!")
        return True
    else:
        print(f"\n⚠️  {tests_failed} TEST(S) FAILED")
        return False


if __name__ == "__main__":
    success = run_all_tests()
    sys.exit(0 if success else 1)
