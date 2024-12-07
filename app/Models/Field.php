<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Field extends Model
{
    protected $fillable = [
        'field_type_id',
        'field_group_id',
        'name',
        'key',
        'required',
        'settings'
    ];

    protected $casts = [
        'required' => 'boolean',
        'settings' => 'json'
    ];

    public function fieldType()
    {
        return $this->belongsTo(FieldType::class);
    }

    public function fieldGroup()
    {
        return $this->belongsTo(FieldGroup::class);
    }
}
