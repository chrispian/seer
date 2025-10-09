<?php
final class OutcomeSummary {
  public string $short_summary = ''; // <= 120 words
  /** @var string[] */
  public array $key_facts = [];
  /** @var array<int,string> */
  public array $links = [];
  public string $confidence = 'medium';
  public string $log_pointer = '';
}
