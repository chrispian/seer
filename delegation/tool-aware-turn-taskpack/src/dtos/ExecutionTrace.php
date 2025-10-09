<?php
final class ExecutionTrace {
  public string $correlation_id;
  /** @var ToolResult[] */
  public array $steps = [];
}
