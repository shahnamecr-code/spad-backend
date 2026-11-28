<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PublicSiteVisitController extends Controller
{
    public function store(Request $request)
    {
        if (app()->environment('production')) {
            abort_unless(in_array($request->header('Origin'), ['https://spad.estate', 'https://www.spad.estate'], true), 403);
        }
        $data = $request->validate(['browser_id' => ['required', 'string', 'regex:/^[a-f0-9]{32}$/D']]);
        abort_unless(Schema::hasTable('spad_public_site_visitors'), 503);

        $day = now('Asia/Tehran')->toDateString();
        DB::table('spad_public_site_visitors')->insertOrIgnore([
            'visit_day' => $day,
            'browser_hash' => hash_hmac('sha256', $day . ':' . $data['browser_id'], (string) config('app.key')),
        ]);

        return response()->noContent();
    }
}
