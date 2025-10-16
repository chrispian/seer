<?php

namespace App\Models\Sushi;

use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class Countries extends Model
{
    use Sushi;

    protected $rows = [
        ['code'=>'US','name'=>'United States'],
        ['code'=>'CA','name'=>'Canada'],
        ['code'=>'JP','name'=>'Japan'],
    ];

    public $incrementing = false;
    protected $primaryKey = 'code';
}
