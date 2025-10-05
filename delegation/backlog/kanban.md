
Please review the following and then review the delegation folder. Then, create backlog tasks for the following.

Please:
	- Eliminate duplicates
	- Combine where it makes sense
	- Group logically where possible
	- Create one task/story in the backlog for each unique request
	- Feel free to prefix them to add any grouping that may be useful

As you go, create a KANBAN.md file and let's list/sort our backlog there. Put any notes in this file. Include a list of any items that were eliminated due to being done, duplicate, or addressed by another item. If an item is too vague and needs more detail, put that item in under a heading for Need Context or similar. Don't slow this process down by asking it now, we can do that as part of a backlog review. This is about seeing what work we currently have planned and an index of work that needs to be planned. 

The KANBAN.md file should have Backlog, Todo, Working, Review, Done (or whatever Linear uses). Tasks should be listed in a markdown table. Fields: Task Name, Sprint, Assigned to (User, Agent), Status (if any), Created At, Updated At, Priority, Tags (Anything else?). 

- Update the install flow, make sure it runs any needed seeders (types, configs, etc.). Ask the user if they want to run one of the new AI powered seeders (Create a simple, user focused profile for that - give them a reset/remove demo data option). Overall, we need to make sure that any critical settings/data be created during install. 
- We need to go through the database and clean out anything unused and/or put to use any that we need
- Get ready for NativePHP (Queues, cron, database, all that stuff)
- Types menu seems limited. Only shows 1, no way to add/edit/delete, etc.
- Make sure all fragment displays use the default card/component where it makes sense
- Make the bookmarks clickable in the /bookmark list command as well as the bookmark widget.
- Right column, the widgets are on cards. Should only have bottom borders, makes it look to cluttered.
- Test out other fonts (system, small fonts)
- Tweak the left sidebar, make that display more compact
- For the Agents, the system will assign a designation in the style of RD-DT2, 3CPO, NO-L2, C1-13. These should be unique. This is mostly for fun. 
- Make agent picker on chat dynamic (should set session, just like model)
- Make mode picker on chat dynamic (should set session, just like model)
- New commands aren't reflected in the slash command auto complete
- Review all the code and look for settings that need to be migrated to our new settings panel
	- What models to use for the built in stuff, like the classifier, default chat llm, the command router, etc.
- Are there any settings on the panel that we need to add behavior for
- Add a small tab/menu item on a fragment to slide out it's meta drawer. The meta drawer will be context aware. If you are on the main screen and open it it will let you see and quick edit the tags, project, all that. If you are on the inbox, you may have additional actions you can perform like create filter/rule, archive, approve, etc.
- Setup daily notes. Combination of a template + scheduled task + transclusion
- Show context (estimated) used
	- All user view and edit (selected parts)
		- View should show context and why/how this context ended up in there. 
	- Compact command
	- Clear context command
- Update readme
	- Purpose
	- Use cases
	- Core features/concepts
	- How to install
- Get the NativePHP install version setup
- Actions (Flows) with merge tags, etc.
- Artifact setup
	- Need a config so users can set allowed folders. All should be opt in for sandboxing purposes
	- Tasks should have a path config option too, that way they can be properly sandboxed if not already supported
- Auto context management / compacting
- Improve /help - add sorting/filtering as well as copy. Make sure it can be kb navigated and execute commands that way too.
- Test & Improve all the command displays
- Edit Fragment
- Test image gen - does it work? Can I download it, etc.
- Fix System Settings (and add more - like default model, etc.)
- Fix search
- Make sure todo/contact are rock solid
- Natural language command processor
- Figure out the issue with my Anthropic API
- Test various models
- See what other local models I can use
- Import from Obsidian/Markdown/Text/csv
- Import ChatGPT
- Exports (csv, sql, markdown, text, pdf)
- Basic file/artifact support
- Image index/tagging/ocr etc support (for things like pin boards)
- Make sure UX auto focuses where I want 
- Minimal toast response bubble beside the AI icon, make it look like the AI is typing something there.
- Blocks for the markdown editor for the "edit page" view
- Tasks to review existing systems for security, speed, bugs, improvements, make sure it's working, make sure the views are correct, etc.
- Make sure slash commands have an enabled toggle
- Need a template manager/editor for users (Markdown)
- Types don't appear to be imported/configured after the recent database refresh.
  - There should be at least 1 type, Todos. 
  - Let's make sure these types get seeded properly when we reset the db/install the app
  - Review the menu item (triggered by /types) and make sure we can CRUD the types properly
- We have a vault rules table, let's make sure it's used and that we have UI for managing these
- We should have rules for tagging, types, etc. too that follow this same basic setup (maybe these are just all routing rules and the rule can have an "applies_to" [Vault, Type, Tags, Category, etc])
- Add a ... indicator that something is happening in the chat
- Add edit fragment (only on user fragments)
  > Use the edited field we created
  > Should it re-run through enrichment
  > Should it create a new clone with modified meta based on the changes?
  > What's the best way to reflect changes in terms of search, if we don't re-run it through the pipeline for example? 
- We need a proper settings panel
  - Add UI/Settings for everthing in .env where appropriate
  - Model config
  - Slash command
  - Routing config
  - User Preferences
  - Agent settings/preferences
- Finish the user widget/dialog
- Keyboard shortcut
  > New Chat
  > Quick add fragment
  > Settings
  > Bookmarks
  > Todo
  > Help
- Wire up search everywhere
  Let's wire up search in the ribbon. Right now we have a slash command for /search {term}. Let's re-use that modal for the moment. When we click the search icon it should pop up a small model with a search input as the only focus. When the search term is entered, the modal should grow and then include fragment cards as the display. Search should then live filter search results if the user changes the term(s). We should include a sort/filter option. For now, just include a few basic filters (Type, Vault, Project) and sort by date, relevance options for now. 

  If you look in the old filament/livewire based interface we had a search input in the main chat area's header. We also had search wired to cmd + k. The cmd+k version is closer to what we want to do here. Look at Shadcn's components and let's use the appropriate one for the search. 
- How to allow the user to schedule recurring tasks (eg: import RSS, api ingest, ai tasks, etc.)
- Implement system prompts, need to design this system. Should be exposed to users and allow them to tweak/override as needed. Make sure to implement common sense safety for the user.
- We should define rules for:
	- Model selection (Vault < Project < Chat < User < Task Specific (defaults configured by user, but system decides these)
- System settings
  - Project
	  Users should be able to set a default vault, model, tags, type, etc.
  - Tags
  - Type
	  Users should be able to set a default model, tag, etc.
  - Vault
	  - Users should be able to set default chat agent, model, tags, project etc.  
- We need a category manager
- We need a tag manager (we may have some code for this?)
- Review the database and make sure all models/tables confirm to laravel conventions.
- Users can change vaults anytime they want with the slash commands or in the ui. We need to make sure this change is reflected in the vault assignment spots in the app and that it properly updates the session.
	1. In the user fragment it should know what the current session vault is set to and use that, or default if one is not set.	
	2. Same for the agent response. It should make sure to pass into the agent the correct session level overrides or defaults. 
- Import specific notes from Obsidian (or folders)
- Get/Send emails
- Schedule recurring actions (like make me a newsletter)
- Daily/weekly/monthly Note system (see obsidian)
- Hook into calendar
- Hook into todoist
- Hook into Contacts to import/link
- Hook into Hardcover
- Hook into LinkedIn
- Hook into RSS
- Hook into Readwise
- Import my ChatGPT chats
  > Have it parse them and look for obvious patterns that match my intent with Fragments Engine so it can parse them properly. Break them into message/responses and run them through the system like normal, just old time stamps. 
- Export fragments to files
- AI generate files for the user to download (Provide stream link to user)
- AI generate files and write them to disk using system level tools
- Execute commands for agentic work
- Natural language command routing
- Artifact Manager
- Go through all the tests and make sure they are pest
- Add browser testing tests via pest where possible
- Feature: Automatic sensitive/PII data scrubbing
- Update Vault config to support a system label. Vaults are very obsidian focused, but they could just as easily be called Workspaces. This should be setting the user can change. For now, let's just give them the option to call it Vault or Workspace (internally, we can refer to it as vault still)
- Implement a collection type. Collections are like, my book collection, records, posters, links, documents, photos. I think perhaps a collection should be able to collect multiple types (eg, for a moodboard style, a user may want to collect music, pics, text for a book moodboard - the collection is a topic, not a type in this case). It may be that a Collection stays type specific and we could create a new type called Board so users can create those mixed styles - yes, let's do that.
- Run an exhaustive code review
	- Make sure everything is secure
	- Reduce complexity
	- Make everything possible composable/fluent/action/pipelines, etc 
	- Consistency
	- Remove dead code/logic
	- This should include a review of the database/relationships/data models, etc.
	- Use common tools for this too (phpstan, rector, pint, etc.)
	- Create detailed context packs for each major system with main files/entry points, overview, and how to find/get more context, etc.

