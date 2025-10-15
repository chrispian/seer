# PM Orchestrator Pack (Fragments Engine – UI Builder v2)

This bundle provides:
- **PM_Orchestrator.md** (profile prompt)
- **ADR_v2_TEMPLATE.md** (decision log template)
- **/work_orders/** (copy/paste work orders)
- **/telemetry/status_report_stub.json** (status payload shape)

## How to Use
1. Give **PM_Orchestrator.md** to your PM agent as the system/role prompt.
2. Issue relevant work orders from **/work_orders/** to FE/BE sub-agents in parallel.
3. Require **ADR v2** for any interface or behavior change using **ADR_v2_TEMPLATE.md**.
4. Collect daily status bundles under `/telemetry/YYYY-MM-DD.json`.
