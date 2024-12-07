<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadType extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'required_fields',
        'is_active'
    ];

    protected $casts = [
        'required_fields' => 'array',
        'is_active' => 'boolean'
    ];

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }
} 