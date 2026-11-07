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
        $path = data_get($tour->settings, 'model_path');

        if (!$path || !str_starts_with($path, 'virtual-tours/' . $tour->id . '/models/') ||
            !Storage::disk(config('virtual-tour.disk', 'local'))->exists($path)) {
            $errors[] = 'برای انتشار بازدید سه‌بعدی، ابتدا فایل مدل GLB معتبر را بارگذاری کنید.';
        }
        if (!$tour->floorplan_path) {
            $warnings[] = 'پلان اختیاری هنوز ثبت نشده است؛ مسیر حرکت با خود مدل سه‌بعدی کنترل می‌شود.';
        }
        return compact('errors', 'warnings');
    }
}
