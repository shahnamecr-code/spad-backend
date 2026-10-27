<?php

namespace App\Http\Controllers;

use App\Models\PartnerPanel;
use Illuminate\Http\Request;

class PartnerPanelController extends Controller
{
    public function index(Request $request)
    {
        $panels = $request->user()
            ->partnerPanels()
            ->active()
            ->orderBy('id')
            ->get()
            ->map(function (PartnerPanel $panel) {
                return $panel->publicPayload();
            })
            ->values();

        return response()->json([
            'status' => true,
            'data' => $panels,
        ]);
    }

    public function show(Request $request, PartnerPanel $partnerPanel)
    {
        if ((int) $partnerPanel->user_id !== (int) $request->user()->id || $partnerPanel->status !== PartnerPanel::STATUS_ACTIVE) {
            return response()->json([
                'status' => false,
                'message' => 'دسترسی به این پنل مجاز نیست.'
            ], 403);
        }

        return response()->json([
            'status' => true,
            'data' => $partnerPanel->publicPayload(),
        ]);
    }
}
