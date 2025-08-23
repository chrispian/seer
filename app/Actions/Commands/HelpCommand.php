<?php

namespace App\Actions\Commands;

use App\Contracts\HandlesCommand;
use App\DTOs\CommandRequest;
use App\DTOs\CommandResponse;

class HelpCommand implements HandlesCommand
{
    public function handle(CommandRequest $command): CommandResponse
    {
        return new CommandResponse(

            message:  <<<MARKDOWN
### Commands Cheat Sheet

Here are the commands you can use in the chat:

#### 🔍 Recall & Memory
- `/recall type:todo limit:5` – Recall recent fragments by type
- `/recall session {hint}` – Recall all fragments from a saved session (coming soon 🚀)
- `/bookmark` – Bookmark the most recent fragment (or all from chaos)
- `/bookmark list` – List saved bookmarks
- `/bookmark show {hint}` – Replay a bookmark in the chat
- `/bookmark forget {hint}` – Delete a saved bookmark

#### 🔁 Fragments
- `/frag This is something worth remembering` – Log a fragment
- `/chaos This contains multiple items. Do X. Also do Y.` – Split and log a chaos fragment

#### 🧠 Sessions
- `/session vault:work type:meeting Project Sync #weekly` – Start a scoped session for all new fragments
- `/session show` – Display current active session details
- `/session end` – End the current session and return to normal logging

Sessions automatically attach vault, type, tags, and a session key to all fragments you create while active.

Example:
`/session vault:writing type:seeds Ideas for Short Stories #shorts`

✅ All fragments during this session will be stored with:
- Vault: `writing`
- Type: `seeds`
- Tags: `shorts`
- Identifier: `Ideas for Short Stories`
- Session grouping key for easy recall later.

#### 🧹 Chat Tools
- `/clear` – Clear the current chat view
- `/help` – Show this help message

---
All fragments are stored and processed. Bookmarks and sessions give you quick access to curated memories and grouped ideas.
MARKDOWN,
            type: 'system',
            shouldResetChat: true,

        );
    }
}
