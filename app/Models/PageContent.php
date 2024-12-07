<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PageContent extends Model
{
    protected $fillable = ['page_id', 'block_name', 'language'];

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function customFields()
    {
        return $this->hasMany(CustomField::class);
    }
} 