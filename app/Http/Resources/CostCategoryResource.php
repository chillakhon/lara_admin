<?php


namespace App\Http\Resources;

use App\Models\CostCategory;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="CostCategoryResource",
 *     type="object",
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         example="Category Name"
 *     ),
 *     @OA\Property(
 *         property="type",
 *         type="string",
 *         example="Type"
 *     ),
 *     @OA\Property(
 *         property="is_active",
 *         type="boolean",
 *         example=true
 *     )
 * )
 */
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
