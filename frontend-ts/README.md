# Ardi-Agents TypeScript Frontend

A sleek, modern frontend for the Ardi-Agents workflow orchestration system. Built with vanilla TypeScript-compatible JavaScript and Tailwind CSS for maximum performance.

## Features

- **Identical Design** - Same UI/UX as PHP version
- **TypeScript Ready** - Clean, typed JavaScript codebase
- **Modern Design** - Glass morphism, smooth animations, Lucide icons
- **Agentic Chat** - Goal-aligned conversation interface
- **Session Management** - Track and resume workflows
- **Agent Activity Panel** - Real-time agent status monitoring
- **Execution Logging** - Detailed workflow execution history
- **Responsive** - Works on desktop, tablet, and mobile

## Quick Start

```bash
# Using any static file server
cd /workspace/frontend-ts
npx serve .

# Or using Python
python -m http.server 8080

# Or using Node.js http-server
npx http-server -p 8080
```

## API Connection

The frontend connects to the Python FastAPI backend by default at `http://localhost:8000`. 

To change the API endpoint:
1. Click the Settings icon (gear)
2. Update the API Endpoint field
3. Save changes

## Configuration

Open Settings (gear icon) to configure:
- **API Endpoint**: Default is `http://localhost:8000`
- **Default Workflow**: Choose which workflow template to use
- **Auto-save Sessions**: Enable/disable session persistence

## Color Palette

The design uses a sophisticated color scheme based on color psychology:

- **Slate (Primary)**: Trust, professionalism, stability
- **Indigo/Accent**: Intelligence, creativity, depth
- **Success Green**: Achievement, completion, positive feedback
- **Error Red**: Urgency, attention needed

## Browser Support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## Performance Optimizations

- Minimal DOM manipulation
- Efficient event delegation
- CSS-based animations (GPU accelerated)
- Lazy icon loading
- LocalStorage caching
- No external dependencies beyond CDN resources

## File Structure

```
frontend-ts/
├── index.html      # Main HTML file with embedded styles
├── app.js          # Application logic (vanilla JS, TS-compatible)
└── README.md       # This file
```

## Development with TypeScript

To convert to TypeScript:

1. Rename `app.js` to `app.ts`
2. Add type definitions:
```typescript
interface Agent {
    name: string;
    description: string;
    model: string;
    provider: string;
}

interface Workflow {
    name: string;
    agents: string[];
}

interface Session {
    session_id: string;
    status: string;
    created_at: string;
}

interface AppState {
    sessionId: string | null;
    currentGoal: string;
    messages: Message[];
    agents: Agent[];
    settings: Settings;
}
```

3. Compile with `tsc`:
```bash
npm install -g typescript
tsc app.ts --target ES2020 --outDir dist
```

## License

MIT License
