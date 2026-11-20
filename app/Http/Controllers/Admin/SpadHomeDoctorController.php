<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SpadAiAssistantSetting;
use App\Services\HomeDoctor\Catalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SpadHomeDoctorController extends Controller
{
    public function index()
    {
        $cases = DB::table('spad_home_doctor_cases');
        $status = (clone $cases)->selectRaw('status, COUNT(*) AS count')->groupBy('status')->pluck('count', 'status');
        $ratings = (clone $cases)->whereNotNull('feedback_rating')
            ->selectRaw('COUNT(*) AS count, AVG(feedback_rating) AS average')->first();
        $assistant = SpadAiAssistantSetting::activeSettings();

        return response()->json(['data' => [
            'categories' => array_values(Catalog::categories()),
            'case_count' => (int) $cases->count(),
            'status_counts' => (object) $status->map(function ($count) { return (int) $count; })->all(),
            'feedback_count' => (int) $ratings->count,
            'average_feedback' => $ratings->average === null ? null : round((float) $ratings->average, 2),
            'provider_configured' => (bool) ($assistant && $assistant->enabled && $assistant->apiKey()),
            'photos_available' => function_exists('imagecreatefromstring') && function_exists('imagejpeg'),
            'queue_connection' => config('queue.default'),
        ]])->header('Cache-Control', 'private, no-store');
    }

    public function updateCategory(Request $request, string $category)
    {
        abort_unless(isset(Catalog::defaults()[$category]), 404);
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'label' => ['present', 'nullable', 'string', 'max:100'],
            'description' => ['present', 'nullable', 'string', 'max:300'],
            'guide' => ['present', 'nullable', 'string', 'max:2400'],
        ]);

        DB::transaction(function () use ($data, $category) {
            $before = DB::table('spad_home_doctor_category_overrides')->where('code', $category)->lockForUpdate()->first();
            DB::table('spad_home_doctor_category_overrides')->upsert([
                ['code' => $category, 'updated_at' => now(), 'created_at' => now()] + $data,
            ], ['code'], array_merge(array_keys($data), ['updated_at']));
            DB::table('spad_home_service_admin_audit')->insert([
                'service' => 'doctor',
                'admin_id' => auth('admin')->id(),
                'changes' => json_encode(['category' => $category, 'before' => $before, 'after' => $data], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
            ]);
        });

        return $this->index();
    }
}
