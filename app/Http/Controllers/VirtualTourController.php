<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use App\Models\VirtualTour;
use App\Models\VirtualTourEvent;
use App\Models\VirtualTourHotspot;
use App\Models\VirtualTourScene;
use App\Models\VirtualTourSession;
use App\Services\VirtualTour\VirtualTourPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class VirtualTourController extends Controller
{
    public function sceneAsset(VirtualTourScene $scene, $variant)
    {
        $tour = $scene->tour;
        $this->assertPublished($tour);
        $allowed = ['ultra', 'high', 'medium', 'preview', 'thumbnail'];
        if (!in_array($variant, $allowed, true)) {
            abort(404);
        }
        $column = $variant . '_path';
        $path = $scene->{$column};
        if (!$path || !Storage::disk(config('virtual-tour.disk', 'local'))->exists($path)) {
            abort(404);
        }
        $response = response()->file(Storage::disk(config('virtual-tour.disk', 'local'))->path($path));
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Cache-Control', 'public, max-age=604800, immutable');
        return $response;
    }

    public function floorplanAsset(VirtualTour $virtualTour)
    {
        $this->assertPublished($virtualTour);
        $disk = Storage::disk(config('virtual-tour.disk', 'local'));
        if (!$virtualTour->floorplan_path || !$disk->exists($virtualTour->floorplan_path)) abort(404);
        $response = response()->file($disk->path($virtualTour->floorplan_path));
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Cache-Control', 'public, max-age=604800');
        return $response;
    }

    public function hotspotAsset(VirtualTourHotspot $hotspot)
    {
        $tour = $hotspot->scene->tour;
        $this->assertPublished($tour);
        $disk = Storage::disk(config('virtual-tour.disk', 'local'));
        if (!$hotspot->media_path || !$disk->exists($hotspot->media_path)) abort(404);
        $response = response()->file($disk->path($hotspot->media_path));
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Cache-Control', 'public, max-age=604800');
        return $response;
    }

    public function byAdvertisement(Advertisement $advertisement)
    {
        if ($advertisement->is_investor_exclusive) abort(404);
        $tour = VirtualTour::where('advertisement_id', $advertisement->id)
            ->where('status', 'published')
            ->latest('published_at')
            ->first();

        if (!$tour) {
            return response()->json(['status' => false, 'data' => null], 404);
        }

        return response()->json(['status' => true, 'data' => VirtualTourPresenter::public($tour)]);
    }

    public function bySlug($slug)
    {
        $tour = VirtualTour::where('slug', $slug)->where('status', 'published')->firstOrFail();
        $this->assertPublished($tour);
        return response()->json(['status' => true, 'data' => VirtualTourPresenter::public($tour)]);
    }

    public function startSession(Request $request, VirtualTour $virtualTour)
    {
        $this->assertPublished($virtualTour);
        $data = $request->validate(['session_key' => 'required|string|min:16|max:80']);
        $session = VirtualTourSession::firstOrCreate(
            ['tour_id' => $virtualTour->id, 'session_key' => $data['session_key']],
            [
                'ip_hash' => hash('sha256', (string) $request->ip() . '|' . config('app.key')),
                'user_agent_hash' => hash('sha256', (string) $request->userAgent()),
                'started_at' => now(),
                'last_seen_at' => now(),
            ]
        );

        if ($session->wasRecentlyCreated) {
            $virtualTour->increment('views_count');
            VirtualTourEvent::create([
                'tour_id' => $virtualTour->id,
                'session_id' => $session->id,
                'type' => 'tour_view',
                'created_at' => now(),
            ]);
        } else {
            $session->update(['last_seen_at' => now()]);
        }

        return response()->json(['status' => true]);
    }

    public function event(Request $request, VirtualTour $virtualTour)
    {
        $this->assertPublished($virtualTour);
        $data = $request->validate([
            'session_key' => 'required|string|min:16|max:80',
            'type' => ['required', Rule::in(['scene_view', 'hotspot_click', 'share', 'qr', 'fullscreen', 'vr'])],
            'scene_id' => 'nullable|integer',
            'hotspot_id' => 'nullable|integer',
            'metadata' => 'nullable|array',
        ]);

        $session = VirtualTourSession::where('tour_id', $virtualTour->id)
            ->where('session_key', $data['session_key'])->first();
        if (!$session) {
            return response()->json(['status' => false, 'message' => 'session_not_found'], 409);
        }

        $scene = null;
        if (!empty($data['scene_id'])) {
            $scene = VirtualTourScene::where('tour_id', $virtualTour->id)->find($data['scene_id']);
        }
        $hotspot = null;
        if (!empty($data['hotspot_id'])) {
            $hotspot = VirtualTourHotspot::whereIn('scene_id', $virtualTour->scenes()->pluck('id'))->find($data['hotspot_id']);
        }

        DB::transaction(function () use ($data, $session, $virtualTour, $scene, $hotspot) {
            if ($data['type'] === 'scene_view' && $scene) {
                $scene->increment('views_count');
                $session->increment('scene_views');
            }
            if ($data['type'] === 'hotspot_click' && $hotspot) {
                $hotspot->increment('clicks_count');
                $session->increment('hotspot_clicks');
            }
            VirtualTourEvent::create([
                'tour_id' => $virtualTour->id,
                'session_id' => $session->id,
                'scene_id' => $scene ? $scene->id : null,
                'hotspot_id' => $hotspot ? $hotspot->id : null,
                'type' => $data['type'],
                'metadata' => isset($data['metadata']) ? $data['metadata'] : null,
                'created_at' => now(),
            ]);
            $this->touchSession($session);
        });

        return response()->json(['status' => true]);
    }

    public function ping(Request $request, VirtualTour $virtualTour)
    {
        $this->assertPublished($virtualTour);
        $data = $request->validate(['session_key' => 'required|string|min:16|max:80']);
        $session = VirtualTourSession::where('tour_id', $virtualTour->id)
            ->where('session_key', $data['session_key'])->first();
        if ($session) {
            $this->touchSession($session);
        }
        return response()->json(['status' => true]);
    }

    private function touchSession(VirtualTourSession $session)
    {
        $started = $session->started_at ?: $session->created_at;
        $duration = $started ? min(14400, max(0, now()->diffInSeconds($started))) : 0;
        $session->update(['last_seen_at' => now(), 'duration_seconds' => $duration]);
    }

    private function assertPublished(VirtualTour $tour)
    {
        if ($tour->status !== 'published' || optional($tour->advertisement)->is_investor_exclusive) {
            abort(404);
        }
    }
}
