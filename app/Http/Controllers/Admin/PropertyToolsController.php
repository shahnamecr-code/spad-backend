<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use Illuminate\Http\Request;

class PropertyToolsController extends Controller
{
    public function show(Request $request, Advertisement $advertisement)
    {
        $tool = $advertisement->toolConfig()->with('virtualTourFile')->first();

        return response()->json([
            'property_tools' => [
                'virtual_tour_enabled' => (bool) optional($tool)->virtual_tour_enabled,
                'virtual_tour_files' => optional($tool)->virtualTourFile ? [optional($tool)->virtualTourFile] : [],
                'virtual_tour_url' => optional($tool)->virtual_tour_url ?? '',
                'renovation_enabled' => (bool) optional($tool)->renovation_enabled,
                'renovation_url' => optional($tool)->renovation_url ?? '',
                'rental_deposit_ceiling' => optional($tool)->rental_deposit_ceiling ?? '',
                'rental_monthly_rent' => optional($tool)->rental_monthly_rent ?? '',
                'rental_occupancy_status' => optional($tool)->rental_occupancy_status ?: 'vacant',
                'rental_evacuation_date' => optional($tool)->rental_evacuation_date ? optional($tool)->rental_evacuation_date->format('Y-m-d') : '',
                'mortgage_enabled' => (bool) optional($tool)->mortgage_enabled,
                'mortgage_max_amount' => optional($tool)->mortgage_max_amount ?? '',
                'document_type' => optional($tool)->document_type ?? '',
                'owner_identity_verified' => (bool) optional($tool)->owner_identity_verified,
                'documents_verified' => (bool) optional($tool)->documents_verified,
                'below_expert_price' => (bool) optional($tool)->below_expert_price,
                'floorplan_3d_enabled' => (bool) optional($tool)->floorplan_3d_enabled,
                'floorplan_3d_url' => optional($tool)->floorplan_3d_url ?? '',
            ]
        ]);
    }
}
