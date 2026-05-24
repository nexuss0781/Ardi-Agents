/**
 * Nexus Tools - Frontend Application
 * Modern Chat Interface with Tool Integration
 * 
 * Features:
 * - Aggressive Caching
 * - Lucide Icons
 * - Unique Tabs per Tool
 * - Modern Dark UI
 */

// Tool Configuration with Modern Lucide Icons
const TOOLS_CONFIG = [
    {
        id: 'file-manager',
        name: 'File Manager',
        description: 'Full CRUD operations with line numbers',
        icon: 'folder-open',
        color: 'text-blue-400',
        bgColor: 'bg-blue-500/10',
        borderColor: 'border-blue-500/20',
        methods: [
            { name: 'list_files', icon: 'list', desc: 'List directory contents' },
            { name: 'read_file', icon: 'file-text', desc: 'Read file with line numbers' },
            { name: 'write_file', icon: 'file-edit', desc: 'Create or update file' },
            { name: 'delete_file', icon: 'trash-2', desc: 'Delete file or folder' },
            { name: 'move_file', icon: 'move', desc: 'Move file/folder' },
            { name: 'copy_file', icon: 'copy', desc: 'Copy file/folder' },
            { name: 'create_folder', icon: 'folder-plus', desc: 'Create new folder' },
            { name: 'rename_file', icon: 'edit-3', desc: 'Rename file/folder' }
        ]
    },
    {
        id: 'web-search',
        name: 'Web Search',
        description: 'Search and fetch from internet',
        icon: 'globe',
        color: 'text-emerald-400',
        bgColor: 'bg-emerald-500/10',
        borderColor: 'border-emerald-500/20',
        methods: [
            { name: 'search_web', icon: 'search', desc: 'Search the web' },
            { name: 'fetch_url', icon: 'download', desc: 'Fetch URL content' },
            { name: 'get_news', icon: 'newspaper', desc: 'Get latest news' },
            { name: 'get_weather', icon: 'cloud', desc: 'Get weather info' }
        ]
    },
    {
        id: 'text-processor',
        name: 'Text Processor',
        description: 'Advanced text manipulation',
        icon: 'type',
        color: 'text-amber-400',
        bgColor: 'bg-amber-500/10',
        borderColor: 'border-amber-500/20',
        methods: [
            { name: 'format_text', icon: 'bold', desc: 'Format text styles' },
            { name: 'convert_case', icon: 'arrow-up-down', desc: 'Change text case' },
            { name: 'extract_emails', icon: 'mail', desc: 'Extract email addresses' },
            { name: 'extract_urls', icon: 'link', desc: 'Extract URLs from text' },
            { name: 'word_count', icon: 'hash', desc: 'Count words and chars' },
            { name: 'remove_duplicates', icon: 'layers', desc: 'Remove duplicate lines' }
        ]
    }
];

// State Management
class AppState {
    constructor() {
        this.currentTool = null;
        this.messages = [];
        this.isLoading = false;
    }
}

const state = new AppState();

// DOM Elements
const elements = {
    toolsContainer: document.getElementById('tools-container'),
    chatContainer: document.getElementById('chat-container'),
    userInput: document.getElementById('user-input'),
    sendBtn: document.getElementById('send-btn'),
    currentToolName: document.getElementById('current-tool-name'),
    toggleSidebar: document.getElementById('toggle-sidebar'),
    sidebar: document.getElementById('sidebar')
};

// Initialize Application
function init() {
    renderTools();
    setupEventListeners();
    loadCachedData();
}

// Render Tool Tabs in Sidebar
function renderTools() {
    elements.toolsContainer.innerHTML = '';
    
    // General Chat Option
    const generalChat = createToolElement({
        id: 'general',
        name: 'General Chat',
        icon: 'message-square',
        color: 'text-slate-400',
        bgColor: 'bg-slate-500/10',
        borderColor: 'border-slate-500/20',
        isGeneral: true
    });
    elements.toolsContainer.appendChild(generalChat);

    // Render each tool category
    TOOLS_CONFIG.forEach(tool => {
        const toolEl = createToolElement(tool);
        elements.toolsContainer.appendChild(toolEl);
        
        // Add method sub-items (collapsible)
        const methodsContainer = document.createElement('div');
        methodsContainer.id = `methods-${tool.id}`;
        methodsContainer.className = 'hidden ml-4 mt-2 space-y-1 border-l border-slate-700 pl-3';
        
        tool.methods.forEach(method => {
            const methodBtn = document.createElement('button');
            methodBtn.className = 'w-full flex items-center gap-2 px-3 py-2 text-xs text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition-all group';
            methodBtn.innerHTML = `
                <i data-lucide="${method.icon}" class="w-3 h-3 text-slate-500 group-hover:${tool.color}"></i>
                <span>${method.name}</span>
            `;
            methodBtn.onclick = () => selectMethod(tool, method);
            methodsContainer.appendChild(methodBtn);
        });
        
        elements.toolsContainer.appendChild(methodsContainer);
    });
    
    // Re-initialize icons
    setTimeout(() => lucide.createIcons(), 0);
}

// Create Tool Button Element
function createToolElement(tool) {
    const btn = document.createElement('button');
    btn.className = `tool-btn w-full flex items-center gap-3 px-4 py-3 rounded-r-lg transition-all duration-200 group ${tool.isGeneral ? '' : 'hover:bg-slate-800/50'}`;
    btn.dataset.toolId = tool.id;
    btn.onclick = () => selectTool(tool);
    
    btn.innerHTML = `
        <div class="w-9 h-9 rounded-lg ${tool.bgColor} ${tool.borderColor} border flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
            <i data-lucide="${tool.icon}" class="w-5 h-5 ${tool.color}"></i>
        </div>
        <div class="text-left overflow-hidden">
            <div class="font-medium text-sm text-slate-200 truncate">${tool.name}</div>
            <div class="text-xs text-slate-500 truncate">${tool.description}</div>
        </div>
    `;
    
    return btn;
}

// Select Tool
function selectTool(tool) {
    state.currentTool = tool;
    
    // Update UI
    document.querySelectorAll('.tool-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.toolId === tool.id) {
            btn.classList.add('active');
        }
    });
    
    // Toggle methods visibility
    TOOLS_CONFIG.forEach(t => {
        const methodsDiv = document.getElementById(`methods-${t.id}`);
        if (methodsDiv && t.id === tool.id) {
            methodsDiv.classList.toggle('hidden');
        } else if (methodsDiv) {
            methodsDiv.classList.add('hidden');
        }
    });
    
    // Update header
    elements.currentToolName.textContent = tool.name;
    
    // Add system message
    if (!tool.isGeneral) {
        addMessage('system', `🔧 Switched to **${tool.name}**. Available methods: ${tool.methods.map(m => m.name).join(', ')}`);
    }
    
    // Focus input
    elements.userInput.focus();
}

// Select Specific Method
function selectTool(tool, method) {
    state.currentTool = tool;
    state.currentMethod = method;
    
    // Update UI
    document.querySelectorAll('.tool-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.toolId === tool.id) {
            btn.classList.add('active');
        }
    });
    
    elements.currentToolName.textContent = `${tool.name} • ${method.name}`;
    addMessage('system', `⚡ Using method: **${method.name}** - ${method.desc}`);
    elements.userInput.focus();
}

// Setup Event Listeners
function setupEventListeners() {
    // Send button
    elements.sendBtn.addEventListener('click', sendMessage);
    
    // Enter key to send (Shift+Enter for new line)
    elements.userInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
    
    // Sidebar toggle
    elements.toggleSidebar.addEventListener('click', () => {
        elements.sidebar.classList.toggle('-ml-72');
    });
}

// Send Message
async function sendMessage() {
    const text = elements.userInput.value.trim();
    if (!text || state.isLoading) return;
    
    // Clear input
    elements.userInput.value = '';
    elements.userInput.style.height = 'auto';
    
    // Add user message
    addMessage('user', text);
    
    state.isLoading = true;
    
    // Show typing indicator
    const typingId = showTypingIndicator();
    
    try {
        // Prepare API request
        const payload = {
            message: text,
            tool: state.currentTool?.id || 'general',
            method: state.currentMethod?.name || null,
            timestamp: Date.now()
        };
        
        // Cache buster
        const cacheBuster = `?t=${Date.now()}`;
        
        // Simulate API call (replace with actual endpoint)
        await simulateAPIResponse(payload);
        
    } catch (error) {
        addMessage('error', `Error: ${error.message}`);
    } finally {
        removeTypingIndicator(typingId);
        state.isLoading = false;
    }
}

// Add Message to Chat
function addMessage(type, content) {
    const msgDiv = document.createElement('div');
    msgDiv.className = 'flex gap-4 max-w-4xl mx-auto animate-slide-up';
    
    const isUser = type === 'user';
    const isError = type === 'error';
    const isSystem = type === 'system';
    
    let avatar, bubbleClass, name;
    
    if (isUser) {
        avatar = `<div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center shrink-0 shadow-lg"><i data-lucide="user" class="w-5 h-5 text-white"></i></div>`;
        bubbleClass = 'message-user text-white';
        name = 'You';
    } else if (isError) {
        avatar = `<div class="w-10 h-10 rounded-full bg-gradient-to-br from-red-500 to-orange-500 flex items-center justify-center shrink-0 shadow-lg"><i data-lucide="alert-circle" class="w-5 h-5 text-white"></i></div>`;
        bubbleClass = 'bg-red-900/20 border border-red-800/50 text-red-200';
        name = 'Error';
    } else if (isSystem) {
        avatar = `<div class="w-10 h-10 rounded-full bg-gradient-to-br from-slate-600 to-slate-700 flex items-center justify-center shrink-0 shadow-lg"><i data-lucide="info" class="w-5 h-5 text-slate-300"></i></div>`;
        bubbleClass = 'bg-slate-800/50 border border-slate-700 text-slate-400 italic';
        name = 'System';
    } else {
        avatar = `<div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shrink-0 shadow-lg"><i data-lucide="bot" class="w-6 h-6 text-white"></i></div>`;
        bubbleClass = 'message-ai text-slate-300';
        name = 'Assistant';
    }
    
    // Process markdown-like syntax
    const processedContent = processContent(content);
    
    msgDiv.innerHTML = `
        ${avatar}
        <div class="space-y-1 flex-1">
            <div class="font-semibold text-xs text-slate-500">${name}</div>
            <div class="${bubbleClass} p-4 rounded-2xl ${isUser ? 'rounded-tr-none' : 'rounded-tl-none'} shadow-md leading-relaxed">
                ${processedContent}
            </div>
        </div>
    `;
    
    elements.chatContainer.appendChild(msgDiv);
    scrollToBottom();
    lucide.createIcons();
}

// Process Content (simple markdown)
function processContent(content) {
    // Bold
    content = content.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    // Code blocks
    content = content.replace(/```(\w*)\n([\s\S]*?)```/g, (match, lang, code) => {
        return `<div class="code-block"><pre><code>${escapeHtml(code)}</code></pre></div>`;
    });
    // Inline code
    content = content.replace(/`([^`]+)`/g, '<code class="bg-slate-800 px-1.5 py-0.5 rounded text-sm text-pink-400">$1</code>');
    // Line breaks
    content = content.replace(/\n/g, '<br>');
    
    return content;
}

// Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Show Typing Indicator
function showTypingIndicator() {
    const id = 'typing-' + Date.now();
    const div = document.createElement('div');
    div.id = id;
    div.className = 'flex gap-4 max-w-4xl mx-auto animate-slide-up';
    div.innerHTML = `
        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shrink-0 shadow-lg">
            <i data-lucide="bot" class="w-6 h-6 text-white"></i>
        </div>
        <div class="flex items-center gap-2 p-4 bg-panel border border-slate-800 rounded-2xl rounded-tl-none">
            <div class="w-2 h-2 bg-slate-500 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
            <div class="w-2 h-2 bg-slate-500 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
            <div class="w-2 h-2 bg-slate-500 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
        </div>
    `;
    elements.chatContainer.appendChild(div);
    scrollToBottom();
    lucide.createIcons();
    return id;
}

// Remove Typing Indicator
function removeTypingIndicator(id) {
    const el = document.getElementById(id);
    if (el) el.remove();
}

// Scroll to Bottom
function scrollToBottom() {
    elements.chatContainer.scrollTo({
        top: elements.chatContainer.scrollHeight,
        behavior: 'smooth'
    });
}

// Simulate API Response (Replace with actual fetch)
async function simulateAPIResponse(payload) {
    return new Promise(resolve => {
        setTimeout(() => {
            let response = '';
            
            if (payload.tool === 'file-manager') {
                response = `📁 **File Manager Action**\n\nProcessing request for method: \`${payload.method || 'general'}\`\n\nQuery: ${payload.message}\n\n\`\`\`bash
# Example output with line numbers
1  drwxr-xr-x  user  staff  64 Jan 15 10:30  .
2  drwxr-xr-x  user  staff  64 Jan 15 10:30  ..
3  -rw-r--r--  user  staff  1.2K Jan 15 10:32  index.html
4  -rw-r--r--  user  staff  3.4K Jan 15 10:33  app.js
5  -rw-r--r--  user  staff  892B Jan 15 10:31  style.css
\`\`\``;
            } else if (payload.tool === 'web-search') {
                response = `🌐 **Web Search Result**\n\nSearching for: ${payload.message}\n\nFound 3 relevant results:\n\n1. **Documentation** - Latest API docs\n2. **Tutorial** - Step by step guide\n3. **Example** - Working code samples`;
            } else if (payload.tool === 'text-processor') {
                response = `⚙️ **Text Processing Complete**\n\nOperation: ${payload.method}\nInput analyzed successfully.\n\nReady for next command.`;
            } else {
                response = `💬 **General Response**\n\nI received your message: "${payload.message}"\n\nHow can I assist you further?`;
            }
            
            addMessage('ai', response);
            resolve();
        }, 1000 + Math.random() * 1000);
    });
}

// Load Cached Data
function loadCachedData() {
    const cached = localStorage.getItem('nexus_chat_history');
    if (cached) {
        try {
            state.messages = JSON.parse(cached);
            // Restore messages if needed
        } catch (e) {
            console.warn('Failed to load cached history');
        }
    }
}

// Save to Cache
function saveToCache() {
    localStorage.setItem('nexus_chat_history', JSON.stringify(state.messages));
}

// Initialize on DOM Ready
document.addEventListener('DOMContentLoaded', init);
