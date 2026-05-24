# 🚀 Ardi-Agent: Smart Agentic AI Workflow System

> **A Production-Ready, Task-Agnostic Multi-Agent Orchestration Framework**  
> *No Heavy Dependencies • Pure Python • AGI-Style Dynamic Planning*

[![Python 3.8+](https://img.shields.io/badge/python-3.8+-blue.svg)](https://www.python.org/downloads/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Tests Passing](https://img.shields.io/badge/tests-passing-green.svg)](./agentic_workflow/test_workflow.py)

---

## 📖 Overview

**Ardi-Agent** is a lightweight, intelligent multi-agent orchestration system designed for **research, coding, UI/UX design, and complex problem-solving**. Unlike traditional frameworks burdened by heavy dependencies, Ardi-Agent uses **native Python** with minimal external libraries while maintaining powerful agentic capabilities.

### 🔥 What Makes It Unique?

- **AGI-Style Meta-Planning**: A supervisory AI can observe all 16 specialized agents and dynamically construct custom workflows on-the-fly
- **Task-Agnostic Design**: Works seamlessly across research, software development, design, auditing, and documentation
- **Zero Framework Lock-in**: No LangChain, no LlamaIndex—just clean, modular Python
- **Production-Grade Error Handling**: Built-in retry logic, state persistence, and comprehensive error tracking
- **Fully Decoupled Architecture**: API, core logic, and configuration are completely separated for maximum flexibility

---

## 🎯 Use Cases

| Domain | Application |
|--------|-------------|
| **🔬 Research** | Market analysis, literature review, technical feasibility studies |
| **💻 Software Development** | Full-stack app generation, code review, debugging, refactoring |
| **🎨 UI/UX Design** | Interface prototyping, user flow optimization, accessibility auditing |
| **🔒 Security** | Vulnerability scanning, penetration testing, security audits |
| **⚡ Performance** | Bottleneck identification, optimization strategies, load testing plans |
| **📚 Documentation** | Auto-generating READMEs, API docs, technical specifications |

---

## ⚡ Quick Start

### Installation

```bash
pip install litellm pyyaml
```

### Basic Usage

```python
from agentic_workflow.api import AgenticAPI

# Initialize the system
api = AgenticAPI()

# 🔍 Discover available agents
agents = api.list_agents()
print(f"Available specialists: {agents}")

# 🎯 Run a single specialist agent
result = api.run_single_agent(
    agent_name="analyst",
    initial_request="Research the best architecture for a real-time chat application"
)
print(result["output"])

# 🔄 Execute a predefined workflow template
result = api.run_template_workflow(
    template_name="planning_phase",
    initial_request="Build an AI-powered task management system"
)

# 🏗️ Run the complete 14-step production workflow
result = api.run_default_workflow(
    initial_request="Create a full-stack e-commerce platform with React and Node.js"
)

# 🛠️ Create and run a custom dynamic workflow
custom_flow = ["analyst", "innovator", "frontend_developer", "security_auditor"]
result = api.run_workflow(
    workflow=custom_flow,
    initial_request="Design a secure voting system blockchain interface"
)
```

---

## 🧠 Core Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                         AGENTIC ORCHESTRATOR                         │
│  (Meta-Planner: Observes all agents, constructs dynamic workflows)   │
└─────────────────────────────────────────────────────────────────────┘
                                    │
        ┌───────────────────────────┼───────────────────────────┐
        │                           │                           │
        ▼                           ▼                           ▼
┌──────────────┐           ┌──────────────┐           ┌──────────────┐
│  Agent       │           │  Workflow    │           │   Prompt     │
│  Registry    │◄─────────►│   State      │◄─────────►│   Loader     │
│  (16 Agents) │           │  Management  │           │  (/prompts)  │
└──────────────┘           └──────────────┘           └──────────────┘
        │
        ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    SPECIALIZED AGENTS (16 Total)                     │
│  language_expert │ analyst │ innovator │ frontend_developer │ ...    │
└─────────────────────────────────────────────────────────────────────┘
```

### Component Breakdown

| Component | Responsibility | File |
|-----------|---------------|------|
| **AgentConfig** | Defines agent parameters (model, temperature, prompt) | `core.py` |
| **WorkflowState** | Maintains context across workflow steps | `core.py` |
| **PromptLoader** | Loads markdown prompts from `/prompts/` directory | `core.py` |
| **Agent** | Executes individual LLM calls with litellm | `core.py` |
| **AgentRegistry** | Central registry for all 16 configured agents | `core.py` |
| **Orchestrator** | Executes workflows with retry logic & error handling | `core.py` |
| **AgenticAPI** | Clean REST-like interface for all operations | `api.py` |
| **agents_config.yaml** | Declarative configuration for agents & workflows | `agents_config.yaml` |

---

## 🤖 The 16 Specialist Agents

Each agent is a domain expert with its own system prompt, optimized model, and temperature settings.

| # | Agent | Purpose | Model Provider | Temperature |
|---|-------|---------|----------------|-------------|
| 1 | **language_expert** | Polishes raw queries into clear statements | Google Gemini | 0.1 |
| 2 | **user_engagement** | Handles interaction & scope clarification | Google Gemini | 0.1 |
| 3 | **analyst** | Market research & technical planning | OpenRouter (Phi-4) | 0.0 |
| 4 | **innovator** | Creative brainstorming & feature expansion | OpenRouter (Qwerky-72B) | 0.7 |
| 5 | **frontend_developer** | UI implementation & client-side logic | OpenRouter (DeepCoder) | 0.1 |
| 6 | **backend_developer** | Server-side logic & API architecture | HuggingFace (DeepSeek) | 0.1 |
| 7 | **debugger** | Bug identification & correction | Together AI (Maestro) | 0.0 |
| 8 | **task_decomposer** | Breaks plans into actionable tasks (JSON) | OpenRouter (Phi-4) | 0.0 |
| 9 | **qa_council_planner** | Plans quality assurance activities | Google Gemini | 0.1 |
| 10 | **code_quality_auditor** | Reviews code style & best practices | HuggingFace (Qwen-Coder) | 0.0 |
| 11 | **security_auditor** | Scans for vulnerabilities | Groq (Llama Guard) | 0.0 |
| 12 | **performance_auditor** | Identifies bottlenecks | OpenRouter (Phi-4) | 0.0 |
| 13 | **ux_logic_auditor** | Verifies UX alignment with requirements | Google Gemini | 0.1 |
| 14 | **antagonistic_tester** | Red-team testing for edge cases | OpenRouter (QwQ-32B) | 0.3 |
| 15 | **justifier** | Impartial arbiter in agent disputes | Together AI (Llama-405B) | 0.0 |
| 16 | **readme_generator** | Generates professional documentation | OpenRouter (Phi-4) | 0.1 |

---

## 🔄 Workflow Templates

Pre-configured workflow sequences for common development phases:

### 1. **initial_engagement** (2 steps)
```
language_expert → user_engagement
```
*Purpose*: Refine user requirements and establish project scope.

### 2. **planning_phase** (3 steps)
```
analyst → innovator → qa_council_planner
```
*Purpose*: Research, creative expansion, and QA strategy formulation.

### 3. **development_phase** (3 steps)
```
task_decomposer → backend_developer → frontend_developer
```
*Purpose*: Break down tasks and implement full-stack functionality.

### 4. **qa_phase** (5 steps)
```
code_quality_auditor → security_auditor → performance_auditor → ux_logic_auditor → antagonistic_tester
```
*Purpose*: Comprehensive quality assurance across multiple dimensions.

### 5. **completion_phase** (1 step)
```
readme_generator
```
*Purpose*: Auto-generate professional project documentation.

### 6. **default_workflow** (14 steps - Full Pipeline)
```
Complete end-to-end pipeline from requirement gathering to documentation
```

---

## 🛠️ Advanced Features

### Dynamic Workflow Creation

The orchestrator can act as an **AGI meta-planner**, observing all available agents and constructing novel workflows:

```python
# Create a custom workflow definition
workflow_def = api.create_custom_workflow(
    name="secure_ai_chat",
    agents=[
        "language_expert",
        "analyst",
        "security_auditor",
        "backend_developer",
        "antagonistic_tester",
        "readme_generator"
    ],
    description="Security-first AI chatbot development pipeline"
)

# Validate before execution
validation = api.validate_workflow(workflow_def["agents"])
if validation["valid"]:
    result = api.run_workflow(
        workflow=workflow_def["agents"],
        initial_request="Build an encrypted AI chat application"
    )
```

### Session-Based State Management

Maintain separate workflow states for concurrent executions:

```python
# Multiple independent sessions
api.run_single_agent("analyst", "Project A", session_id="session_a")
api.run_single_agent("analyst", "Project B", session_id="session_b")

# Retrieve state per session
state_a = api.get_state("session_a")
state_b = api.get_state("session_b")

# Reset individual sessions
api.reset_session("session_a")
```

### Error Handling & Retry Logic

```python
result = api.run_workflow(
    workflow=["analyst", "backend_developer", "debugger"],
    initial_request="Complex distributed system",
    stop_on_error=False,      # Continue even if one agent fails
    max_retries=3             # Retry failed agents up to 3 times
)

# Check execution details
if not result["success"]:
    print(f"Failed at step: {result['completed_steps']}")
    print(f"Errors: {result['final_state']['errors']}")
```

---

## 📁 Project Structure

```
agentic_workflow/
├── __init__.py              # Package initialization
├── core.py                  # Core workflow engine
├── api.py                   # Clean API layer
├── agents_config.yaml       # Agent & workflow configuration
├── test_workflow.py         # Comprehensive test suite
├── examples/
│   └── run_workflow.py      # Usage examples
└── README.md                # This file

prompts/                     # Agent system prompts (markdown)
├── language_expert.md
├── user_engagement.md
├── analyst.md
├── innovator.md
├── frontend_developer.md
├── backend_developer.md
├── debugger.md
├── task_decomposer.md
├── qa_council_planner.md
├── code_quality_auditor.md
├── security_auditor.md
├── performance_auditor.md
├── ux_logic_auditor.md
├── antagonistic_tester.md
├── justifier.md
└── readme_generator.md
```

---

## ✅ Testing

Run the comprehensive test suite:

```bash
cd /workspace
python -m agentic_workflow.test_workflow
```

**Test Coverage:**
- ✅ Prompt loader validation
- ✅ Agent registry loading
- ✅ Individual agent instantiation
- ✅ Orchestrator state management
- ✅ Workflow template validation
- ✅ Custom workflow creation
- ✅ Error handling scenarios
- ✅ Session isolation

---

## 🔐 Environment Configuration

Set your LLM provider API keys:

```bash
# Google (Gemini models)
export GOOGLE_API_KEY="your_google_api_key"

# OpenRouter (Multiple open models)
export OPENROUTER_API_KEY="your_openrouter_key"

# Groq (Fast inference)
export GROQ_API_KEY="your_groq_key"

# Together AI (Large models)
export TOGETHER_API_KEY="your_together_key"

# HuggingFace (Open models)
export HUGGINGFACE_TOKEN="your_hf_token"
```

---

## 🎯 Example: Full-Stack App Development

```python
from agentic_workflow.api import AgenticAPI
import json

api = AgenticAPI()

request = """
Build a real-time collaborative whiteboard application with:
- WebSocket-based live synchronization
- Drawing tools (pen, shapes, text)
- User authentication
- Room management
- Export to PNG/PDF
"""

# Execute the complete production pipeline
result = api.run_default_workflow(request)

if result["success"]:
    print("✅ Workflow completed successfully!")
    
    # Access final artifacts
    final_state = result["final_state"]
    
    # Generated project files
    if final_state.get("project_files"):
        for filename, content in final_state["project_files"].items():
            print(f"\n📄 {filename}:")
            print(content[:200] + "..." if len(content) > 200 else content)
    
    # Execution summary
    print(f"\n📊 Execution Summary:")
    print(f"   Steps completed: {result['completed_steps']}/{result['total_steps']}")
    print(f"   Errors encountered: {len(final_state['errors'])}")
else:
    print("❌ Workflow failed")
    print(f"Stopped at step: {result['completed_steps']}")
    print(f"Error: {result.get('error')}")
```

---

## 🚀 Why Choose Ardi-Agent?

| Feature | Ardi-Agent | LangChain | Traditional Scripts |
|---------|------------|-----------|---------------------|
| **Dependencies** | Minimal (2 libs) | Heavy (50+ libs) | None |
| **Learning Curve** | Low | High | Medium |
| **Flexibility** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐ |
| **Debugging** | Easy | Complex | Easy |
| **AGI-Style Planning** | ✅ Built-in | ❌ Manual | ❌ Manual |
| **Production Ready** | ✅ Yes | ⚠️ Complex | ⚠️ Manual effort |
| **Custom Workflows** | Dynamic | Static | Manual |

---

## 📄 License

MIT License - See [LICENSE](LICENSE) for details.

---

## 🤝 Contributing

Contributions welcome! Areas of focus:
- New agent specializations
- Additional workflow templates
- Enhanced error recovery strategies
- Integration with external tools (GitHub, Jira, Figma)

---

## 📬 Support

- **Issues**: Report bugs on GitHub Issues
- **Discussions**: Join community discussions
- **Email**: nexuss0781@gmail.com

---

**Built with ❤️ by the Ardi-Agent Team**  
*Empowering developers with intelligent, autonomous AI workflows*
