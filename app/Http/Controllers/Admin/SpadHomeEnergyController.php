<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\HomeEnergy\UtilityCapabilities;
use Illuminate\Support\Facades\DB;

class SpadHomeEnergyController extends Controller
{
    public function summary(UtilityCapabilities $provider)
    {
        $admin = auth('admin')->user();
        abort_unless($admin && $admin->roles()->where('name', 'Super Admin')->where('guard_name', 'admin')->exists(), 403);
        return response()->json(['data' => [
            'location_count' => DB::table('spad_home_energy_locations')->count(),
            'account_count' => DB::table('spad_home_energy_accounts')->count(),
            'bill_count' => DB::table('spad_home_energy_bills')->count(),
            'receipt_count' => DB::table('spad_home_energy_bills')->whereNotNull('receipt_path')->count(),
            'provider' => $provider->read(),
        ]])->header('Cache-Control', 'private, no-store');
    }
}
