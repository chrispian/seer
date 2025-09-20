<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    public function fragments()
    {
        return $this->hasMany(Fragment::class);
    }
}
