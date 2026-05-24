"""
Agentic Workflow API

Clean, decoupled API for the agentic workflow system.
This module provides REST-like endpoints without framework dependencies.
"""

import json
from typing import Dict, Any, List, Optional
from pathlib import Path

from .core import AgentRegistry, Orchestrator, WorkflowState


class AgenticAPI:
    """
    Clean API for interacting with the agentic workflow system.
    
    This class provides methods that can be called from:
    - Direct Python code
    - FastAPI/Flask endpoints
    - CLI tools
    - Other services
    """
    
    def __init__(self, config_path: str = None):
        """
        Initialize the API.
        
        Args:
            config_path: Optional path to agents_config.yaml
        """
        if config_path is None:
            config_path = Path(__file__).parent / "agents_config.yaml"
        
        self.config_path = Path(config_path)
        self.registry = AgentRegistry(str(self.config_path))
        self._orchestrators: Dict[str, Orchestrator] = {}
    
    def _get_orchestrator(self, session_id: str) -> Orchestrator:
        """Get or create an orchestrator for a session."""
        if session_id not in self._orchestrators:
            self._orchestrators[session_id] = Orchestrator(self.registry)
        return self._orchestrators[session_id]
    
    # ========== Agent Operations ==========
    
    def list_agents(self) -> List[str]:
        """List all available agents."""
        return self.registry.list_agents()
    
    def get_agent_info(self, agent_name: str) -> Dict[str, Any]:
        """Get detailed information about an agent."""
        agent = self.registry.get_agent(agent_name)
        return {
            "name": agent.config.name,
            "description": agent.config.description,
            "model": agent.config.model,
            "provider": agent.config.provider,
            "temperature": agent.config.temperature,
            "response_format": agent.config.response_format,
            "prompt_file": agent.config.prompt_file
        }
    
    def run_single_agent(
        self,
        agent_name: str,
        initial_request: str,
        session_id: str = "default"
    ) -> Dict[str, Any]:
        """
        Run a single agent.
        
        Args:
            agent_name: Name of the agent to run
            initial_request: User's initial request
            session_id: Optional session identifier
            
        Returns:
            Agent execution result
        """
        orchestrator = self._get_orchestrator(session_id)
        orchestrator.reset(initial_request)
        
        result = orchestrator.execute_agent(agent_name, initial_request)
        
        return {
            "success": "error" not in result,
            "agent": agent_name,
            "output": result.get("output"),
            "error": result.get("error"),
            "state": orchestrator.state.to_dict()
        }
    
    # ========== Workflow Operations ==========
    
    def list_workflows(self) -> Dict[str, List[str]]:
        """List all available workflow templates."""
        return self.registry.workflow_templates
    
    def get_default_workflow(self) -> List[str]:
        """Get the default workflow sequence."""
        return self.registry.default_workflow
    
    def run_workflow(
        self,
        workflow: List[str],
        initial_request: str,
        session_id: str = "default",
        stop_on_error: bool = False,
        max_retries: int = 1
    ) -> Dict[str, Any]:
        """
        Run a custom workflow.
        
        Args:
            workflow: List of agent names to execute
            initial_request: User's initial request
            session_id: Optional session identifier
            stop_on_error: Whether to stop on first error
            max_retries: Number of retries per agent
            
        Returns:
            Workflow execution result
        """
        orchestrator = self._get_orchestrator(session_id)
        orchestrator.reset(initial_request)
        
        result = orchestrator.execute_workflow(
            workflow,
            stop_on_error=stop_on_error,
            max_retries=max_retries
        )
        
        return result
    
    def run_template_workflow(
        self,
        template_name: str,
        initial_request: str,
        session_id: str = "default",
        stop_on_error: bool = False
    ) -> Dict[str, Any]:
        """
        Run a predefined workflow template.
        
        Args:
            template_name: Name of the workflow template
            initial_request: User's initial request
            session_id: Optional session identifier
            stop_on_error: Whether to stop on first error
            
        Returns:
            Workflow execution result
        """
        workflow = self.registry.get_workflow(template_name)
        return self.run_workflow(
            workflow,
            initial_request,
            session_id,
            stop_on_error
        )
    
    def run_default_workflow(
        self,
        initial_request: str,
        session_id: str = "default",
        stop_on_error: bool = False
    ) -> Dict[str, Any]:
        """
        Run the default workflow.
        
        Args:
            initial_request: User's initial request
            session_id: Optional session identifier
            stop_on_error: Whether to stop on first error
            
        Returns:
            Workflow execution result
        """
        orchestrator = self._get_orchestrator(session_id)
        return orchestrator.run_default_workflow(initial_request)
    
    # ========== State Management ==========
    
    def get_state(self, session_id: str = "default") -> Dict[str, Any]:
        """Get the current state for a session."""
        orchestrator = self._get_orchestrator(session_id)
        return orchestrator.state.to_dict()
    
    def reset_session(self, session_id: str = "default") -> bool:
        """Reset a session."""
        orchestrator = self._get_orchestrator(session_id)
        orchestrator.reset()
        return True
    
    def get_execution_history(self, session_id: str = "default") -> List[Dict[str, Any]]:
        """Get the execution history for a session."""
        orchestrator = self._get_orchestrator(session_id)
        return orchestrator.execution_history
    
    # ========== Dynamic Workflow Creation ==========
    
    def create_custom_workflow(
        self,
        name: str,
        agents: List[str],
        description: str = ""
    ) -> Dict[str, Any]:
        """
        Create a custom workflow dynamically.
        
        Note: This creates the workflow definition but doesn't persist it.
        For persistence, save to a YAML file.
        
        Args:
            name: Name for the workflow
            agents: List of agent names in execution order
            description: Optional description
            
        Returns:
            Workflow definition
        """
        # Validate all agents exist
        for agent_name in agents:
            self.registry.get_agent(agent_name)  # Will raise if not found
        
        workflow_def = {
            "name": name,
            "description": description,
            "agents": agents,
            "step_count": len(agents)
        }
        
        return workflow_def
    
    def validate_workflow(self, workflow: List[str]) -> Dict[str, Any]:
        """
        Validate a workflow definition.
        
        Args:
            workflow: List of agent names
            
        Returns:
            Validation result with any errors
        """
        errors = []
        valid_agents = set(self.registry.list_agents())
        
        for i, agent_name in enumerate(workflow):
            if agent_name not in valid_agents:
                errors.append(f"Step {i+1}: Unknown agent '{agent_name}'")
        
        return {
            "valid": len(errors) == 0,
            "step_count": len(workflow),
            "errors": errors
        }


# Convenience functions for simple usage

def get_api(config_path: str = None) -> AgenticAPI:
    """Get an API instance."""
    return AgenticAPI(config_path)


def run_agent(agent_name: str, request: str) -> Dict[str, Any]:
    """Quick helper to run a single agent."""
    api = AgenticAPI()
    return api.run_single_agent(agent_name, request)


def run_workflow(workflow: List[str], request: str) -> Dict[str, Any]:
    """Quick helper to run a workflow."""
    api = AgenticAPI()
    return api.run_workflow(workflow, request)
