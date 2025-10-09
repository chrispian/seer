# Guardrails Architecture Overview

This document explains the layered approach:
1. **ToolCallMiddleware** wraps all tool calls with policy preflight and structured audit.
2. **PolicyRegistry** centralizes allowlists (tools, commands, paths, domains) and risk scoring.
3. **LimitedShell** runs only whitelisted commands with validated args, caps, and timeouts.
4. **Filesystem Guard** provides a VFS-like FileOps facade and PHP open_basedir restrictions.
5. **Network Guard** wraps HTTP clients and offers optional OS-level egress filters.
6. **Approvals**: high-risk actions pause and surface a diff/intent preview in the UI.
7. **Audit**: JSONL with rolling hash chain for tamper-evidence.
