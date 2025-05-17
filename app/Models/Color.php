<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Color extends Model
{
    use HasFactory;

    protected $table = "colors";

    protected $guarded = [
        "id"
    ];

    protected $hidden = ['pivot'];


    public function products()
    {
        return $this->morphedByMany(Product::class, 'colorable');
    }

    public function variants()
    {
        return $this->morphedByMany(ProductVariant::class, 'colorable');
    }
}
