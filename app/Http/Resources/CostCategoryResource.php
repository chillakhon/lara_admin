<?php


namespace App\Http\Resources;

use App\Models\CostCategory;
use Illuminate\Http\Resources\Json\JsonResource;

class CostCategoryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'type_name' => CostCategory::getTypes()[$this->type] ?? $this->type,
            'description' => $this->description,
            'is_active' => $this->is_active
        ];
    }
}
