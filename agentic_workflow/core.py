"""
Agentic Workflow Core - Native Python Implementation

This module provides a clean, decoupled agentic workflow system without heavy dependencies.
It features:
- Individual agent execution with their own prompts
- Dynamic workflow orchestration
- Error handling and retry logic
- Clean separation between agents, API, and orchestration
"""

import os
import yaml
import json
from typing import Dict, Any, List, Optional, Callable
from dataclasses import dataclass, field
from pathlib import Path
import litellm


@dataclass
class AgentConfig:
    """Configuration for a single agent."""
    name: str
    prompt_file: str
    description: str
    model: str
    provider: str
    temperature: float = 0.1
    response_format: Optional[str] = None


@dataclass 
class WorkflowState:
    """The state passed through the workflow."""
    initial_request: str = ""
    refined_query: Optional[str] = None
    project_brief: Optional[Dict[str, Any]] = None
    research_document: Optional[str] = None
    conceptual_plan: Optional[str] = None
    technical_plan: Optional[str] = None
    task_list: Optional[List[Dict]] = None
    project_files: Dict[str, str] = field(default_factory=dict)
    history_log: List[str] = field(default_factory=list)
    current_step: Optional[str] = None
    errors: List[str] = field(default_factory=list)
    
    def to_dict(self) -> Dict[str, Any]:
        """Convert state to dictionary for LLM context."""
        return {
            "initial_request": self.initial_request,
            "refined_query": self.refined_query,
            "project_brief": self.project_brief,
            "research_document": self.research_document,
            "conceptual_plan": self.conceptual_plan,
            "technical_plan": self.technical_plan,
            "task_list": self.task_list,
            "project_files": self.project_files,
            "history_log": self.history_log[-10:],  # Last 10 entries
            "current_step": self.current_step,
            "errors": self.errors
        }


class PromptLoader:
    """Loads prompts from the prompts directory."""
    
    def __init__(self, prompts_dir: str = None):
        if prompts_dir is None:
            # Default to /workspace/prompts
            prompts_dir = Path(__file__).parent.parent / "prompts"
        self.prompts_dir = Path(prompts_dir)
        
    def load(self, filename: str) -> str:
        """Load a prompt file by name."""
        prompt_path = self.prompts_dir / filename
        
        if not prompt_path.exists():
            raise FileNotFoundError(f"Prompt file not found: {prompt_path}")
        
        with open(prompt_path, 'r', encoding='utf-8') as f:
            return f.read()
    
    def list_available(self) -> List[str]:
        """List all available prompt files."""
        return [f.name for f in self.prompts_dir.glob("*.md")]


class Agent:
    """A single AI agent that executes a specific task."""
    
    def __init__(self, config: AgentConfig, prompt_loader: PromptLoader):
        self.config = config
        self.prompt_loader = prompt_loader
        self._system_prompt: Optional[str] = None
    
    @property
    def system_prompt(self) -> str:
        """Lazy-load the system prompt."""
        if self._system_prompt is None:
            self._system_prompt = self.prompt_loader.load(self.config.prompt_file)
        return self._system_prompt
    
    def execute(self, state: WorkflowState, user_input: Optional[str] = None) -> Dict[str, Any]:
        """
        Execute the agent with the current state.
        
        Args:
            state: The current workflow state
            user_input: Optional additional user input
            
        Returns:
            Dictionary with agent output and any state updates
        """
        print(f"--- [Agent] Executing: {self.config.name} ---")
        print(f"    Model: {self.config.model} ({self.config.provider})")
        
        # Prepare the context from state
        state_context = json.dumps(state.to_dict(), indent=2, default=str)
        
        # Build the user message
        if user_input:
            user_content = f"{user_input}\n\n--- Current State ---\n{state_context}"
        else:
            user_content = state_context
        
        try:
            # Prepare completion arguments with proper provider prefix
            model_name = self.config.model
            
            # Ensure model name has provider prefix for litellm
            if self.config.provider and not model_name.startswith(f"{self.config.provider}/"):
                # Add provider prefix if not already present
                if self.config.provider == "google":
                    model_name = f"gemini/{self.config.model}"
                elif self.config.provider == "openrouter":
                    if not "/" in self.config.model or self.config.model.count('/') < 1:
                        model_name = f"openrouter/{self.config.model}"
                elif self.config.provider == "huggingface":
                    model_name = f"huggingface/{self.config.model}"
            
            # Prepare completion arguments
            completion_args = {
                "model": model_name,
                "messages": [
                    {"role": "system", "content": self.system_prompt},
                    {"role": "user", "content": user_content}
                ],
                "temperature": self.config.temperature,
            }
            
            # Add response format if specified
            if self.config.response_format == "json_object":
                completion_args["response_format"] = {"type": "json_object"}
            
            # Call the LLM
            response = litellm.completion(**completion_args)
            
            content = response.choices[0].message.content.strip()
            
            # Parse JSON if response_format is json_object
            if self.config.response_format == "json_object":
                try:
                    parsed = json.loads(content)
                    return {"output": parsed, "raw_output": content}
                except json.JSONDecodeError as e:
                    print(f"--- [Agent] Warning: Failed to parse JSON response: {e}")
                    return {"output": content, "raw_output": content, "parse_error": str(e)}
            
            return {"output": content, "raw_output": content}
            
        except Exception as e:
            error_msg = f"Agent {self.config.name} failed: {str(e)}"
            print(f"--- [Agent] ERROR: {error_msg} ---")
            return {"output": None, "error": error_msg, "raw_error": str(e)}


class AgentRegistry:
    """Registry that loads and manages all available agents."""
    
    def __init__(self, config_path: str = None):
        if config_path is None:
            config_path = Path(__file__).parent / "agents_config.yaml"
        
        self.config_path = Path(config_path)
        self.agents: Dict[str, Agent] = {}
        self.workflow_templates: Dict[str, List[str]] = {}
        self.default_workflow: List[str] = []
        self.prompt_loader = PromptLoader()
        
        self._load_config()
    
    def _load_config(self):
        """Load agent configurations from YAML file."""
        if not self.config_path.exists():
            raise FileNotFoundError(f"Agent config not found: {self.config_path}")
        
        with open(self.config_path, 'r') as f:
            config = yaml.safe_load(f)
        
        # Load agents
        for name, agent_config in config.get('agents', {}).items():
            agent_config_obj = AgentConfig(
                name=name,
                prompt_file=agent_config['prompt_file'],
                description=agent_config['description'],
                model=agent_config['model'],
                provider=agent_config['provider'],
                temperature=agent_config.get('temperature', 0.1),
                response_format=agent_config.get('response_format')
            )
            
            agent = Agent(agent_config_obj, self.prompt_loader)
            self.agents[name] = agent
        
        # Load workflow templates
        self.workflow_templates = config.get('workflow_templates', {})
        
        # Load default workflow
        self.default_workflow = config.get('default_workflow', [])
        
        print(f"--- [Registry] Loaded {len(self.agents)} agents ---")
        print(f"--- [Registry] Available workflows: {list(self.workflow_templates.keys())} ---")
    
    def get_agent(self, name: str) -> Agent:
        """Get an agent by name."""
        if name not in self.agents:
            raise ValueError(f"Unknown agent: {name}. Available: {list(self.agents.keys())}")
        return self.agents[name]
    
    def get_workflow(self, name: str) -> List[str]:
        """Get a workflow template by name."""
        if name not in self.workflow_templates:
            raise ValueError(f"Unknown workflow: {name}. Available: {list(self.workflow_templates.keys())}")
        return self.workflow_templates[name]
    
    def list_agents(self) -> List[str]:
        """List all available agent names."""
        return list(self.agents.keys())


class Orchestrator:
    """
    The main orchestrator that manages workflow execution.
    
    Can execute:
    1. Predefined workflows from config
    2. Custom dynamic workflows
    3. Individual agents
    """
    
    def __init__(self, registry: AgentRegistry = None):
        self.registry = registry or AgentRegistry()
        self.state = WorkflowState()
        self.execution_history: List[Dict[str, Any]] = []
    
    def reset(self, initial_request: str = ""):
        """Reset the orchestrator state for a new workflow."""
        self.state = WorkflowState(initial_request=initial_request)
        self.execution_history = []
        print("--- [Orchestrator] State reset ---")
    
    def execute_agent(
        self, 
        agent_name: str, 
        user_input: Optional[str] = None,
        update_state_callback: Optional[Callable[[Dict], None]] = None
    ) -> Dict[str, Any]:
        """
        Execute a single agent and update state.
        
        Args:
            agent_name: Name of the agent to execute
            user_input: Optional user input for this step
            update_state_callback: Optional callback to process agent output into state updates
            
        Returns:
            Agent execution result
        """
        agent = self.registry.get_agent(agent_name)
        self.state.current_step = agent_name
        
        result = agent.execute(self.state, user_input)
        
        # Record execution
        self.execution_history.append({
            "agent": agent_name,
            "success": "error" not in result,
            "timestamp": str(len(self.execution_history))
        })
        
        # Update state history
        if "error" in result:
            self.state.errors.append(result["error"])
            self.state.history_log.append(f"[ERROR] {agent_name}: {result['error']}")
        else:
            self.state.history_log.append(f"[SUCCESS] {agent_name} completed")
            
            # Apply state updates if callback provided
            if update_state_callback:
                updates = update_state_callback(result)
                self._apply_updates(updates)
        
        return result
    
    def _apply_updates(self, updates: Dict[str, Any]):
        """Apply dictionary updates to the state."""
        if not updates:
            return
        
        for key, value in updates.items():
            if hasattr(self.state, key):
                setattr(self.state, key, value)
    
    def execute_workflow(
        self,
        workflow: List[str],
        stop_on_error: bool = False,
        max_retries: int = 1
    ) -> Dict[str, Any]:
        """
        Execute a sequence of agents as a workflow.
        
        Args:
            workflow: List of agent names to execute in order
            stop_on_error: Whether to stop on first error
            max_retries: Number of retries per agent
            
        Returns:
            Final state and execution summary
        """
        print(f"\n{'='*60}")
        print(f"--- [Orchestrator] Starting workflow: {workflow} ---")
        print(f"{'='*60}\n")
        
        results = {}
        
        for i, agent_name in enumerate(workflow):
            print(f"\n--- Step {i+1}/{len(workflow)}: {agent_name} ---")
            
            retries = 0
            success = False
            
            while retries <= max_retries and not success:
                if retries > 0:
                    print(f"--- Retry {retries}/{max_retries} for {agent_name} ---")
                
                result = self.execute_agent(agent_name)
                results[agent_name] = result
                
                if "error" not in result:
                    success = True
                else:
                    retries += 1
                    if retries > max_retries:
                        if stop_on_error:
                            print(f"--- [Orchestrator] Stopping workflow due to error ---")
                            return {
                                "success": False,
                                "completed_steps": i,
                                "total_steps": len(workflow),
                                "results": results,
                                "final_state": self.state.to_dict(),
                                "error": result["error"]
                            }
            
            if not success:
                print(f"--- [Orchestrator] Agent {agent_name} failed after {max_retries} retries ---")
        
        print(f"\n{'='*60}")
        print(f"--- [Orchestrator] Workflow completed ---")
        print(f"{'='*60}\n")
        
        return {
            "success": len(self.state.errors) == 0,
            "completed_steps": len(workflow),
            "total_steps": len(workflow),
            "results": results,
            "final_state": self.state.to_dict(),
            "execution_history": self.execution_history
        }
    
    def run_default_workflow(self, initial_request: str) -> Dict[str, Any]:
        """Run the default workflow with an initial request."""
        self.reset(initial_request)
        return self.execute_workflow(self.registry.default_workflow)
    
    def run_template_workflow(self, template_name: str, initial_request: str) -> Dict[str, Any]:
        """Run a named workflow template."""
        self.reset(initial_request)
        workflow = self.registry.get_workflow(template_name)
        return self.execute_workflow(workflow)


# Convenience function for simple usage
def run_agent(agent_name: str, initial_request: str, config_path: str = None) -> Dict[str, Any]:
    """
    Run a single agent with an initial request.
    
    Args:
        agent_name: Name of the agent from config
        initial_request: The user's initial request
        config_path: Optional path to agents_config.yaml
        
    Returns:
        Agent execution result
    """
    registry = AgentRegistry(config_path)
    orchestrator = Orchestrator(registry)
    orchestrator.reset(initial_request)
    return orchestrator.execute_agent(agent_name, initial_request)


def run_workflow(
    workflow: List[str], 
    initial_request: str,
    config_path: str = None
) -> Dict[str, Any]:
    """
    Run a custom workflow with an initial request.
    
    Args:
        workflow: List of agent names to execute
        initial_request: The user's initial request
        config_path: Optional path to agents_config.yaml
        
    Returns:
        Workflow execution result
    """
    registry = AgentRegistry(config_path)
    orchestrator = Orchestrator(registry)
    orchestrator.reset(initial_request)
    return orchestrator.execute_workflow(workflow)
