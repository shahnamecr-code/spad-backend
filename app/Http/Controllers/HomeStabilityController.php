<?php

namespace App\Http\Controllers;

use App\Services\PublicSnapshotService;
use Illuminate\Http\JsonResponse;

class HomeStabilityController extends Controller
{
    public function homeLayout(PublicSnapshotService $snapshots): JsonResponse
    {
        return $snapshots->serve('home-layout-v1', 60, function () {
            return app(AdvertisementController::class)->homeLayout();
        });
    }

    public function mainPageFiltersV1(PublicSnapshotService $snapshots): JsonResponse
    {
        return $snapshots->serve('home-filters-v1', 300, function () {
            return app(AdvertisementController::class)->mainPageFiltersV1();
        });
    }
}
