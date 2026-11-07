<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use App\Models\VirtualTour;
use App\Models\VirtualTourEvent;
use App\Models\VirtualTourHotspot;
use App\Models\VirtualTourScene;
use App\Models\VirtualTourSession;
use App\Services\VirtualTour\VirtualTourPresenter;
use App\Services\VirtualTour\SpadTourTemplateV3;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class VirtualTourController extends Controller
{
    /**
     * Read-only site-owned IRANSans FaNum fonts. Files are deployed from
     * SPAD's existing private source repository, not from user ZIP uploads.
     * CORS is required because a sandboxed tour iframe has an opaque origin.
     */
    public function tourFont($weight)
    {
        $names = [
            'regular' => 'IRANSansWeb(FaNum).woff',
            'medium' => 'IRANSansWeb(FaNum)_Medium.woff',
            'bold' => 'IRANSansWeb(FaNum)_Bold.woff',
            'black' => 'IRANSansWeb(FaNum)_Black.woff',
            'light' => 'IRANSansWeb(FaNum)_Light.woff',
            'ultralight' => 'IRANSansWeb(FaNum)_UltraLight.woff',
        ];
        if (!isset($names[$weight])) abort(404);
        $file = storage_path('app/spad-tour-fonts/' . $names[$weight]);
        if (!is_file($file) || !is_readable($file)) abort(404);

        return response()->file($file, [
            'Content-Type' => 'font/woff',
            'X-Content-Type-Options' => 'nosniff',
            'Access-Control-Allow-Origin' => '*',
            'Cross-Origin-Resource-Policy' => 'cross-origin',
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }

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

    public function modelAsset(VirtualTour $virtualTour)
    {
        $this->assertPublished($virtualTour);
        $path = data_get($virtualTour->settings, 'model_path');
        $disk = Storage::disk(config('virtual-tour.disk', 'local'));
        if (!$path || !str_starts_with($path, 'virtual-tours/' . $virtualTour->id . '/models/') || !$disk->exists($path)) {
            abort(404);
        }
        return response()->file($disk->path($path), [
            'Content-Type' => 'model/gltf-binary',
            'Access-Control-Allow-Origin' => '*',
            'Cache-Control' => 'public, max-age=604800',
            'X-Content-Type-Options' => 'nosniff',
        ]);
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

    /**
     * Deliver whitelisted files of a published ready-made tour.
     * They are isolated by a CSP sandbox and the embedding iframe sandbox.
     * No server executable can ever be served from an uploaded archive.
     */
    public function packageAsset(VirtualTour $virtualTour, $asset)
    {
        $this->assertPublished($virtualTour);
        $settings = $virtualTour->settings ?: [];
        $root = $settings['package_root'] ?? '';
        $allowed = $settings['package_files'] ?? [];
        if (empty($settings['package_ready'])
            || !is_string($root)
            || !str_starts_with($root, 'virtual-tours/packages/' . $virtualTour->id . '/')
            || !is_string($asset)
            || strlen($asset) > 260
            || str_contains($asset, '\\')
            || str_contains($asset, "\0")
            || str_contains($asset, '..')
            || str_starts_with($asset, '/')
            || !is_array($allowed)
            || !in_array($asset, $allowed, true)) {
            abort(404);
        }
        $disk = Storage::disk(config('virtual-tour.disk', 'local'));
        $relative = $root . '/' . $asset;
        if (!$disk->exists($relative)) abort(404);

        $ext = strtolower(pathinfo($asset, PATHINFO_EXTENSION));
        $types = [
            'html' => 'text/html; charset=UTF-8',
            'js' => 'text/javascript; charset=UTF-8',
            'css' => 'text/css; charset=UTF-8',
            'json' => 'application/json',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'webm' => 'video/webm',
            'mp4' => 'video/mp4',
        ];
        if (!isset($types[$ext])) abort(404);

        $headers = [
            'Content-Type' => $types[$ext],
            'X-Content-Type-Options' => 'nosniff',
            'Access-Control-Allow-Origin' => '*',
            'Cross-Origin-Resource-Policy' => 'cross-origin',
            'Cache-Control' => 'no-store',
            'Referrer-Policy' => 'no-referrer',
        ];
        if ($asset === 'index.html') {
            // Sandbox with an opaque origin; user-provided JavaScript cannot
            // read SPAD cookies, local storage or the parent website.
            $headers['Content-Security-Policy'] =
                "sandbox allow-scripts; default-src 'none'; " .
                "script-src 'self' 'unsafe-inline'; " .
                "style-src 'self' 'unsafe-inline'; " .
                "img-src 'self' data: blob:; font-src 'self' https://panel.spad.estate data:; " .
                "media-src 'self' blob:; connect-src 'none'; " .
                "object-src 'none'; base-uri 'none'; form-action 'none'; " .
                "frame-ancestors https://spad.estate https://www.spad.estate https://panel.spad.estate";
        }

        // The packaged SPAD WebGL player uses <img> -> texture. When
        // embedded in an opaque sandbox, that image must opt into CORS.
        // Presentation-only v3 transformation. Keep stored ZIP bytes and
        // unrelated packages untouched; no studio or upload migration needed.
        if (in_array($asset, ['index.html', 'styles.css', 'scenes.js', 'app.js'], true)) {
            $source = $disk->get($relative);
            if ($asset === 'app.js') {
                $source = str_replace(
                    "const img=new Image();img.decoding='async';",
                    "const img=new Image();img.crossOrigin='anonymous';img.decoding='async';",
                    $source
                );
            }
            return response(SpadTourTemplateV3::apply($asset, $source), 200, $headers);
        }

        return response()->file($disk->path($relative), $headers);
    }

    public function byAdvertisement(Advertisement $advertisement)
    {
        if ((int) $advertisement->status !== Advertisement::APPROVED) abort(404);
        $tour = VirtualTour::where('advertisement_id', $advertisement->id)
            ->where('status', 'published')
            ->latest('published_at')
            ->first();

        if (!$tour || (!data_get($tour->settings, 'package_ready') && !$tour->scenes()->where('process_status', 'ready')->exists())) {
            return response()->json(['status' => false, 'data' => null], 404);
        }

        return response()->json(['status' => true, 'data' => VirtualTourPresenter::public($tour)]);
    }

    public function bySlug($slug)
    {
        $tour = VirtualTour::where('slug', $slug)->where('status', 'published')->firstOrFail();
        $this->assertPublished($tour);
        if (!data_get($tour->settings, 'package_ready') && !$tour->scenes()->where('process_status', 'ready')->exists()) abort(404);
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
        if ($tour->status !== 'published' || !$tour->advertisement || (int) $tour->advertisement->status !== Advertisement::APPROVED) {
            abort(404);
        }
    }
}
