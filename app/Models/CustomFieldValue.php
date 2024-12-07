<?php

namespace App\Models;

class CustomFieldValue extends Model
{
    protected $fillable = ['page_content_id', 'custom_field_id', 'value'];
    
    protected $casts = [
        'value' => 'json'
    ];

    public function customField()
    {
        return $this->belongsTo(CustomField::class);
    }

    public function pageContent()
    {
        return $this->belongsTo(PageContent::class);
    }
}