<?php

namespace App\Http\Middleware;

use App\Models\PartnerPanel;
use Closure;
use Illuminate\Http\Request;

class EnsureActivePartnerPanel
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $panelId = $request->route('partnerPanel') ?: $request->input('partner_panel_id');
        $query = $user->partnerPanels()->active();

        if ($panelId instanceof PartnerPanel) {
            $panelId = $panelId->id;
        }

        $panel = $panelId ? $query->whereKey($panelId)->first() : $query->first();
        if (!$panel) {
            return response()->json([
                'status' => false,
                'message' => 'پنل همکاری فعال برای این حساب وجود ندارد.'
            ], 403);
        }

        $request->attributes->set('partner_panel', $panel);
        return $next($request);
    }
}
