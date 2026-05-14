<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieldGroup extends Model
{
    protected $fillable = ['name'];

    public function fields()
    {
        return $this->hasMany(Field::class);
    }

    public function contentBlocks()
    {
        return $this->hasMany(ContentBlock::class);
    }
}
