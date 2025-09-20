<?php

namespace App\Http\Controllers;

use App\Models\Contact;

class ContactController extends Controller
{
    public function show($id)
    {
        $contact = Contact::with('fragment:id,message,created_at,type')
            ->where('fragment_id', $id)
            ->first();

        if (! $contact) {
            return response()->json(['error' => 'Contact not found'], 404);
        }

        return response()->json([
            'fragment_id' => $contact->fragment_id,
            'full_name' => $contact->full_name,
            'emails' => $contact->emails ?: [],
            'phones' => $contact->phones ?: [],
            'organization' => $contact->organization,
            'state' => $contact->state ?: [],
            'fragment' => [
                'id' => $contact->fragment->id,
                'message' => $contact->fragment->message,
                'type' => $contact->fragment->type?->value,
                'created_at' => $contact->fragment->created_at->toISOString(),
            ],
        ]);
    }
}
