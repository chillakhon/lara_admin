<?php

namespace App\Models\Tag;

use App\Models\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $fillable = [
        'name',
        'color',
    ];

    protected $hidden = ['pivot'];


    /**
     * Tags attached to this client
     * @return BelongsToMany
     */

    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, 'client_tag', 'tag_id', 'client_id');
    }
}
