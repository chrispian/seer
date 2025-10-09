<?php
final class ContextBundle {
  public string $conversation_summary; // ~300-600 chars
  public string $user_message;
  public array  $agent_prefs = [];     // mode, risk tolerance, etc.
  public array  $tool_registry_preview = []; // up to 5 relevant tools
}
