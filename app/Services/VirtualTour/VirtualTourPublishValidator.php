<?php

namespace App\Services\VirtualTour;

use App\Models\VirtualTour;
use Illuminate\Support\Facades\Storage;

class VirtualTourPublishValidator
{
    public function validateTour(VirtualTour $tour)
    {
        $errors = [];
        $warnings = [];
        $disk = Storage::disk(config('virtual-tour.disk', 'local'));

        // A virtual tour is a sequence of processed 360-degree images, not a GLB model.
        $ready = $tour->scenes()->where('process_status', 'ready')->get()
            ->filter(function ($scene) use ($disk) {
                foreach (['medium_path', 'high_path', 'preview_path'] as $field) {
                    if ($scene->{$field} && $disk->exists($scene->{$field})) return true;
                }
                return false;
            });

        if ($ready->isEmpty()) {
            $errors[] = 'برای انتشار تور مجازی، حداقل یک تصویر پانورامای ۳۶۰ درجه پردازش‌شده لازم است.';
        }

        $unfinished = $tour->scenes()->where('process_status', '!=', 'ready')->count();
        if ($unfinished > 0) {
            $warnings[] = 'برخی تصاویر هنوز آماده نیستند و تا تکمیل پردازش در تور نمایش داده نمی‌شوند.';
        }

        if ($ready->isNotEmpty() && $tour->start_scene_id &&
            !$ready->contains('id', (int) $tour->start_scene_id)) {
            $warnings[] = 'سکانس شروع آماده نیست؛ تور از اولین سکانس آماده آغاز می‌شود.';
        }

        return compact('errors', 'warnings');
    }
}
