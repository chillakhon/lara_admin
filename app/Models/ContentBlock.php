<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentBlock extends Model
{
    protected $fillable = [
        'name',
        'key',
        'field_group_id',
        'description'
    ];

    public function fieldGroup()
    {
        return $this->belongsTo(FieldGroup::class);
    }

    public function pageContents()
    {
        return $this->hasMany(PageContent::class);
    }
}
