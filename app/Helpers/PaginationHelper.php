<?php

namespace App\Helpers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PaginationHelper
{
    public static function format(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'total'        => $paginator->total(),
            'per_page'     => $paginator->perPage(),
        ];
    }
}
