<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdvertisementTool extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'virtual_tour_enabled' => 'boolean',
        'renovation_enabled' => 'boolean',
        'mortgage_enabled' => 'boolean',
        'owner_identity_verified' => 'boolean',
        'documents_verified' => 'boolean',
        'below_expert_price' => 'boolean',
        'floorplan_3d_enabled' => 'boolean',
        'rental_deposit_ceiling' => 'integer',
        'rental_monthly_rent' => 'integer',
        'rental_evacuation_date' => 'date:Y-m-d',
        'mortgage_max_amount' => 'integer',
    ];

    public function advertisement()
    {
        return $this->belongsTo(Advertisement::class);
    }

    public function virtualTourFile()
    {
        return $this->belongsTo(File::class, 'virtual_tour_file_id');
    }
}
