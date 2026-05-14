<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Image extends Model
{
    use HasFactory;

    public $timestamps = false;

    // protected $fillable = [
    //     'path',
    //     'url',
    //     'is_main',
    //     'order'
    // ];

    protected $guarded = ['id'];

    public function item()
    {
        return $this->morphTo('item', 'item_type', 'item_id');
    }

}
