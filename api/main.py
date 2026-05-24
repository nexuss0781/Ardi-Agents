"""
Ardi-Agents FastAPI Server
Production-ready API for agentic workflow orchestration
"""

from fastapi import FastAPI, HTTPException, BackgroundTasks
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field
from typing import Dict, List, Optional, Any
import uuid
import sys
import os

# Add parent directory to path to import core modules
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from agentic_workflow.core import AgentRegistry, Orchestrator, WorkflowState, PromptLoader

# Initialize FastAPI app
app = FastAPI(
    title="Ardi-Agents API",
    description="Task-agnostic Agentic Workflow System for Research, Coding, UI/UX Design",
    version="1.0.0",
    docs_url="/docs",
    redoc_url="/redoc"
)

# Enable CORS for frontend development
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # Configure appropriately for production
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Initialize core components
try:
    agent_registry = AgentRegistry()
    orchestrator = Orchestrator(agent_registry)
    prompt_loader = agent_registry.prompt_loader  # Get from registry
except Exception as e:
    print(f"Warning: Could not initialize core components: {e}")
    prompt_loader = None
    agent_registry = None
    orchestrator = None

# In-memory session storage (use Redis/DB for production)
sessions: Dict[str, WorkflowState] = {}


# ==================== Pydantic Models ====================

class AgentExecuteRequest(BaseModel):
    agent_name: str = Field(..., description="Name of the agent to execute")
    input_text: str = Field(..., description="Input text for the agent")
    session_id: Optional[str] = Field(None, description="Optional session ID for state management")


class AgentExecuteResponse(BaseModel):
    success: bool
    output: str
    agent_name: str
    model_used: str
    tokens_used: int
    execution_time: float
    session_id: Optional[str] = None


class WorkflowRequest(BaseModel):
    template_name: Optional[str] = Field(None, description="Name of workflow template to use")
    custom_steps: Optional[List[str]] = Field(None, description="Custom list of agent names for dynamic workflow")
    initial_request: str = Field(..., description="Initial user request or task description")
    session_id: Optional[str] = Field(None, description="Optional session ID")
    max_retries: int = Field(3, description="Maximum retry attempts per step")


class WorkflowResponse(BaseModel):
    success: bool
    final_output: str
    steps_executed: List[Dict[str, Any]]
    total_steps: int
    successful_steps: int
    failed_steps: int
    session_id: str
    execution_time: float


class SessionResponse(BaseModel):
    session_id: str
    status: str
    current_step: Optional[int]
    total_steps: Optional[int]
    last_output: Optional[str]
    created_at: str
    updated_at: str


class ErrorResponse(BaseModel):
    success: bool = False
    error: str
    detail: Optional[str] = None


# ==================== Health & Info Endpoints ====================

@app.get("/", tags=["Root"])
async def root():
    """Root endpoint with API information"""
    return {
        "name": "Ardi-Agents API",
        "version": "1.0.0",
        "description": "Task-agnostic Agentic Workflow System",
        "endpoints": {
            "health": "/health",
            "agents": "/agents",
            "workflows": "/workflows",
            "execute_agent": "/execute/agent",
            "execute_workflow": "/execute/workflow",
            "sessions": "/sessions",
            "docs": "/docs"
        }
    }


@app.get("/health", tags=["Health"])
async def health_check():
    """Health check endpoint"""
    status = {
        "status": "healthy",
        "components": {
            "prompt_loader": "initialized" if prompt_loader else "not_initialized",
            "agent_registry": "initialized" if agent_registry else "not_initialized",
            "orchestrator": "initialized" if orchestrator else "not_initialized",
            "active_sessions": len(sessions)
        }
    }
    
    if not all([prompt_loader, agent_registry, orchestrator]):
        status["status"] = "degraded"
    
    return status


@app.get("/agents", response_model=List[Dict[str, Any]], tags=["Agents"])
async def list_agents():
    """List all available agents with their configurations"""
    if not agent_registry:
        raise HTTPException(status_code=503, detail="Agent registry not initialized")
    
    agents = []
    for name, agent_obj in agent_registry.agents.items():
        config = agent_obj.config  # Get the AgentConfig from Agent object
        agents.append({
            "name": name,
            "description": config.description,
            "model": config.model,
            "provider": config.provider,
            "temperature": config.temperature,
            "prompt_file": config.prompt_file
        })
    
    return agents


@app.get("/agents/{agent_name}", tags=["Agents"])
async def get_agent(agent_name: str):
    """Get detailed information about a specific agent"""
    if not agent_registry:
        raise HTTPException(status_code=503, detail="Agent registry not initialized")
    
    if agent_name not in agent_registry.agents:
        raise HTTPException(status_code=404, detail=f"Agent '{agent_name}' not found")
    
    agent_obj = agent_registry.agents[agent_name]
    config = agent_obj.config
    # Load prompt for preview
    try:
        prompt_text = agent_obj.system_prompt
        prompt_preview = prompt_text[:200] + "..." if len(prompt_text) > 200 else prompt_text
    except Exception:
        prompt_preview = "Prompt loading failed"
    
    return {
        "name": agent_name,
        "description": config.description,
        "model": config.model,
        "provider": config.provider,
        "temperature": config.temperature,
        "prompt_file": config.prompt_file,
        "prompt_preview": prompt_preview
    }


@app.get("/workflows", tags=["Workflows"])
async def list_workflows():
    """List all available workflow templates"""
    if not orchestrator or not orchestrator.registry:
        raise HTTPException(status_code=503, detail="Orchestrator not initialized")
    
    registry = orchestrator.registry
    return {
        "templates": list(registry.workflow_templates.keys()),
        "default_workflow": registry.default_workflow,
        "total_templates": len(registry.workflow_templates)
    }


@app.get("/workflows/{template_name}", tags=["Workflows"])
async def get_workflow_template(template_name: str):
    """Get details of a specific workflow template"""
    if not orchestrator or not orchestrator.registry:
        raise HTTPException(status_code=503, detail="Orchestrator not initialized")
    
    registry = orchestrator.registry
    if template_name not in registry.workflow_templates:
        raise HTTPException(status_code=404, detail=f"Workflow template '{template_name}' not found")
    
    steps = registry.workflow_templates[template_name]
    return {
        "template_name": template_name,
        "steps": steps,
        "total_steps": len(steps),
        "agents_involved": list(set(steps))
    }


# ==================== Execution Endpoints ====================

@app.post("/execute/agent", response_model=AgentExecuteResponse, tags=["Execution"])
async def execute_agent(request: AgentExecuteRequest):
    """Execute a single agent with given input"""
    if not agent_registry:
        raise HTTPException(status_code=503, detail="Agent registry not initialized")
    
    try:
        # Validate agent exists
        if request.agent_name not in agent_registry.agents:
            raise HTTPException(status_code=404, detail=f"Agent '{request.agent_name}' not found")
        
        # Get or create session
        session_id = request.session_id or str(uuid.uuid4())
        if session_id not in sessions:
            sessions[session_id] = WorkflowState(initial_request=request.input_text)
        
        # Execute agent
        result = await orchestrator.execute_agent_async(
            agent_name=request.agent_name,
            input_text=request.input_text,
            state=sessions[session_id]
        )
        
        return AgentExecuteResponse(
            success=result["success"],
            output=result["output"],
            agent_name=request.agent_name,
            model_used=result.get("model_used", "unknown"),
            tokens_used=result.get("tokens_used", 0),
            execution_time=result.get("execution_time", 0.0),
            session_id=session_id
        )
    
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Agent execution failed: {str(e)}")


@app.post("/execute/workflow", response_model=WorkflowResponse, tags=["Execution"])
async def execute_workflow(request: WorkflowRequest, background_tasks: BackgroundTasks):
    """Execute a workflow (template-based or custom)"""
    if not orchestrator:
        raise HTTPException(status_code=503, detail="Orchestrator not initialized")
    
    try:
        # Generate session ID
        session_id = request.session_id or str(uuid.uuid4())
        
        # Determine workflow steps
        if request.custom_steps:
            steps = request.custom_steps
            workflow_name = "custom"
        elif request.template_name:
            if request.template_name not in orchestrator.workflow_templates:
                raise HTTPException(status_code=404, detail=f"Workflow template '{request.template_name}' not found")
            steps = orchestrator.workflow_templates[request.template_name]
            workflow_name = request.template_name
        else:
            steps = orchestrator.default_workflow
            workflow_name = "default"
        
        # Initialize session state
        sessions[session_id] = WorkflowState(
            initial_request=request.initial_request,
            current_workflow=steps
        )
        
        # Execute workflow
        result = await orchestrator.execute_workflow_async(
            steps=steps,
            initial_request=request.initial_request,
            state=sessions[session_id],
            max_retries=request.max_retries
        )
        
        # Calculate statistics
        steps_executed = []
        successful = 0
        failed = 0
        
        for step_result in result.get("step_results", []):
            step_info = {
                "step": step_result.get("step"),
                "agent": step_result.get("agent"),
                "success": step_result.get("success", False),
                "output_preview": step_result.get("output", "")[:100] + "..." if len(step_result.get("output", "")) > 100 else step_result.get("output", "")
            }
            steps_executed.append(step_info)
            if step_result.get("success"):
                successful += 1
            else:
                failed += 1
        
        return WorkflowResponse(
            success=result.get("success", False),
            final_output=result.get("final_output", ""),
            steps_executed=steps_executed,
            total_steps=len(steps),
            successful_steps=successful,
            failed_steps=failed,
            session_id=session_id,
            execution_time=result.get("execution_time", 0.0)
        )
    
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Workflow execution failed: {str(e)}")


# ==================== Session Management Endpoints ====================

@app.get("/sessions", tags=["Sessions"])
async def list_sessions():
    """List all active sessions"""
    session_list = []
    for session_id, state in sessions.items():
        session_list.append({
            "session_id": session_id,
            "status": "completed" if state.completed else "in_progress",
            "current_step": state.current_step_index,
            "total_steps": len(state.current_workflow) if state.current_workflow else 0,
            "created_at": state.created_at.isoformat(),
            "updated_at": state.updated_at.isoformat()
        })
    
    return {"sessions": session_list, "total": len(session_list)}


@app.get("/sessions/{session_id}", response_model=SessionResponse, tags=["Sessions"])
async def get_session(session_id: str):
    """Get detailed session information"""
    if session_id not in sessions:
        raise HTTPException(status_code=404, detail=f"Session '{session_id}' not found")
    
    state = sessions[session_id]
    return SessionResponse(
        session_id=session_id,
        status="completed" if state.completed else "in_progress",
        current_step=state.current_step_index,
        total_steps=len(state.current_workflow) if state.current_workflow else 0,
        last_output=state.context.get("last_output"),
        created_at=state.created_at.isoformat(),
        updated_at=state.updated_at.isoformat()
    )


@app.delete("/sessions/{session_id}", tags=["Sessions"])
async def delete_session(session_id: str):
    """Delete a session"""
    if session_id not in sessions:
        raise HTTPException(status_code=404, detail=f"Session '{session_id}' not found")
    
    del sessions[session_id]
    return {"success": True, "message": f"Session '{session_id}' deleted"}


@app.post("/sessions/clear", tags=["Sessions"])
async def clear_sessions():
    """Clear all sessions"""
    count = len(sessions)
    sessions.clear()
    return {"success": True, "message": f"Cleared {count} sessions"}


# ==================== Dynamic Workflow Endpoints ====================

class DynamicWorkflowRequest(BaseModel):
    initial_request: str
    context: Optional[Dict[str, Any]] = Field(default_factory=dict)
    max_agents: int = Field(5, description="Maximum number of agents to involve")


class DynamicWorkflowPlan(BaseModel):
    plan: List[str]
    reasoning: str
    estimated_steps: int


@app.post("/plan/dynamic", response_model=DynamicWorkflowPlan, tags=["Planning"])
async def plan_dynamic_workflow(request: DynamicWorkflowRequest):
    """
    Use meta-planning agent to dynamically create a workflow based on the request.
    This simulates AGI-style planning where the system analyzes the request and selects appropriate agents.
    """
    if not agent_registry:
        raise HTTPException(status_code=503, detail="Agent registry not initialized")
    
    try:
        # Simple rule-based planning (can be enhanced with LLM-based planning)
        available_agents = list(agent_registry.agents.keys())
        request_lower = request.initial_request.lower()
        
        selected_agents = []
        reasoning_parts = []
        
        # Analyze request and select relevant agents
        if any(word in request_lower for word in ["code", "program", "develop", "build", "software"]):
            selected_agents.extend(["analyst", "innovator", "frontend_developer", "backend_developer"])
            reasoning_parts.append("Detected software development task")
        
        if any(word in request_lower for word in ["design", "ui", "ux", "interface", "visual"]):
            selected_agents.extend(["ux_logic_auditor", "frontend_developer", "innovator"])
            reasoning_parts.append("Detected design task")
        
        if any(word in request_lower for word in ["research", "analyze", "study", "investigate"]):
            selected_agents.extend(["analyst", "qa_council_planner", "justifier"])
            reasoning_parts.append("Detected research task")
        
        if any(word in request_lower for word in ["test", "qa", "quality", "bug", "error"]):
            selected_agents.extend(["debugger", "security_auditor", "performance_auditor"])
            reasoning_parts.append("Detected testing/QA task")
        
        if any(word in request_lower for word in ["document", "write", "explain", "guide"]):
            selected_agents.append("readme_generator")
            reasoning_parts.append("Detected documentation task")
        
        # Ensure we have at least one agent
        if not selected_agents:
            selected_agents = ["analyst", "innovator"]
            reasoning_parts.append("General task detected, using default agents")
        
        # Limit to max_agents
        selected_agents = selected_agents[:request.max_agents]
        
        # Add project manager for coordination if multiple agents
        if len(selected_agents) > 1 and "project_manager" not in selected_agents:
            selected_agents.insert(0, "project_manager")
        
        return DynamicWorkflowPlan(
            plan=selected_agents,
            reasoning=". ".join(reasoning_parts),
            estimated_steps=len(selected_agents)
        )
    
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Planning failed: {str(e)}")


# ==================== Error Handlers ====================

@app.exception_handler(Exception)
async def global_exception_handler(request, exc):
    """Global exception handler"""
    return ErrorResponse(
        error=str(exc),
        detail=getattr(exc, 'detail', None)
    )


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
