<?php

namespace App\Http\Resources\OtoBanner;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OtoBannerAnalyticsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'banner_id' => $this->resource['banner_id'],
            'banner_name' => $this->resource['banner_name'],

            'views' => $this->resource['views_count'],
            'submissions' => $this->resource['submissions_count'],
            'conversion_rate' => $this->resource['conversion_rate'],

            'orders_count' => $this->resource['orders_count'],
            'total_orders_amount' => $this->resource['total_orders_amount'],

            'conversion_percentage' => $this->when(
                isset($this->resource['conversion_percentage']),
                $this->resource['conversion_percentage']
            ),
        ];
    }
}
