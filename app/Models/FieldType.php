<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieldType extends Model
{
    protected $fillable = ['name', 'type', 'settings'];

    protected $casts = [
        'settings' => 'json'
    ];

    public function fields()
    {
        return $this->hasMany(Field::class);
    }
}
