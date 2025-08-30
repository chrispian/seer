<?php

namespace App\Http\Controllers;

use App\Models\Fragment;
use Illuminate\Http\Request;

class FragmentDetailController extends Controller
{
    public function show($id)
    {
        $fragment = Fragment::with(['fragmentTags', 'todo', 'contact', 'link', 'type'])
            ->find($id);
        
        if (!$fragment) {
            return response()->json(['error' => 'Fragment not found'], 404);
        }
        
        // Extract tags from relationships
        $tags = $fragment->fragmentTags->pluck('tag')->toArray();
        
        // Add any tags from the tags JSON field
        if (is_array($fragment->tags)) {
            $tags = array_merge($tags, $fragment->tags);
        }
        
        return response()->json([
            'id' => $fragment->id,
            'message' => $fragment->message,
            'type' => $fragment->type?->value ?? $fragment->getAttribute('type'),
            'created_at' => $fragment->created_at->toISOString(),
            'updated_at' => $fragment->updated_at->toISOString(),
            'tags' => array_unique($tags),
            'metadata' => $fragment->metadata ?: [],
            'state' => $fragment->state ?: [],
            'importance' => $fragment->importance,
            'confidence' => $fragment->confidence,
            'pinned' => $fragment->pinned,
            'relationships' => $fragment->relationships ?: [],
            'source_key' => $fragment->source_key,
            'vault' => $fragment->vault,
            // Include typed object data if available
            'todo' => $fragment->todo ? [
                'title' => $fragment->todo->title,
                'state' => $fragment->todo->state ?: []
            ] : null,
            'contact' => $fragment->contact ? [
                'full_name' => $fragment->contact->full_name,
                'organization' => $fragment->contact->organization
            ] : null,
            'link' => $fragment->link ? [
                'url' => $fragment->link->url,
                'title' => $fragment->link->title,
                'domain' => $fragment->link->domain
            ] : null
        ]);
    }
}