<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionBatchMaterial extends Model
{
    protected $table = "production_batches_materials";

    protected $guarded = ['id'];

    public $timestamps = false;


    public function material()
    {
        return $this->morphTo('component', 'material_type', 'material_id');
    }
}
