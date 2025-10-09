<?php
final class ToolPlan {
  /** @var string[] */
  public array $selected_tool_ids = [];
  /** @var array<int,array{tool_id:string, args:array, why:string}> */
  public array $plan_steps = [];
  /** @var string[] */
  public array $inputs_needed = [];
}
