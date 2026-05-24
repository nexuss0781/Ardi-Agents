/**
 * Ardi-Agents Frontend Application (TypeScript Version)
 * Modern, minimalist chat interface for agentic workflow orchestration
 */

// ==================== Type Definitions ====================

interface Agent {
    name: string;
    description: string;
    model: string;
    provider: string;
    temperature?: number;
}

interface Workflow {
    name: string;
    agents: string[];
    step_count?: number;
}

interface Session {
    session_id: string;
    status: 'in_progress' | 'completed';
    created_at: string;
    updated_at?: string;
    current_step?: number;
    total_steps?: number;
}

interface Message {
    role: 'user' | 'assistant' | 'system';
    content: string;
    metadata?: {
        steps_executed?: Array<{
            step?: string;
            agent?: string;
            success: boolean;
            output_preview?: string;
        }>;
        total_steps?: number;
        successful_steps?: number;
    };
    timestamp?: Date;
}

interface Settings {
    apiEndpoint: string;
    defaultWorkflow: string;
    autoSaveSessions: boolean;
}

interface AppState {
    sessionId: string | null;
    currentGoal: string;
    messages: Message[];
    agents: Agent[];
    workflows: Workflow[];
    sessions: Session[];
    settings: Settings;
    isLoading: boolean;
    activeAgent: string | null;
}

interface APIResponse<T> {
    success: boolean;
    data?: T;
    error?: string;
    message?: string;
}

interface WorkflowResult {
    success: boolean;
    final_output: string;
    steps_executed: Array<{
        step: string;
        agent: string;
        success: boolean;
        output_preview: string;
    }>;
    total_steps: number;
    successful_steps: number;
    failed_steps: number;
    session_id: string;
    execution_time: number;
}

// ==================== State Management ====================

const AppState: AppState = {
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
    async request<T>(endpoint: string, options: RequestInit = {}): Promise<T> {
        const url = `${AppState.settings.apiEndpoint}${endpoint}`;
        const config: RequestInit = {
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
            return await response.json() as T;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    },

    async health(): Promise<APIResponse<unknown>> {
        return this.request('/health');
    },

    async getAgents(): Promise<Agent[]> {
        return this.request('/agents');
    },

    async getAgent(name: string): Promise<Agent & { prompt_preview?: string }> {
        return this.request(`/agents/${name}`);
    },

    async executeAgent(agentName: string, input: string, sessionId: string | null = null): Promise<APIResponse<string>> {
        return this.request('/execute/agent', {
            method: 'POST',
            body: JSON.stringify({
                agent_name: agentName,
                input_text: input,
                session_id: sessionId
            })
        });
    },

    async getWorkflows(): Promise<{ templates: string[]; default_workflow: string; total_templates: number }> {
        return this.request('/workflows');
    },

    async executeWorkflow(templateName: string, initialRequest: string, sessionId: string | null = null): Promise<WorkflowResult> {
        return this.request('/execute/workflow', {
            method: 'POST',
            body: JSON.stringify({
                template_name: templateName,
                initial_request: initialRequest,
                session_id: sessionId
            })
        });
    },

    async getSessions(): Promise<{ sessions: Session[]; total: number }> {
        return this.request('/sessions');
    },

    async getSession(sessionId: string): Promise<Session> {
        return this.request(`/sessions/${sessionId}`);
    },

    async deleteSession(sessionId: string): Promise<APIResponse<null>> {
        return this.request(`/sessions/${sessionId}`, { method: 'DELETE' });
    },

    async clearSessions(): Promise<APIResponse<null>> {
        return this.request('/sessions/clear', { method: 'POST' });
    }
};

// ==================== UI Components ====================

const UI = {
    initIcons(): void {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    },

    addMessage(message: Message): HTMLElement {
        const container = document.getElementById('messagesContainer')!;
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
        container.scrollTop = container.scrollHeight;
        return messageEl;
    },

    renderMetadata(metadata: NonNullable<Message['metadata']>): string {
        if (!metadata.steps_executed) return '';
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
    },

    escapeHtml(text: string): string {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML.replace(/\n/g, '<br>');
    },

    showLoading(agentName: string | null = null): void {
        const container = document.getElementById('messagesContainer')!;
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
        
        if (agentName) {
            document.getElementById('agentStatus')!.classList.remove('hidden');
            document.getElementById('currentAgent')!.textContent = agentName;
        }
    },

    hideLoading(): void {
        const loadingEl = document.getElementById('loadingIndicator');
        if (loadingEl) loadingEl.remove();
        document.getElementById('agentStatus')!.classList.add('hidden');
    },

    updateGoal(goal: string): void {
        const goalEl = document.getElementById('currentGoal')!;
        goalEl.textContent = goal || 'No active goal. Start a new session to begin.';
        AppState.currentGoal = goal;
    },

    addLogEntry(message: string, type: 'info' | 'success' | 'error' | 'warning' = 'info'): void {
        const log = document.getElementById('executionLog')!;
        const entry = document.createElement('div');
        const timestamp = new Date().toLocaleTimeString();
        const colors: Record<string, string> = {
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

    clearLog(): void {
        document.getElementById('executionLog')!.innerHTML = '';
    },

    populateAgents(agents: Agent[]): void {
        const container = document.getElementById('agentsList')!;
        const agentIcons: Record<string, string> = {
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
                 data-agent="${agent.name}" title="${agent.description}">
                <div class="w-8 h-8 rounded-lg bg-slate-800 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="${agentIcons[agent.name] || 'cpu'}" class="w-4 h-4 text-slate-400 group-hover:text-accent-400 transition-colors"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-slate-300 truncate">${this.formatAgentName(agent.name)}</div>
                    <div class="text-xs text-slate-500 truncate">${agent.model.split('/')[0]}</div>
                </div>
            </div>
        `).join('');
        this.initIcons();
    },

    formatAgentName(name: string): string {
        return name.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
    },

    populateSessions(sessions: Session[]): void {
        const container = document.getElementById('sessionsList')!;
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
            <button class="w-full text-left p-2 rounded-lg hover:bg-slate-800/50 transition-colors group" data-session="${session.session_id}">
                <div class="text-sm text-slate-300 truncate group-hover:text-white">Session ${session.session_id.slice(0, 8)}...</div>
                <div class="text-xs text-slate-500 flex items-center justify-between mt-1">
                    <span>${new Date(session.created_at).toLocaleDateString()}</span>
                    <span class="${session.status === 'completed' ? 'text-success-500' : 'text-accent-400'}">${session.status}</span>
                </div>
            </button>
        `).join('');
        this.initIcons();
    },

    updateStats(agentCount: number | null, activeCount: number | null): void {
        document.getElementById('totalAgents')!.textContent = String(agentCount ?? 16);
        document.getElementById('activeSession')!.textContent = String(activeCount ?? 0);
    },

    toggleSettings(show: boolean): void {
        const modal = document.getElementById('settingsModal')!;
        if (show) {
            modal.classList.remove('hidden');
            (document.getElementById('apiEndpoint') as HTMLInputElement).value = AppState.settings.apiEndpoint;
            (document.getElementById('defaultWorkflow') as HTMLSelectElement).value = AppState.settings.defaultWorkflow;
        } else {
            modal.classList.add('hidden');
        }
    },

    showToast(message: string, type: 'info' | 'success' | 'error' = 'info'): void {
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-4 right-4 px-4 py-3 rounded-lg shadow-lg z-50 animate-slide-up';
        const colors: Record<string, string> = {
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

(window as unknown as Record<string, unknown>).UI = UI;

export { AppState, API, UI };
export type { Agent, Workflow, Session, Message, Settings, WorkflowResult };
