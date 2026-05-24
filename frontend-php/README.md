# Ardi-Agents PHP Frontend

A sleek, modern frontend for the Ardi-Agents workflow orchestration system. Built with vanilla JavaScript and Tailwind CSS for maximum performance and easy deployment on InfinityFree or any PHP hosting.

## Features

- **Identical Design** - Same UI/UX as TypeScript version
- **Lightweight** - Pure vanilla JS, no build step required
- **Modern Design** - Glass morphism, smooth animations, Lucide icons
- **Agentic Chat** - Goal-aligned conversation interface
- **Session Management** - Track and resume workflows
- **Agent Activity Panel** - Real-time agent status monitoring
- **Execution Logging** - Detailed workflow execution history
- **Responsive** - Works on desktop, tablet, and mobile

## Quick Deploy (InfinityFree)

1. Upload these files to your `htdocs` directory:
   ```
   htdocs/
   ├── index.html
   ├── app.js
   └── api.php
   ```

2. Copy the PHP API source files:
   ```
   htdocs/
   └── src/
       ├── Config.php
       ├── Agent.php
       ├── Orchestrator.php
       ├── Api.php
       └── PromptLoader.php
   ```

3. Copy prompts directory from `/workspace/php-api/prompts/`

4. Set environment variables in your hosting panel:
   - `OPENAI_API_KEY` (if using OpenAI)
   - `GOOGLE_API_KEY` (if using Google)
   - `GROQ_API_KEY` (if using Groq)
   - Other provider keys as needed

5. Access your site at `https://yourdomain.infinityfreeapp.com`

## Local Development

```bash
# Using PHP built-in server
cd /workspace/frontend-php
php -S localhost:8080

# Or copy to your php-api directory
cp index.html app.js api.php ../php-api/
cd ../php-api
php -S localhost:8080
```

## Configuration

Open Settings (gear icon) to configure:
- **API Endpoint**: Default is `./api.php` for same-directory deployment
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
frontend-php/
├── index.html      # Main HTML file with embedded styles
├── app.js          # Application logic (vanilla JS)
├── api.php         # API endpoint (proxies to PHP backend)
└── README.md       # This file
```

## License

MIT License
