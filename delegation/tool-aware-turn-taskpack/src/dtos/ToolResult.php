<?php
final class ToolResult {
  public string $tool_id;
  public array  $args = [];
  public $result = null;       // raw payload
  public ?string $error = null;
  public float  $elapsed_ms = 0.0;
}
