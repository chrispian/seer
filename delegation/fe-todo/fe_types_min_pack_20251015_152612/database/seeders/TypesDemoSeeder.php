<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{FeType, FeTypeField, FeTypeRelation};

class TypesDemoSeeder extends Seeder
{
    public function run(): void
    {
        $invoice = FeType::updateOrCreate(
            ['key' => 'Invoice'],
            ['version' => '1.0.0', 'meta_json' => ['capabilities'=>['search','filter','sort']], 'options_json' => ['materialize'=>false]]
        );

        $fields = [
            ['name'=>'number', 'type'=>'string', 'required'=>true, 'unique'=>true, 'order'=>1, 'options_json'=>['label'=>'Invoice #']],
            ['name'=>'amount', 'type'=>'decimal:12,2', 'required'=>true, 'unique'=>false, 'order'=>2],
            ['name'=>'status', 'type'=>'enum:pending,paid,void', 'required'=>true, 'unique'=>false, 'order'=>3, 'options_json'=>['default'=>'pending']],
            ['name'=>'issued_at', 'type'=>'datetime', 'required'=>false, 'unique'=>false, 'order'=>4],
        ];

        foreach ($fields as $f) {
            FeTypeField::updateOrCreate(
                ['fe_type_id'=>$invoice->id, 'name'=>$f['name']],
                $f
            );
        }

        FeTypeRelation::updateOrCreate(
            ['fe_type_id'=>$invoice->id, 'name'=>'company'],
            ['relation'=>'belongsTo','target'=>'Company','order'=>1,'options_json'=>['fk'=>'company_id']]
        );
        FeTypeRelation::updateOrCreate(
            ['fe_type_id'=>$invoice->id, 'name'=>'lines'],
            ['relation'=>'hasMany','target'=>'InvoiceLine','order'=>2,'options_json'=>['fk'=>'invoice_id']]
        );
    }
}
