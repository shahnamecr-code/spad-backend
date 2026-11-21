<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddSpadHouseholdServicesSettings extends Migration
{
    public function up(): void
    {
        foreach ($this->services() as $name) {
            DB::table('spad_home_service_settings')->insertOrIgnore([
                'service' => $name,
                'enabled' => true,
                'writes_enabled' => true,
                'writes_per_minute' => 18,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('spad_home_service_settings')->whereIn('service', $this->services())->delete();
    }

    private function services(): array
    {
        return ['energy', 'book', 'planner', 'sunlight', 'garden', 'smart'];
    }
}
