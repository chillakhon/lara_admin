<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Imageable extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $guarded = false;

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }
}
