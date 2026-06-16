<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CarIndexRequest;
use App\Http\Resources\Api\CarDetailResource;
use App\Http\Resources\Api\CarResource;
use App\Models\Car;
use App\Services\CarFilterService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CarController extends Controller
{
    /**
     * Columns selected for the list endpoint. Kept tight so we don't ship
     * the TEXT description, whatsapp_number, drivetrain, engine_size, etc.
     * over the wire for every row on a 24-per-page listing.
     */
    protected const LIST_COLUMNS = [
        'id', 'slug',
        'title',
        'brand', 'model', 'trim',
        'body_type', 'year', 'color',
        'price', 'currency',
        'mileage', 'transmission', 'fuel_type', 'condition',
        'city',
        'is_featured', 'status',
        // 'published_at' is intentionally omitted from SELECT — it's only
        // needed by ORDER BY (which MySQL handles via the index alone) and
        // is never returned by CarResource.
    ];

    public function __construct(protected CarFilterService $filters) {}

    /**
     * GET /api/cars
     *
     * Returns a paginated list of publicly visible cars (status=available,
     * published_at set and not in the future), with filters + sort.
     */
    public function index(CarIndexRequest $request): AnonymousResourceCollection
    {
        $query = $this->visibleCarsQuery()
            ->select(self::LIST_COLUMNS)
            ->with(['displayImage:id,car_id,image_path,alt_text,is_primary,sort_order']);

        $this->filters->apply($query, $request->filters());

        $cars = $query
            ->paginate($request->perPage())
            ->withQueryString();

        return CarResource::collection($cars);
    }

    /**
     * GET /api/cars/{slug}
     *
     * Returns one publicly visible car with all images.
     */
    public function show(string $slug): CarDetailResource
    {
        $car = $this->visibleCarsQuery()
            ->with(['images:id,car_id,image_path,alt_text,sort_order,is_primary'])
            ->where('slug', $slug)
            ->firstOrFail();

        return new CarDetailResource($car);
    }

    protected function visibleCarsQuery(): Builder
    {
        return Car::query()->visible();
    }
}
