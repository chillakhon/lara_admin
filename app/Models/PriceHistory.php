<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceHistory extends Model
{
    protected $table = "price_history";

    protected $guarded = ["id"];

    public function item()
    {
        return $this->morphTo('item', 'item_type', 'item_id');
    }
}
