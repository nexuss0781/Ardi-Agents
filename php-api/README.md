# Ardi-Agents PHP API

A pure PHP implementation of the Ardi-Agents agentic workflow system. This is a standalone, isolated deployment option that mirrors the Python version functionality with zero external dependencies (only requires PHP + cURL).

## Features

- **16 Specialized Agents** - Same agent configuration as Python version
- **5 Workflow Templates** - Predefined sequences for different project phases
- **Custom Workflows** - Dynamic agent sequences
- **Session Management** - State tracking across requests
- **Multi-Provider Support** - OpenAI, Anthropic, Google, OpenRouter, Groq, Together AI, HuggingFace
- **Error Handling** - Exponential backoff retry logic
- **CORS Enabled** - Ready for frontend integration
- **Zero Dependencies** - Pure PHP with built-in cURL

## Directory Structure

```
php-api/
├── api.php              # Main entry point
├── agents_config.yaml   # Agent and workflow configuration
├── test_api.php         # Test suite
├── src/
│   ├── Config.php       # Configuration loader
│   ├── PromptLoader.php # Prompt template loader
│   ├── Agent.php        # Individual agent execution
│   ├── Orchestrator.php # Workflow orchestration
│   └── Api.php          # Business logic layer
└── prompts/             # Agent prompt templates (16 .md files)
```

## Installation

### Requirements
- PHP 8.0+
- cURL extension (usually enabled by default)
- API keys for your chosen providers

### Setup

1. Clone or copy the `php-api` directory to your server
2. Set environment variables for your API keys:

```bash
export OPENAI_API_KEY='your-openai-key'
export ANTHROPIC_API_KEY='your-anthropic-key'
export GOOGLE_API_KEY='your-google-key'
export GROQ_API_KEY='your-groq-key'
export TOGETHER_API_KEY='your-together-key'
export HUGGINGFACE_API_KEY='your-huggingface-key'
```

3. Start the PHP built-in server:

```bash
cd php-api
php -S localhost:8000 api.php
```

## API Endpoints

### Agents

#### GET /api/agents
List all available agents.

```bash
curl http://localhost:8000/api/agents
```

Response:
```json
{
  "success": true,
  "count": 16,
  "agents": [
    {
      "id": "language_expert",
      "description": "Polishes raw user queries...",
      "model": "models/gemini-1.5-flash-latest",
      "provider": "google",
      "temperature": 0.1
    }
  ]
}
```

#### GET /api/agents/{id}
Get details for a specific agent.

```bash
curl http://localhost:8000/api/agents/analyst
```

#### POST /api/agents
Execute a single agent.

```bash
curl -X POST http://localhost:8000/api/agents \
  -H "Content-Type: application/json" \
  -d '{
    "agent_id": "language_expert",
    "request": "build me a todo app with react"
  }'
```

### Workflows

#### GET /api/workflows
List all workflow templates.

```bash
curl http://localhost:8000/api/workflows
```

#### POST /api/workflows
Run a workflow (template, custom, or default).

**Using a template:**
```bash
curl -X POST http://localhost:8000/api/workflows \
  -H "Content-Type: application/json" \
  -d '{
    "workflow_name": "initial_engagement",
    "initial_request": "Build a todo application"
  }'
```

**Using default workflow:**
```bash
curl -X POST http://localhost:8000/api/workflows \
  -H "Content-Type: application/json" \
  -d '{
    "workflow_name": "default",
    "initial_request": "Create a weather dashboard"
  }'
```

**Using custom workflow:**
```bash
curl -X POST http://localhost:8000/api/workflows \
  -H "Content-Type: application/json" \
  -d '{
    "workflow": ["language_expert", "analyst", "frontend_developer"],
    "initial_request": "Build a landing page"
  }'
```

### Sessions

#### GET /api/sessions
List all active sessions.

```bash
curl http://localhost:8000/api/sessions
```

#### GET /api/sessions/{id}
Get state of a specific session.

```bash
curl http://localhost:8000/api/sessions/session_12345
```

#### DELETE /api/sessions/{id}
Clear/delete a session.

```bash
curl -X DELETE http://localhost:8000/api/sessions/session_12345
```

## Available Agents

| ID | Description | Model | Provider |
|----|-------------|-------|----------|
| language_expert | Polishes raw user queries | gemini-1.5-flash | Google |
| user_engagement | Handles user interaction | gemini-1.5-flash | Google |
| analyst | Market research & planning | phi-4-reasoning-plus | OpenRouter |
| innovator | Creative brainstorming | qwerky-72b | OpenRouter |
| frontend_developer | UI implementation | deepcoder-14b-preview | OpenRouter |
| backend_developer | Server-side logic | DeepSeek-Coder-V2 | HuggingFace |
| debugger | Bug identification | maestro-reasoning | Together AI |
| task_decomposer | Task breakdown | phi-4-reasoning-plus | OpenRouter |
| qa_council_planner | QA planning | gemini-1.5-flash-002 | Google |
| code_quality_auditor | Code standards audit | Qwen2.5-Coder-32B | HuggingFace |
| security_auditor | Security scanning | llama-prompt-guard-2 | Groq |
| performance_auditor | Performance checks | phi-4-reasoning | OpenRouter |
| ux_logic_auditor | UX verification | gemini-1.5-flash-002 | Google |
| antagonistic_tester | Red team testing | qwq-32b-arliai-rpr | OpenRouter |
| justifier | Impartial decider | Llama-3.1-405B | Together AI |
| readme_generator | Documentation generation | phi-4-reasoning-plus | OpenRouter |

## Workflow Templates

### initial_engagement (2 steps)
- language_expert
- user_engagement

### planning_phase (3 steps)
- analyst
- innovator
- qa_council_planner

### development_phase (3 steps)
- task_decomposer
- backend_developer
- frontend_developer

### qa_phase (5 steps)
- code_quality_auditor
- security_auditor
- performance_auditor
- ux_logic_auditor
- antagonistic_tester

### completion_phase (1 step)
- readme_generator

## Testing

Run the test suite:

```bash
cd php-api
php test_api.php
```

Expected output:
```
============================================================
ARDI-AGENTS PHP API TEST SUITE
============================================================

Testing: Config Loading... ✓ PASSED
Testing: Workflow Templates Loading... ✓ PASSED
Testing: Prompt Loader - List Prompts... ✓ PASSED
Testing: Prompt Loader - Load Individual Prompt... ✓ PASSED
Testing: Prompt Loader - Render Prompt with Variables... ✓ PASSED
Testing: Agent Registry Validation... ✓ PASSED
Testing: Orchestrator - Session Management... ✓ PASSED
Testing: API Routes Configuration... ✓ PASSED
Testing: Default Workflow Validation... ✓ PASSED

============================================================
TEST SUMMARY
============================================================
✓ Passed: 9
✗ Failed: 0
Total: 9
============================================================

✅ ALL TESTS PASSED!
```

## Frontend Integration Example

```javascript
// Execute a workflow
async function runWorkflow(workflowName, request) {
  const response = await fetch('http://localhost:8000/api/workflows', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      workflow_name: workflowName,
      initial_request: request
    })
  });
  
  const data = await response.json();
  return data.result;
}

// Usage
const result = await runWorkflow('initial_engagement', 'Build a todo app');
console.log(result.final_output);
```

## Production Deployment

### Apache

Add to your `.htaccess`:
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ api.php [QSA,L]
```

### Nginx

```nginx
location /api {
    try_files $uri $uri/ /api/api.php?$query_string;
    
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

### Docker

```dockerfile
FROM php:8.2-cli

WORKDIR /app
COPY . /app

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "api.php"]
```

## Comparison with Python Version

| Feature | Python | PHP |
|---------|--------|-----|
| Dependencies | litellm, pyyaml | None (pure PHP) |
| Runtime | Python 3.9+ | PHP 8.0+ |
| Async Support | Yes | No (synchronous) |
| Performance | Fast | Fast |
| Deployment | pip install | Copy files |
| API Compatibility | ✓ | ✓ (100% compatible) |

## License

MIT License - Same as Python version

## Support

For issues or questions, please open an issue on GitHub: https://github.com/nexuss0781/Ardi-Agents
