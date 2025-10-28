You are a senior fullstack developer using Laravel, React and Tailwind. 

Your task: 

1. Review the @hollis-labs/ui-builder packaged in the vendor folder to learn how it works. 

2. Review how it is implemented in /pages/page.agent.table.modal and /pages/page.model.table.modal views. They are basic examples using the ui-builder package.

3. Create a plan to create a UI for creating UI's with the UI-Builder. The goal is to create a similar ui component that powers the above routes that let's us create and manage new routes with their own components. 

Deliverables:
- A working UI component that lets users create, edit, and use the UI builder with all current features.
- It should create the appropriate entries in the fe_* tables and json cache files. 
- There should be screens and actions to make it easy to create and edit ui components. The ui should always keep the user from having to load new screens when possible. Prefer inline actions, modals, etc.

Contraints:
- You should not need to modify any existing project code. You should be able to use the @hollis-labs/ui-builder without writing any code to create the new ui-builder-ui. It's 100% configuration based. If you run into any situation where you think you need to write new code you *must* collaborate with the user to explain the need for new code and decide together if we want to proceed or work around it. Part of the point of this task is use the ui-builder and identify any gaps if we run into any. 
- Nothing should be hardcoded. If you need to hardcode something you *must* collaborate with the user and explain. This system is config based and nothing should be hard coded. 
- If you have to do anything other than insert data into the database you *must* collaborate with the user and decide why and if it's truly necessary.
- You may create notes/documents in the modules/ui-builder-ui folder. You may create temporary commands or seeders to add config data to the database. 
- You must create a TASKS.md file in the modules/ui-builder-ui to track progress.
- You must create a CONTEXT.md file to store documentation/notes on how you build this and any discoveries made during the process. 
 
MVP: A working UI Builder UI that allows the user to add new components to the UI Builder UI as well as create new components. The MVP should focus on everything needed for page level components. Creating modules, themes and other advanced features will be added as we go. 

A new branch has already been initilized for this task.


