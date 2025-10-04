# Architecture Overview
- **Tool Facade Layer:** JSON Schemas (inputs/outputs), semantic versions.
- **Execution Layer:** Laravel Services/Actions that implement contracts.
- **Exposure Layer:** Prism functions (local), optional REST/MCP.
- **Policy Layer:** Capability scopes, rate limits, quotas, approvals.
