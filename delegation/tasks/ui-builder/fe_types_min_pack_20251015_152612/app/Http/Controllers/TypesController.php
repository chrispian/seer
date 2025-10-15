<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Types\{TypeRegistry, TypeResolver};

class TypesController
{
    public function __construct(
        protected TypeRegistry $registry,
        protected TypeResolver $resolver,
    ) {}

    public function query(Request $request, string $alias)
    {
        // Optionally load schema for client-side rendering hints
        $schema = $this->registry->get($alias);
        $result = $this->resolver->query($alias, $request->all());
        if ($schema) {
            $result['schema'] = [
                'key' => $schema->key,
                'version' => $schema->version,
                'fields' => array_map(fn($f) => [
                    'name'=>$f->name,'type'=>$f->type,'required'=>$f->required,'unique'=>$f->unique,'options'=>$f->options,'order'=>$f->order
                ], $schema->fields),
                'relations' => array_map(fn($r) => [
                    'name'=>$r->name,'relation'=>$r->relation,'target'=>$r->target,'options'=>$r->options,'order'=>$r->order
                ], $schema->relations),
                'meta' => $schema->meta,
                'options' => $schema->options,
            ];
        }
        return response()->json($result);
    }

    public function show(string $alias, $id)
    {
        $row = $this->resolver->find($alias, $id);
        abort_if(!$row, 404);
        return response()->json(['data'=>$row]);
    }
}
