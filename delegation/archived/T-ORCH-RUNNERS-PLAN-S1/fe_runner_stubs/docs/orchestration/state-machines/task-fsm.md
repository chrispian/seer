# Task FSM

```
boot -> read_task -> gather_context -> plan -> execute[*] -> summary -> admin -> shutdown
```

Each node emits a `StepResult`. `execute[*]` may perform substeps but still respects budgets and quantum yielding.
