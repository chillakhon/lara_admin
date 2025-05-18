<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discountable extends Model
{
    use HasFactory;

    protected $table = "discountables";
    protected $guarded = ['id'];

    public function discount()
{
    return $this->belongsTo(Discount::class);
}
}
