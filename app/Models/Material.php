<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = ['title', 'unit_of_measurement', 'cost_per_unit'];

    public function conversions()
    {
        return $this->hasMany(MaterialConversion::class);
    }

    public function productComponents()
    {
        return $this->hasMany(ProductComponent::class);
    }
}
