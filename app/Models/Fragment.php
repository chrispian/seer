<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;

    class Fragment extends Model
    {
        protected $guarded = [];

        protected $casts = [
            'tags' => 'array',
            'relationships' => 'array',
        ];

        public function category()
        {
            return $this->belongsTo(Category::class);
        }

    }

