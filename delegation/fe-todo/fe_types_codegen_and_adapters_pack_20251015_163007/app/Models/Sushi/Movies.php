<?php

namespace App\Models\Sushi;

use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class Movies extends Model
{
    use Sushi;

    protected $rows = [
        ['id'=>1,'title'=>'The Matrix','year'=>1999,'rating'=>8.7],
        ['id'=>2,'title'=>'Arrival','year'=>2016,'rating'=>8.0],
        ['id'=>3,'title'=>'Dune','year'=>2021,'rating'=>8.3],
    ];

    protected $casts = [
        'year' => 'integer',
        'rating' => 'decimal:1',
    ];
}
