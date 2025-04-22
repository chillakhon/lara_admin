<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionBatchOutputProduct extends Model
{
    protected $table = 'production_batches_output_products';

    protected $guarded = ['id'];

    public function output()
    {
        return $this->morphTo('output', 'output_type', 'output_id');
    }
}
