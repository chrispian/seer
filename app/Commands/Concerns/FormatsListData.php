<?php

namespace App\Commands\Concerns;

use Illuminate\Database\Eloquent\Model;

trait FormatsListData
{
    protected function formatListItem(Model $item, ?array $fields = null): array
    {
        $fields = $fields ?? $this->type?->hot_fields ?? [];
        $data = ['id' => $item->id];
        
        if (!empty($fields)) {
            foreach ($fields as $field) {
                if (isset($item->$field)) {
                    $data[$field] = $item->$field;
                }
            }
        } else {
            $data = array_merge($data, $item->only([
                'name', 'title', 'description', 'status', 'type'
            ]));
        }
        
        if (isset($item->created_at)) {
            $data['created_at'] = $item->created_at->toISOString();
            $data['created_human'] = $item->created_at->diffForHumans();
        }
        
        if (isset($item->updated_at)) {
            $data['updated_at'] = $item->updated_at->toISOString();
            $data['updated_human'] = $item->updated_at->diffForHumans();
        }
        
        return $data;
    }
    
    protected function formatFragmentItem(\App\Models\Fragment $fragment): array
    {
        return [
            'id' => $fragment->id,
            'title' => $fragment->title,
            'message' => $fragment->message,
            'type' => $fragment->type,
            'metadata' => $fragment->metadata,
            'created_at' => $fragment->created_at?->toISOString(),
            'updated_at' => $fragment->updated_at?->toISOString(),
            'created_human' => $fragment->created_at?->diffForHumans(),
            'preview' => \Illuminate\Support\Str::limit($fragment->message ?? '', 150),
        ];
    }
}
