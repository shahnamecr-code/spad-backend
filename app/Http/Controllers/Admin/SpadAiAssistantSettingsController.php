<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SpadAiAssistantSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class SpadAiAssistantSettingsController extends Controller
{
    private function requireSuperAdmin()
    {
        $admin = auth('admin')->user();
        abort_unless($admin && $admin->roles()->where('name', 'Super Admin')->exists(), 403);
    }

    public function show()
    {
        $this->requireSuperAdmin();
        $setting = SpadAiAssistantSetting::activeSettings();
        return response()->json(['data' => [
            'enabled' => (bool) optional($setting)->enabled,
            'title' => optional($setting)->title ?: 'دستیار هوشمند ملکی اسپاد',
            'model' => optional($setting)->model ?: 'gpt-4.1-mini',
            'daily_limit' => optional($setting)->daily_limit ?: 30,
            'max_output_tokens' => optional($setting)->max_output_tokens ?: 450,
            'custom_instructions' => optional($setting)->custom_instructions ?: '',
            'api_key_configured' => (bool) ($setting && $setting->apiKey()),
        ]])->header('Cache-Control', 'private, no-store');
    }

    public function save(Request $request)
    {
        $this->requireSuperAdmin();
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'title' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'in:gpt-4.1-mini,gpt-4.1,gpt-4o-mini'],
            'daily_limit' => ['required', 'integer', 'between:5,100'],
            'max_output_tokens' => ['required', 'integer', 'between:180,1000'],
            'custom_instructions' => ['nullable', 'string', 'max:3500'],
            'api_key' => ['nullable', 'string', 'min:20', 'max:300'],
        ]);
        $settings = SpadAiAssistantSetting::query()->firstOrCreate(['id' => 1]);
        $settings->fill([
            'enabled' => (bool) $data['enabled'],
            'title' => $data['title'],
            'model' => $data['model'],
            'daily_limit' => (int) $data['daily_limit'],
            'max_output_tokens' => (int) $data['max_output_tokens'],
            'custom_instructions' => $data['custom_instructions'] ?? '',
        ]);
        if (isset($data['api_key']) && trim($data['api_key']) !== '') {
            $settings->api_key_encrypted = Crypt::encryptString(trim($data['api_key']));
        }
        if ($settings->enabled && !$settings->apiKey()) {
            return response()->json(['message' => 'برای فعال‌سازی ابتدا کلید API سرویس هوش مصنوعی را تنظیم کنید.'], 422);
        }
        $settings->save();
        return $this->show();
    }
}
