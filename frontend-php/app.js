/**
 * Ardi-Agents Frontend Application
 * Modern, minimalist chat interface for agentic workflow orchestration
 */

// ==================== State Management ====================
const AppState = {
    sessionId: null,
    currentGoal: '',
    messages: [],
    agents: [],
    workflows: [],
    sessions: [],
    settings: {
        apiEndpoint: 'http://localhost:8000',
        defaultWorkflow: 'default',
        autoSaveSessions: true
    },
    isLoading: false,
    activeAgent: null
};

// ==================== API Client ====================
const API = {
    async request(endpoint, options = {}) {
        const url = `${AppState.settings.apiEndpoint}${endpoint}`;
        const config = {
            headers: {
                'Content-Type': 'application/json',
            },
            ...options
        };

        try {
            const response = await fetch(url, config);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    },

    // Health check
    async health() {
        return this.request('/health');
    },

    // Agents
    async getAgents() {
        return this.request('/agents');
    },

    async getAgent(name) {
        return this.request(`/agents/${name}`);
    },

    async executeAgent(agentName, input, sessionId = null) {
        return this.request('/execute/agent', {
            method: 'POST',
            body: JSON.stringify({
                agent_name: agentName,
                input_text: input,
                session_id: sessionId
            })
        });
    },

    // Workflows
    async getWorkflows() {
        return this.request('/workflows');
    },

    async executeWorkflow(templateName, initialRequest, sessionId = null) {
        return this.request('/execute/workflow', {
            method: 'POST',
            body: JSON.stringify({
                template_name: templateName,
                initial_request: initialRequest,
                session_id: sessionId
            })
        });
    },

    // Sessions
    async getSessions() {
        return this.request('/sessions');
    },

    async getSession(sessionId) {
        return this.request(`/sessions/${sessionId}`);
    },

    async deleteSession(sessionId) {
        return this.request(`/sessions/${sessionId}`, {
            method: 'DELETE'
        });
    },

    async clearSessions() {
        return this.request('/sessions/clear', {
            method: 'POST'
        });
    }
};

// ==================== UI Components ====================
const UI = {
    // Initialize Lucide icons
    initIcons() {
        lucide.createIcons();
    },

    // Add message to chat
    addMessage(message) {
        const container = document.getElementById('messagesContainer');
        const messageEl = document.createElement('div');
        messageEl.className = 'message-enter max-w-3xl mx-auto';
        
        const isUser = message.role === 'user';
        const timestamp = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        
        messageEl.innerHTML = `
            <div class="flex items-start space-x-4">
                <div class="w-10 h-10 rounded-xl ${isUser ? 'bg-slate-700' : 'bg-gradient-to-br from-accent-500 to-accent-700'} flex items-center justify-center flex-shrink-0">
                    <i data-lucide="${isUser ? 'user' : 'bot'}" class="w-5 h-5 text-white"></i>
                </div>
                <div class="flex-1 space-y-2">
                    <div class="flex items-center space-x-2">
                        <span class="font-semibold text-white">${isUser ? 'You' : 'Ardi Assistant'}</span>
                        <span class="text-xs text-slate-500">${timestamp}</span>
                    </div>
                    <div class="prose prose-invert prose-sm max-w-none">
                        <p class="text-slate-300 leading-relaxed">${this.escapeHtml(message.content)}</p>
                        ${message.metadata ? this.renderMetadata(message.metadata) : ''}
                    </div>
                </div>
            </div>
        `;
        
        container.appendChild(messageEl);
        this.initIcons();
        
        // Scroll to bottom
        container.scrollTop = container.scrollHeight;
        
        return messageEl;
    },

    // Render metadata (workflow steps, agent info, etc.)
    renderMetadata(metadata) {
        if (!metadata) return '';
        
        if (metadata.steps_executed) {
            return `
                <div class="mt-4 space-y-2">
                    <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Workflow Execution</h4>
                    <div class="space-y-1">
                        ${metadata.steps_executed.map(step => `
                            <div class="flex items-center space-x-2 text-sm">
                                <i data-lucide="${step.success ? 'check-circle' : 'x-circle'}" 
                                   class="w-4 h-4 ${step.success ? 'text-success-500' : 'text-error-500'}"></i>
                                <span class="text-slate-300">${step.agent || step.step}</span>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        }
        
        return '';
    },

    // Escape HTML to prevent XSS
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML.replace(/\n/g, '<br>');
    },

    // Show loading state
    showLoading(agentName = null) {
        const container = document.getElementById('messagesContainer');
        const loadingEl = document.createElement('div');
        loadingEl.id = 'loadingIndicator';
        loadingEl.className = 'max-w-3xl mx-auto';
        
        loadingEl.innerHTML = `
            <div class="flex items-start space-x-4">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-accent-500 to-accent-700 flex items-center justify-center flex-shrink-0 shimmer-bg">
                    <i data-lucide="loader-2" class="w-5 h-5 text-white animate-spin"></i>
                </div>
                <div class="flex-1 space-y-2">
                    <div class="flex items-center space-x-2">
                        <span class="font-semibold text-white">Ardi Assistant</span>
                        <span class="text-xs text-slate-500">Processing...</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="flex space-x-1">
                            <div class="w-2 h-2 bg-accent-400 rounded-full animate-bounce" style="animation-delay: 0ms;"></div>
                            <div class="w-2 h-2 bg-accent-400 rounded-full animate-bounce" style="animation-delay: 150ms;"></div>
                            <div class="w-2 h-2 bg-accent-400 rounded-full animate-bounce" style="animation-delay: 300ms;"></div>
                        </div>
                        ${agentName ? `<span class="text-xs text-slate-500">Executing: ${agentName}</span>` : ''}
                    </div>
                </div>
            </div>
        `;
        
        container.appendChild(loadingEl);
        this.initIcons();
        container.scrollTop = container.scrollHeight;
        
        // Show agent status
        if (agentName) {
            document.getElementById('agentStatus').classList.remove('hidden');
            document.getElementById('currentAgent').textContent = agentName;
        }
    },

    // Hide loading state
    hideLoading() {
        const loadingEl = document.getElementById('loadingIndicator');
        if (loadingEl) {
            loadingEl.remove();
        }
        document.getElementById('agentStatus').classList.add('hidden');
    },

    // Update goal display
    updateGoal(goal) {
        const goalEl = document.getElementById('currentGoal');
        goalEl.textContent = goal || 'No active goal. Start a new session to begin.';
        AppState.currentGoal = goal;
    },

    // Add log entry
    addLogEntry(message, type = 'info') {
        const log = document.getElementById('executionLog');
        const entry = document.createElement('div');
        const timestamp = new Date().toLocaleTimeString();
        
        const colors = {
            info: 'text-slate-400',
            success: 'text-success-500',
            error: 'text-error-500',
            warning: 'text-yellow-500'
        };
        
        entry.className = `${colors[type] || colors.info}`;
        entry.textContent = `[${timestamp}] ${message}`;
        
        log.appendChild(entry);
        log.scrollTop = log.scrollHeight;
    },

    // Clear log
    clearLog() {
        document.getElementById('executionLog').innerHTML = '';
    },

    // Populate agents list
    populateAgents(agents) {
        const container = document.getElementById('agentsList');
        
        const agentIcons = {
            language_expert: 'languages',
            user_engagement: 'message-circle',
            analyst: 'chart-bar',
            innovator: 'lightbulb',
            frontend_developer: 'monitor',
            backend_developer: 'server',
            debugger: 'bug',
            task_decomposer: 'list-checks',
            qa_council_planner: 'clipboard-check',
            code_quality_auditor: 'code',
            security_auditor: 'shield',
            performance_auditor: 'gauge',
            ux_logic_auditor: 'eye',
            antagonistic_tester: 'flask-conical',
            justifier: 'scale',
            readme_generator: 'file-text'
        };
        
        container.innerHTML = agents.map(agent => `
            <div class="flex items-center space-x-3 p-2 rounded-lg hover:bg-slate-800/50 transition-colors cursor-pointer group" 
                 data-agent="${agent.name}"
                 title="${agent.description}">
                <div class="w-8 h-8 rounded-lg bg-slate-800 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="${agentIcons[agent.name] || 'cpu'}" 
                       class="w-4 h-4 text-slate-400 group-hover:text-accent-400 transition-colors"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-slate-300 truncate">${this.formatAgentName(agent.name)}</div>
                    <div class="text-xs text-slate-500 truncate">${agent.model.split('/')[0]}</div>
                </div>
            </div>
        `).join('');
        
        this.initIcons();
    },

    // Format agent name for display
    formatAgentName(name) {
        return name
            .split('_')
            .map(word => word.charAt(0).toUpperCase() + word.slice(1))
            .join(' ');
    },

    // Populate sessions list
    populateSessions(sessions) {
        const container = document.getElementById('sessionsList');
        
        if (!sessions || sessions.length === 0) {
            container.innerHTML = `
                <div class="text-center py-6 text-slate-500 text-sm">
                    <i data-lucide="inbox" class="w-6 h-6 mx-auto mb-2 opacity-50"></i>
                    <p>No recent sessions</p>
                </div>
            `;
            this.initIcons();
            return;
        }
        
        container.innerHTML = sessions.map(session => `
            <button class="w-full text-left p-2 rounded-lg hover:bg-slate-800/50 transition-colors group" 
                    data-session="${session.session_id}">
                <div class="text-sm text-slate-300 truncate group-hover:text-white">
                    Session ${session.session_id.slice(0, 8)}...
                </div>
                <div class="text-xs text-slate-500 flex items-center justify-between mt-1">
                    <span>${new Date(session.created_at).toLocaleDateString()}</span>
                    <span class="${session.status === 'completed' ? 'text-success-500' : 'text-accent-400'}">
                        ${session.status}
                    </span>
                </div>
            </button>
        `).join('');
        
        this.initIcons();
    },

    // Update stats
    updateStats(agentCount, activeCount) {
        document.getElementById('totalAgents').textContent = agentCount || 16;
        document.getElementById('activeSession').textContent = activeCount || 0;
    },

    // Toggle settings modal
    toggleSettings(show) {
        const modal = document.getElementById('settingsModal');
        if (show) {
            modal.classList.remove('hidden');
            document.getElementById('apiEndpoint').value = AppState.settings.apiEndpoint;
            document.getElementById('defaultWorkflow').value = AppState.settings.defaultWorkflow;
        } else {
            modal.classList.add('hidden');
        }
    },

    // Show toast notification
    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-4 right-4 px-4 py-3 rounded-lg shadow-lg z-50 animate-slide-up';
        
        const colors = {
            info: 'bg-slate-800 text-white border border-slate-700',
            success: 'bg-success-500 text-white',
            error: 'bg-error-500 text-white'
        };
        
        toast.className += ` ${colors[type] || colors.info}`;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
};

// ==================== Event Handlers ====================
const Handlers = {
    // Handle form submission
    async handleSubmit(e) {
        e.preventDefault();
        
        const input = document.getElementById('messageInput');
        const message = input.value.trim();
        
        if (!message || AppState.isLoading) return;
        
        // Add user message
        UI.addMessage({ role: 'user', content: message });
        input.value = '';
        UI.updateCharCount(0);
        
        // Update goal if first message
        if (!AppState.currentGoal) {
            UI.updateGoal(message);
        }
        
        // Execute workflow
        await this.executeWorkflow(message);
    },

    // Execute workflow
    async executeWorkflow(input) {
        AppState.isLoading = true;
        UI.showLoading();
        UI.addLogEntry(`Starting workflow with input: "${input.slice(0, 50)}..."`, 'info');
        
        try {
            // First, get available workflows
            const workflowsData = await API.getWorkflows();
            const templateName = AppState.settings.defaultWorkflow;
            
            UI.addLogEntry(`Executing workflow: ${templateName}`, 'info');
            
            // Execute the workflow
            const result = await API.executeWorkflow(templateName, input, AppState.sessionId);
            
            UI.hideLoading();
            
            if (result.success) {
                // Add assistant response
                UI.addMessage({
                    role: 'assistant',
                    content: result.final_output,
                    metadata: {
                        steps_executed: result.steps_executed,
                        total_steps: result.total_steps,
                        successful_steps: result.successful_steps
                    }
                });
                
                // Update session
                AppState.sessionId = result.session_id;
                
                UI.addLogEntry(`Workflow completed: ${result.successful_steps}/${result.total_steps} steps successful`, 'success');
                UI.showToast('Workflow completed successfully', 'success');
                
                // Refresh sessions
                await this.loadSessions();
            } else {
                UI.addMessage({
                    role: 'assistant',
                    content: 'The workflow encountered some issues. Please review the execution log for details.'
                });
                UI.addLogEntry('Workflow failed', 'error');
            }
        } catch (error) {
            UI.hideLoading();
            UI.addMessage({
                role: 'assistant',
                content: `Error: ${error.message}. Please check your API connection and try again.`
            });
            UI.addLogEntry(`Error: ${error.message}`, 'error');
            UI.showToast('Failed to execute workflow', 'error');
        } finally {
            AppState.isLoading = false;
        }
    },

    // Load sessions
    async loadSessions() {
        try {
            const result = await API.getSessions();
            AppState.sessions = result.sessions || [];
            UI.populateSessions(AppState.sessions);
            UI.updateStats(null, AppState.sessions.filter(s => s.status === 'in_progress').length);
        } catch (error) {
            console.error('Failed to load sessions:', error);
        }
    },

    // Load agents
    async loadAgents() {
        try {
            const agents = await API.getAgents();
            AppState.agents = agents;
            UI.populateAgents(agents);
            UI.updateStats(agents.length, null);
            UI.addLogEntry(`Loaded ${agents.length} agents`, 'success');
        } catch (error) {
            console.error('Failed to load agents:', error);
            UI.addLogEntry('Failed to load agents - using defaults', 'warning');
        }
    },

    // Handle quick action clicks
    handleQuickAction(action) {
        const input = document.getElementById('messageInput');
        const prompts = {
            'build': 'Build a complete web application with frontend and backend components',
            'research': 'Conduct comprehensive research on ',
            'design': 'Design a modern UI/UX for ',
            'audit': 'Audit and optimize the following code/system: '
        };
        
        input.value = prompts[action] || '';
        input.focus();
    },

    // Handle character count
    updateCharCount(count) {
        document.getElementById('charCount').textContent = `${count} / 2000`;
    },

    // Handle new session
    handleNewSession() {
        AppState.sessionId = null;
        AppState.currentGoal = '';
        UI.updateGoal('');
        
        // Clear messages except welcome
        const container = document.getElementById('messagesContainer');
        container.innerHTML = '';
        
        // Re-add welcome message
        UI.addMessage({
            role: 'assistant',
            content: 'Welcome to Ardi-Agents. I\'m your intelligent workflow orchestrator. Describe your project, research question, or development task, and I\'ll coordinate the appropriate agents to deliver comprehensive results.'
        });
        
        UI.addLogEntry('New session started', 'info');
    },

    // Handle session selection
    async handleSessionSelect(sessionId) {
        try {
            const session = await API.getSession(sessionId);
            AppState.sessionId = sessionId;
            UI.addLogEntry(`Loaded session ${sessionId.slice(0, 8)}...`, 'info');
            UI.showToast('Session loaded', 'success');
        } catch (error) {
            UI.showToast('Failed to load session', 'error');
        }
    }
};

    // Settings Functions
    function openSettings() {
        UI.toggleSettings(true);
    }

    function closeSettingsModal() {
        UI.toggleSettings(false);
    }

    function saveSettings() {
        AppState.settings.apiEndpoint = document.getElementById('apiEndpoint').value;
        AppState.settings.defaultWorkflow = document.getElementById('defaultWorkflow').value;
        
        // Save to localStorage
        localStorage.setItem('ardi-settings', JSON.stringify(AppState.settings));
        
        UI.toggleSettings(false);
        UI.showToast('Settings saved', 'success');
        UI.addLogEntry('Settings updated', 'info');
    }
    
    // Export for global access
    window.openSettings = openSettings;
    window.closeSettingsModal = closeSettingsModal;
    window.saveSettings = saveSettings;

// ==================== Initialization ====================
async function init() {
    // Load settings from localStorage
    const savedSettings = localStorage.getItem('ardi-settings');
    if (savedSettings) {
        AppState.settings = { ...AppState.settings, ...JSON.parse(savedSettings) };
    }
    
    // Initialize icons
    UI.initIcons();
    
    // Setup event listeners
    document.getElementById('chatForm').addEventListener('submit', (e) => Handlers.handleSubmit(e));
    
    document.getElementById('newChatBtn').addEventListener('click', () => Handlers.handleNewSession());
    document.getElementById('settingsBtn').addEventListener('click', openSettings);
    document.getElementById('clearGoalBtn').addEventListener('click', () => {
        UI.updateGoal('');
        UI.addLogEntry('Goal cleared', 'info');
    });
    document.getElementById('clearLogBtn').addEventListener('click', () => UI.clearLog());
    
    // Quick action buttons
    document.querySelectorAll('.quick-action').forEach((btn, index) => {
        const actions = ['build', 'research', 'design', 'audit'];
        btn.addEventListener('click', () => Handlers.handleQuickAction(actions[index]));
    });
    
    // Character count
    const input = document.getElementById('messageInput');
    input.addEventListener('input', (e) => {
        Handlers.updateCharCount(e.target.value.length);
        
        // Auto-resize textarea
        e.target.style.height = 'auto';
        e.target.style.height = Math.min(e.target.scrollHeight, 128) + 'px';
    });
    
    // Keyboard shortcuts
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            document.getElementById('chatForm').dispatchEvent(new Event('submit'));
        }
    });
    
    // Sessions list click handler
    document.getElementById('sessionsList').addEventListener('click', (e) => {
        const sessionBtn = e.target.closest('[data-session]');
        if (sessionBtn) {
            Handlers.handleSessionSelect(sessionBtn.dataset.session);
        }
    });
    
    // Agents list click handler
    document.getElementById('agentsList').addEventListener('click', (e) => {
        const agentEl = e.target.closest('[data-agent]');
        if (agentEl) {
            const agentName = agentEl.dataset.agent;
            UI.addLogEntry(`Selected agent: ${agentName}`, 'info');
        }
    });
    
    // Initial data load
    try {
        await Handlers.loadAgents();
        await Handlers.loadSessions();
        UI.addLogEntry('Application initialized', 'success');
    } catch (error) {
        UI.addLogEntry('Initial load failed - check API connection', 'error');
    }
    
    console.log('Ardi-Agents Frontend initialized');
}

// Start the application
document.addEventListener('DOMContentLoaded', init);
