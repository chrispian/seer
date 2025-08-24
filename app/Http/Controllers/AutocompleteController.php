<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Fragment;
use App\Services\CommandRegistry;
use Illuminate\Http\Request;

class AutocompleteController extends Controller
{
    public function commands(Request $request)
    {
        $query = $request->get('q', '');
        $commands = CommandRegistry::all();
        
        if (!empty($query)) {
            $commands = array_filter($commands, function($command) use ($query) {
                return str_contains(strtolower($command), strtolower($query));
            });
        }
        
        $results = array_map(function($command) {
            return [
                'type' => 'command',
                'value' => $command,
                'display' => "/{$command}",
                'description' => $this->getCommandDescription($command)
            ];
        }, $commands);
        
        return response()->json(['results' => array_values($results)]);
    }
    
    public function contacts(Request $request)
    {
        $query = $request->get('q', '');
        $limit = min((int) $request->get('limit', 10), 50);
        
        $contacts = Contact::query()
            ->with('fragment:id,message')
            ->when($query, function($q) use ($query) {
                $q->search($query);
            })
            ->limit($limit)
            ->get();
        
        $results = $contacts->map(function($contact) {
            return [
                'type' => 'contact',
                'value' => $contact->display_name,
                'display' => "@{$contact->display_name}",
                'description' => $contact->primary_email ? "({$contact->primary_email})" : null,
                'organization' => $contact->organization,
                'fragment_id' => $contact->fragment_id
            ];
        });
        
        return response()->json(['results' => $results]);
    }
    
    public function fragments(Request $request)
    {
        $query = $request->get('q', '');
        $limit = min((int) $request->get('limit', 10), 50);
        
        $fragments = Fragment::query()
            ->forAutocomplete($limit)
            ->when($query, function($q) use ($query) {
                $q->searchContent($query);
            })
            ->get();
        
        $results = $fragments->map(function($fragment) {
            $title = $fragment->title;
            $preview = $fragment->preview;
            
            return [
                'type' => 'fragment',
                'value' => $title,
                'display' => "[[{$title}]]",
                'description' => $preview !== $title ? $preview : null,
                'fragment_type' => $fragment->type?->value,
                'fragment_id' => $fragment->id,
                'created_at' => $fragment->created_at->format('M j, Y')
            ];
        });
        
        return response()->json(['results' => $results]);
    }
    
    private function getCommandDescription(string $command): string
    {
        $descriptions = [
            'session' => 'Start or manage chat sessions',
            'recall' => 'Recall saved fragments and todos',
            'bookmark' => 'Save and manage bookmarks',
            'help' => 'Show available commands',
            'clear' => 'Clear chat history',
            'frag' => 'Create fragment from text',
            'chaos' => 'Process complex multi-part content'
        ];
        
        return $descriptions[$command] ?? 'Execute command';
    }
}