<?php

namespace App\Services\VirtualTour;

use App\Models\VirtualTour;

class VirtualTourPresenter
{
    public static function admin(VirtualTour $tour)
    {
        $tour->load(['advertisement', 'scenes.hotspots', 'floorplanPoints.scene', 'creator']);
        return [
            'id' => $tour->id,
            'advertisement_id' => $tour->advertisement_id,
            'property' => [
                'id' => optional($tour->advertisement)->id,
                'title' => optional($tour->advertisement)->title,
            ],
            'title' => $tour->title,
            'slug' => $tour->slug,
            'public_url' => self::frontendUrl($tour),
            'status' => $tour->status,
            'start_scene_id' => $tour->start_scene_id,
            'floorplan_url' => $tour->floorplan_path ? url('/api/admin/virtual-tours/assets/tours/' . $tour->id . '/floorplan') : null,
            'settings' => $tour->settings ?: [],
            'views_count' => $tour->views_count,
            'published_at' => optional($tour->published_at)->toIso8601String(),
            'created_by' => $tour->creator ? [
                'id' => $tour->creator->id,
                'name' => trim(($tour->creator->first_name ?? '') . ' ' . ($tour->creator->last_name ?? '')),
            ] : null,
            'created_at' => optional($tour->created_at)->toIso8601String(),
            'updated_at' => optional($tour->updated_at)->toIso8601String(),
            'scenes' => $tour->scenes->map(function ($scene) {
                return self::scene($scene, true);
            })->values(),
            'floorplan_points' => $tour->floorplanPoints->map(function ($point) {
                return [
                    'id' => $point->id,
                    'scene_id' => $point->scene_id,
                    'label' => $point->label,
                    'x' => (float) $point->x,
                    'y' => (float) $point->y,
                ];
            })->values(),
        ];
    }

    public static function public(VirtualTour $tour)
    {
        $tour->load(['scenes.hotspots', 'floorplanPoints']);
        return [
            'id' => $tour->id,
            'advertisement_id' => $tour->advertisement_id,
            'title' => $tour->title,
            'slug' => $tour->slug,
            'public_url' => self::frontendUrl($tour),
            'start_scene_id' => $tour->start_scene_id,
            'floorplan_url' => $tour->floorplan_path ? url('/api/virtual-tours/assets/tours/' . $tour->id . '/floorplan') : null,
            'settings' => $tour->settings ?: [],
            'scenes' => $tour->scenes->where('process_status', 'ready')->map(function ($scene) {
                return self::scene($scene, false);
            })->values(),
            'floorplan_points' => $tour->floorplanPoints->map(function ($point) {
                return [
                    'scene_id' => $point->scene_id,
                    'label' => $point->label,
                    'x' => (float) $point->x,
                    'y' => (float) $point->y,
                ];
            })->values(),
        ];
    }

    private static function scene($scene, $admin)
    {
        return [
            'id' => $scene->id,
            'uuid' => $scene->uuid,
            'title' => $scene->title,
            'images' => $admin ? self::adminImages($scene) : self::publicImages($scene),
            'width' => $scene->width,
            'height' => $scene->height,
            'initial_yaw' => (float) $scene->initial_yaw,
            'initial_pitch' => (float) $scene->initial_pitch,
            'initial_fov' => (float) $scene->initial_fov,
            'sort_order' => $scene->sort_order,
            'process_status' => $scene->process_status,
            'process_error' => $admin ? $scene->process_error : null,
            'metadata' => $admin ? ($scene->metadata ?: []) : [],
            'views_count' => $scene->views_count,
            'hotspots' => $scene->hotspots->map(function ($hotspot) use ($admin) {
                return [
                    'id' => $hotspot->id,
                    'scene_id' => $hotspot->scene_id,
                    'target_scene_id' => $hotspot->target_scene_id,
                    'type' => $hotspot->type,
                    'title' => $hotspot->title,
                    'content' => $hotspot->content,
                    'yaw' => (float) $hotspot->yaw,
                    'pitch' => (float) $hotspot->pitch,
                    'icon' => $hotspot->icon,
                    'media_url' => $hotspot->media_path
                        ? url(($admin ? '/api/admin/virtual-tours/assets/hotspots/' : '/api/virtual-tours/assets/hotspots/') . $hotspot->id)
                        : null,
                    'link_url' => $hotspot->link_url,
                    'settings' => $hotspot->settings ?: [],
                    'clicks_count' => $admin ? $hotspot->clicks_count : null,
                ];
            })->values(),
        ];
    }

    private static function adminImages($scene)
    {
        $result = [];
        foreach (['ultra', 'high', 'medium', 'preview', 'thumbnail'] as $key) {
            $column = $key . '_path';
            $result[$key] = $scene->{$column} ? url('/api/admin/virtual-tours/assets/scenes/' . $scene->id . '/' . $key) : null;
        }
        $result['original'] = null;
        return $result;
    }

    private static function publicImages($scene)
    {
        $result = [];
        foreach (['ultra', 'high', 'medium', 'preview', 'thumbnail'] as $key) {
            $column = $key . '_path';
            $result[$key] = $scene->{$column} ? url('/api/virtual-tours/assets/scenes/' . $scene->id . '/' . $key) : null;
        }
        $result['original'] = null;
        return $result;
    }

    private static function frontendUrl(VirtualTour $tour)
    {
        return rtrim(env('FRONTEND_URL', env('CLIENT_URL', 'https://spad.estate')), '/') . '/virtual-tour/' . $tour->slug;
    }
}
