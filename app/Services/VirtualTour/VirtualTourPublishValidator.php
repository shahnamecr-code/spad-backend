<?php

namespace App\Services\VirtualTour;

use App\Models\VirtualTour;

class VirtualTourPublishValidator
{
    public function validateTour(VirtualTour $tour)
    {
        $tour->load(['scenes.hotspots', 'floorplanPoints']);
        $errors = [];
        $warnings = [];
        $scenes = $tour->scenes;

        if ($scenes->isEmpty()) {
            $errors[] = 'حداقل یک Scene برای انتشار تور لازم است.';
            return compact('errors', 'warnings');
        }

        if (!$tour->start_scene_id || !$scenes->contains('id', $tour->start_scene_id)) {
            $errors[] = 'Start Scene معتبر تعیین نشده است.';
        }

        foreach ($scenes as $scene) {
            if (!$scene->original_path) {
                $errors[] = 'Scene «' . $scene->title . '» تصویر ندارد.';
            }
            if ($scene->process_status !== 'ready') {
                $errors[] = 'پردازش تصویر Scene «' . $scene->title . '» کامل نشده است.';
            }
            foreach ($scene->hotspots as $hotspot) {
                if ($hotspot->type === 'navigation' && (!$hotspot->target_scene_id || !$scenes->contains('id', $hotspot->target_scene_id))) {
                    $errors[] = 'یک Hotspot ناوبری در «' . $scene->title . '» مقصد معتبر ندارد.';
                }
            }
        }

        if ($tour->start_scene_id && $scenes->contains('id', $tour->start_scene_id)) {
            $visited = [];
            $queue = [(int) $tour->start_scene_id];
            while ($queue) {
                $id = array_shift($queue);
                if (isset($visited[$id])) continue;
                $visited[$id] = true;
                $scene = $scenes->firstWhere('id', $id);
                if (!$scene) continue;
                foreach ($scene->hotspots as $hotspot) {
                    if ($hotspot->type === 'navigation' && $hotspot->target_scene_id && !isset($visited[$hotspot->target_scene_id])) {
                        $queue[] = (int) $hotspot->target_scene_id;
                    }
                }
            }
            foreach ($scenes as $scene) {
                if (!isset($visited[$scene->id])) {
                    $warnings[] = 'Scene «' . $scene->title . '» از Start Scene قابل دسترسی نیست.';
                }
            }
        }

        if ($tour->floorplan_path && $tour->floorplanPoints->isEmpty()) {
            $warnings[] = 'پلان آپلود شده اما هنوز Sceneای روی آن مشخص نشده است.';
        }

        return compact('errors', 'warnings');
    }
}
