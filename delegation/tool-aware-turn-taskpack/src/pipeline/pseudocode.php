<?php
// Pseudocode / outline for the Tool‑Aware Turn MVP

$ctx = ContextBroker::assemble($sessionId, $userMessage); // ContextBundle

$routerJson = Llm::complete('system_orchestrator', file_get_contents(__DIR__.'/../prompts/router_decision.md'), [
  'conversation_summary' => $ctx->conversation_summary,
  'user_message' => $ctx->user_message,
]);

$decision = Json::strictDecode($routerJson); // retry once on failure

if (!$decision['needs_tools']) {
  return FinalComposer::reply($ctx, null, null);
}

$registrySlice = ToolRegistry::sliceForGoal($decision['high_level_goal']); // gmail.*, calendar.* etc

$candidatesJson = Llm::complete('system_orchestrator', file_get_contents(__DIR__.'/../prompts/tool_candidates.md'), [
  'high_level_goal' => $decision['high_level_goal'],
  'tools' => $registrySlice,
]);

$plan = Json::validateAgainstSchema($candidatesJson, ToolPlan::class);

$plan = PermissionGate::filterAllowList($plan, $userId);
$plan = ArgResolver::fillFromContext($plan, $ctx);

$trace = new ExecutionTrace();
$trace->correlation_id = Uuid::v4();

foreach ($plan->plan_steps as $step) {
  $start = microtime(true);
  $res = McpClient::call($step['tool_id'], $step['args']);
  $toolResult = new ToolResult();
  $toolResult->tool_id = $step['tool_id'];
  $toolResult->args = $step['args'];
  $toolResult->result = $res['data'] ?? null;
  $toolResult->error = $res['error'] ?? null;
  $toolResult->elapsed_ms = (microtime(true)-$start)*1000;
  $trace->steps[] = $toolResult;
}

$summaryJson = Llm::complete('system_orchestrator', file_get_contents(__DIR__.'/../prompts/outcome_summary.md'), [
  'results' => Redactor::results($trace->steps),
]);

$summary = Json::strictDecode($summaryJson);

Logger::audit([
  'correlation_id' => $trace->correlation_id,
  'ctx' => Redactor::context($ctx),
  'decision' => $decision,
  'plan' => $plan,
  'trace' => Redactor::trace($trace),
  'summary' => $summary,
]);

return FinalComposer::reply($ctx, $summary, $trace->correlation_id);
