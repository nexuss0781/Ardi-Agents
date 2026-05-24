# Ardi-Agents PHP API

🚀 **Lightweight Agentic Workflow System in PHP**

A task-agnostic multi-agent orchestration framework for research, coding, UI/UX design, and more. This is the PHP implementation of the Ardi-Agents system, providing a clean API layer for frontend integration.

## Features

- ✅ **16 Specialized Agents** - Configurable agents for different tasks
- ✅ **5 Workflow Templates** - Pre-defined workflows for common scenarios
- ✅ **Custom Workflows** - Create dynamic agent sequences
- ✅ **Session Management** - Track workflow state across requests
- ✅ **Error Handling** - Built-in retry logic with exponential backoff
- ✅ **Multi-Provider Support** - OpenAI and Anthropic integration
- ✅ **Zero Dependencies** - Pure PHP with cURL for HTTP requests
- ✅ **CORS Enabled** - Ready for frontend integration

## Project Structure

```
php-api/
├── api.php                 # Main API endpoint
├── config/
│   └── agents_config.json  # Agent and workflow configuration
├── prompts/                # Agent prompt templates
│   ├── analyst.txt
│   ├── architect.txt
│   ├── backend_dev.txt
│   └── ... (16 total)
├── src/
│   ├── Config.php          # Configuration loader
│   ├── PromptLoader.php    # Prompt template handler
│   ├── Agent.php           # Individual agent executor
│   ├── Orchestrator.php    # Workflow orchestration engine
│   └── Api.php             # API business logic
└── tests/
    └── test_workflow.php   # Test suite
```

## Installation

### Requirements
- PHP 8.0+
- cURL extension enabled
- OPENAI_API_KEY environment variable

### Setup

1. Clone the repository:
```bash
git clone https://github.com/nexuss0781/Ardi-Agents.git
cd Ardi-Agents/php-api
```

2. Set environment variable:
```bash
export OPENAI_API_KEY='your-api-key-here'
```

3. Run with PHP built-in server:
```bash
php -S localhost:8000 api.php
```

## API Endpoints

### Agents

#### List All Agents
```http
GET /api/agents
```

Response:
```json
{
  "success": true,
  "data": [
    {
      "id": "analyst",
      "name": "Requirements Analyst",
      "role": "Analyze requirements and define scope",
      "model": "gpt-4o",
      "provider": "openai",
      "temperature": 0.3
    }
  ]
}
```

#### Get Specific Agent
```http
GET /api/agents/{id}
```

#### Execute Agent
```http
POST /api/agents
Content-Type: application/json

{
  "agent_id": "analyst",
  "context": {
    "user_request": "Build a todo app"
  }
}
```

### Workflows

#### List Workflow Templates
```http
GET /api/workflows
```

#### Run Workflow Template
```http
POST /api/workflows
Content-Type: application/json

{
  "workflow_name": "initial_engagement",
  "initial_request": "Build a REST API for a blog platform",
  "session_id": "optional-session-id"
}
```

#### Run Custom Workflow
```http
POST /api/workflows
Content-Type: application/json

{
  "agent_sequence": ["analyst", "architect", "backend_dev"],
  "initial_request": "Create a microservice architecture",
  "session_id": "optional-session-id"
}
```

#### Run Default Workflow
```http
POST /api/workflows
Content-Type: application/json

{
  "initial_request": "Build a complete SaaS platform"
}
```

### Sessions

#### List Sessions
```http
GET /api/sessions
```

#### Get Session State
```http
GET /api/sessions/{session_id}
```

#### Clear Session
```http
DELETE /api/sessions/{session_id}
```

## Available Agents

| ID | Name | Role | Model | Temperature |
|----|------|------|-------|-------------|
| `analyst` | Requirements Analyst | Analyze requirements | gpt-4o | 0.3 |
| `architect` | System Architect | Design architecture | gpt-4o | 0.4 |
| `backend_dev` | Backend Developer | Implement backend | gpt-4o | 0.2 |
| `frontend_dev` | Frontend Developer | Implement frontend | gpt-4o | 0.2 |
| `ui_ux_designer` | UI/UX Designer | Design interfaces | gpt-4o | 0.5 |
| `database_designer` | Database Designer | Design schemas | gpt-4o | 0.3 |
| `security_expert` | Security Expert | Review security | gpt-4o | 0.2 |
| `performance_expert` | Performance Expert | Optimize performance | gpt-4o | 0.3 |
| `qa_engineer` | QA Engineer | Test and validate | gpt-4o | 0.2 |
| `devops_engineer` | DevOps Engineer | Setup deployment | gpt-4o | 0.3 |
| `technical_writer` | Technical Writer | Create documentation | gpt-4o | 0.4 |
| `code_reviewer` | Code Reviewer | Review code quality | gpt-4o | 0.2 |
| `integration_specialist` | Integration Specialist | Handle integrations | gpt-4o | 0.3 |
| `data_scientist` | Data Scientist | Analyze data | gpt-4o | 0.4 |
| `ml_engineer` | ML Engineer | Implement ML models | gpt-4o | 0.3 |
| `project_manager` | Project Manager | Coordinate delivery | gpt-4o | 0.5 |

## Workflow Templates

| Template | Steps | Purpose |
|----------|-------|---------|
| `initial_engagement` | 3 | Requirements analysis and planning |
| `planning_phase` | 4 | Architecture and design |
| `development_phase` | 4 | Implementation |
| `qa_phase` | 4 | Testing and validation |
| `completion_phase` | 3 | Documentation and deployment |

## Usage Examples

### JavaScript/Fetch Example

```javascript
// List agents
const response = await fetch('http://localhost:8000/api/agents');
const data = await response.json();
console.log(data.data);

// Execute single agent
const result = await fetch('http://localhost:8000/api/agents', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    agent_id: 'analyst',
    context: { user_request: 'Build a todo app' }
  })
});

// Run workflow
const workflow = await fetch('http://localhost:8000/api/workflows', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    workflow_name: 'initial_engagement',
    initial_request: 'Build a REST API'
  })
});
```

### PHP Example

```php
<?php
require_once 'src/Api.php';

use ArdiAgents\Api;

$api = new Api();

// List agents
$agents = $api->listAgents();

// Execute agent
$result = $api->executeAgent('analyst', [
    'user_request' => 'Build a todo app'
]);

// Run workflow
$workflow = $api->runWorkflow(
    'initial_engagement',
    'Build a REST API for a blog platform'
);

print_r($workflow);
```

## Testing

Run the test suite:

```bash
cd php-api
php tests/test_workflow.php
```

Expected output:
```
============================================================
ARDI AGENTS PHP - WORKFLOW TEST SUITE
============================================================

=== Testing Config Loading ===
✓ PASS: Config loaded with 16 agents

=== Testing Prompt Loader ===
✓ PASS: Prompt loaded successfully

=== Testing Agent Registry ===
✓ PASS: All expected agents found (5/5)

=== Testing List Agents API ===
✓ PASS: Listed 16 agents via API

=== Testing List Workflows API ===
✓ PASS: Listed 5 workflow templates

=== Testing Execute Agent: analyst ===
✓ PASS: Agent executed successfully

=== Testing Workflow Execution: initial_engagement ===
✓ PASS: Workflow executed with status: completed

============================================================
TEST SUMMARY
============================================================
✓ Passed: 7
✗ Failed: 0
Total: 7
============================================================
✅ ALL TESTS PASSED
```

## Error Handling

The API includes robust error handling:

- **Retry Logic**: Automatic retries with exponential backoff (up to 3 attempts)
- **Error Responses**: Consistent error format with detailed messages
- **Session Recovery**: Failed workflows preserve state for debugging

Example error response:
```json
{
  "success": false,
  "error": "Agent not found: invalid_agent",
  "retries": 0,
  "timestamp": "2024-01-15T10:30:00+00:00"
}
```

## Deployment

### Apache/Nginx

Configure your web server to route requests to `api.php`:

**Apache (.htaccess)**:
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ api.php [QSA,L]
```

**Nginx**:
```nginx
location / {
    try_files $uri $uri/ /api.php?$query_string;
}
```

### Docker

```dockerfile
FROM php:8.2-apache
RUN docker-php-ext-install curl
COPY . /var/www/html
EXPOSE 80
```

## License

MIT License - See LICENSE file for details.

## Support

For issues and feature requests, please open an issue on GitHub: https://github.com/nexuss0781/Ardi-Agents

---

**Ardi-Agents PHP** - Smart, lightweight agentic workflow system for modern applications.
