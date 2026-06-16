<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CarFilterOptionsService;
use Illuminate\Http\JsonResponse;

class CarFilterController extends Controller
{
    /**
     * GET /api/car-filters
     *
     * Dynamic filter options built from the current set of publicly
     * visible cars. Response is cached (see config/cars.php) and auto-
     * invalidated whenever a Car row is created/updated/deleted.
     */
    public function __invoke(CarFilterOptionsService $service): JsonResponse
    {
        $ttl = (int) config('cars.filter_options_cache_ttl', 600);

        return response()
            ->json(['data' => $service->get()])
            ->setPublic()
            ->setMaxAge($ttl)
            ->setSharedMaxAge($ttl * 2);
    }
}
