Please create task files for sub agents and delegate and manage the following tasks. 

Task 1 & 2: (Must be done first)

Please delegate to appropriate sub agents with the agent profiles proived previously and get the following implemented with our current system. Do not use any existing fragments engine code for this, generate new tables, new code, etc. It can use/integrate with v2 code. We need this for the next phases. If possible, parallel these two. 

Task 1 context pack: fe_types_min_pack_20251015_152612
Task 2 context pack: fe_ui_registry_flags_pack_20251015_152026

Task 3: New tables + suggested changes to other fields to support this (we have most). Adapt to work as needed.

fe_ui_modules * NEW
	id, key (e.g., crm, ttrpg.characters)
	title, description
	manifest_json (declares pages, required datasources, default actions)
	version (semver), hash
	enabled (bool), order
	capabilities (json: ["search","filter","export"])
	permissions (json)

fe_ui_themes * New
	id, key (theme.default, theme.halloween2025)
	design_tokens_json (radius, spacing, colors, typography)
	tailwind_overrides_json
	variants_json (light/dark/accessible)

fe_ui_pages
	id, key (e.g., page.agent.table.modal)
	route (optional; null for modal-only pages)
	title, meta_json (SEO, breadcrumbs)
	layout_tree_json (top-level component tree)
	module_key (fk → fe_ui_modules.key)
	guards_json (auth/roles)
	version, hash, enabled

fe_ui_components
	id, key (component.table.agent, layout.columns.3, pattern.resource.list)
	type (high-level taxonomy: component)
	kind (enum): primitive | composite | pattern | layout
	variant (e.g., standard, dense, modal, drawer)
	schema_json (props/slots contract)
	defaults_json (sane defaults)
	capabilities_json (searchable/sortable/filterable etc.)
	version, hash, enabled

fe_ui_datasources
	id, alias (Agent, Agent.detail, AgentTasks)
	handler (class/command), default_params_json
	capabilities_json (supports: ["list","detail","search","paginate","aggregate"])
	schema_json (shape of data, meta, filters, sorts)
	version, hash, enabled

fe_ui_actions
	id, key (action.command, action.navigate, action.openModal)
	payload_schema_json (params shape)
	policy_json (who can trigger)
	version, hash, enabled

Task 4: Create components to match parity with Shadcn adapted to use our config system.

See: component_list.md

Create a sprint and group these logically in phases to maximize re-use and build speed. Identify building blocks needed and start with those so that building compound/complex components is just config by that point. Start with buttons, fields, icons, etc. Then to layout/containers like divs, rows, grids, lists, tables, etc. 

Once the first front end agent has the primatives built you can use multiple agents to complete assembling the rest. 

Task 5: Review files like ModelDataSourceResolver.php and AgentDataSourceResolver.php and determine if we can make these less hard coded and instead config based? The goal is as much DB driven for the builder as possible. List all v2 files that are currently using hard coded patterns and either fix or discuss with user if needed. I added the AI Model page as a test and I had to create the ModelDataSourceResolver class and that was the only file I had to create so I think we are at a pretty solid start. If we can make this one config based then we are in great shape. 

Deliverables:

- Documentation on how to wire up each component
- Entries in our fe_ui_components table (With kind, etc. set)
- Working component ts files ready to be used
- migrations, classes, and any supporting files needed to make these work. 

