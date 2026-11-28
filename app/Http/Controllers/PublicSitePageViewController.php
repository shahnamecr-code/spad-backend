<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use App\Models\User;
use App\Services\PublicSitePages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Laravel\Sanctum\PersonalAccessToken;

class PublicSitePageViewController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $token = $user instanceof User ? $user->currentAccessToken() : null;
        abort_unless($token instanceof PersonalAccessToken && $token->name === 'token', 403);

        if (app()->environment('production')) {
            abort_unless(in_array($request->header('Origin'), ['https://spad.estate', 'https://www.spad.estate'], true), 403);
        }

        $data = $request->validate([
            'event_key' => ['required', 'uuid'],
            'page' => ['required', 'string', Rule::in(array_keys(PublicSitePages::LABELS))],
            'property_id' => [
                'required_if:page,' . PublicSitePages::PROPERTY,
                'prohibited_unless:page,' . PublicSitePages::PROPERTY,
                'integer', 'min:1', 'max:9007199254740991',
            ],
            'user_id' => ['prohibited'],
        ]);
        $propertyId = $data['property_id'] ?? null;
        if ($propertyId !== null) {
            abort_unless(Advertisement::query()->publicCatalog()->whereKey($propertyId)->exists(), 404);
        }

        DB::table('spad_public_site_page_views')->insertOrIgnore([
            'user_id' => $user->id,
            'event_key' => strtolower($data['event_key']),
            'page' => $data['page'],
            'property_id' => $propertyId,
            'created_at' => now('Asia/Tehran'),
        ]);

        return response()->noContent();
    }
}
