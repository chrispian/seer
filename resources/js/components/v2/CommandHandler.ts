// Command Handler for v2 UI System
// Listens for command:execute events and handles them appropriately

export class CommandHandler {
  private handlers: Map<string, (payload: any) => void> = new Map();

  constructor() {
    this.setupDefaultHandlers();
    this.listen();
  }

  private setupDefaultHandlers() {
    // Handler for /orch-agent command
    this.register('/orch-agent', (payload) => {
      console.log('Opening agent details for:', payload.name);
      // TODO: Open agent detail modal or navigate to agent page
      // For now, just show an alert
      alert(`Agent Details:\n\nName: ${payload.name}\nID: ${payload.id}\nStatus: ${payload.status}\nDesignation: ${payload.designation}`);
    });

    // Handler for /orch-agent-new command  
    this.register('/orch-agent-new', () => {
      console.log('Opening new agent form');
      // TODO: Open new agent modal
      alert('New Agent form would open here');
    });

    // Handler for /model-details command
    this.register('/model-details', (payload) => {
      console.log('Opening model details for:', payload.name);
      alert(`Model Details:\n\nName: ${payload.name}\nID: ${payload.id}\nProvider: ${payload.provider}`);
    });
  }

  register(command: string, handler: (payload: any) => void) {
    this.handlers.set(command, handler);
  }

  private listen() {
    window.addEventListener('command:execute', ((event: CustomEvent) => {
      const { command, payload } = event.detail;
      console.log('CommandHandler received:', command, payload);
      
      const handler = this.handlers.get(command);
      if (handler) {
        handler(payload);
      } else {
        console.warn(`No handler registered for command: ${command}`);
      }
    }) as EventListener);
  }
}

// Create and export a singleton instance
export const commandHandler = new CommandHandler();