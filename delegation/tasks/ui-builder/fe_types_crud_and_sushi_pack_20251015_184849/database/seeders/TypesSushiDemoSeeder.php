<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{FeType, FeTypeField};
use App\Models\Sushi\Movies;

class TypesSushiDemoSeeder extends Seeder
{
    public function run(): void
    {
        $movie = FeType::updateOrCreate(
            ['key' => 'Movie'],
            ['version' => '1.0.0', 'meta_json' => ['capabilities'=>['search','sort']], 'options_json' => ['adapter'=>'sushi','model'=>Movies::class]]
        );

        $fields = [
            ['name'=>'title', 'type'=>'string', 'required'=>true, 'order'=>1],
            ['name'=>'year', 'type'=>'decimal:4,0', 'required'=>false, 'order'=>2],
            ['name'=>'rating', 'type'=>'decimal:2,1', 'required'=>false, 'order'=>3],
        ];

        foreach ($fields as $f) {
            FeTypeField::updateOrCreate(
                ['fe_type_id'=>$movie->id, 'name'=>$f['name']],
                $f
            );
        }
    }
}
