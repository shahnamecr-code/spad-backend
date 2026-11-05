<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class RenovationAiController extends Controller
{
    private function asset($id)
    {
        abort_unless(Schema::hasTable('spad_renovation_assets'), 503, 'کاتالوگ بازسازی آماده نیست.');
        $asset = DB::table('spad_renovation_assets')->where('id', $id)->first();
        abort_unless($asset, 404);
        return $asset;
    }

    public function status()
    {
        return response()->json(['data' => [
            'available' => (bool) config('services.spad_renovation_ai.enabled', false)
                && (bool) config('services.spad_renovation_ai.key', ''),
            'model' => 'SPAD AI Renovation',
            'photo_only' => true
        ]]);
    }

    public function assetImage($id)
    {
        $asset = $this->asset($id);
        abort_unless($asset->enabled && $asset->reference_path && Storage::disk('local')->exists($asset->reference_path), 404);
        return response()->file(Storage::disk('local')->path($asset->reference_path), [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, max-age=1800',
            'X-Content-Type-Options' => 'nosniff'
        ]);
    }

    public function uploadReference(Request $request, $id)
    {
        abort_unless(auth('admin')->check(), 403);
        $asset = $this->asset($id);
        $data = $request->validate(['image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120']);
        abort_unless(extension_loaded('gd'), 503, 'پردازش تصویر سرور فعال نیست.');
        $raw = file_get_contents($data['image']->getRealPath());
        $info = @getimagesizefromstring($raw);
        abort_unless($info && $info[0] >= 300 && $info[1] >= 300
            && $info[0] * $info[1] <= 18000000, 422, 'ابعاد عکس مرجع نامعتبر است.');
        $image = @imagecreatefromstring($raw);
        abort_unless($image, 422, 'فایل عکس معتبر نیست.');
        $width = imagesx($image); $height = imagesy($image);
        $ratio = min(1, 1600 / max($width, $height));
        $w = (int) round($width * $ratio); $h = (int) round($height * $ratio);
        $canvas = imagecreatetruecolor($w, $h);
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $w, $h, $width, $height);
        ob_start(); imagejpeg($canvas, null, 88); $bytes = ob_get_clean();
        imagedestroy($canvas); imagedestroy($image);
        $path = 'private/renovation/catalog/ref-' . (int) $id . '-' . bin2hex(random_bytes(8)) . '.jpg';
        abort_unless(Storage::disk('local')->put($path, $bytes), 503, 'ذخیره تصویر مرجع انجام نشد.');
        $old = $asset->reference_path;
        DB::table('spad_renovation_assets')->where('id', $id)->update(['reference_path' => $path, 'updated_at' => now()]);
        if ($old && $old !== $path) Storage::disk('local')->delete($old);
        return response()->json(['data' => [
            'reference_image_url' => url('/api/online-renovation/asset-photo/' . $id)
        ]]);
    }

    public function render(Request $request, $id)
    {
        $userId = optional($request->user())->id;
        abort_unless($userId, 401);
        abort_unless(config('services.spad_renovation_ai.enabled', false)
            && config('services.spad_renovation_ai.key', ''), 503, 'موتور بازسازی هوش مصنوعی هنوز توسط مدیریت فعال نشده است.');
        abort_unless(extension_loaded('gd'), 503, 'موتور تصویر سرور در دسترس نیست.');
        $d = $request->validate([
            'media_id' => 'required|integer|min:1',
            'layer_index' => 'required|integer|min:0|max:23',
            'frame_ratio' => 'required|numeric|min:0.5|max:3',
            'consent' => 'accepted'
        ]);
        $project = DB::table('spad_renovation_projects')->where('id', $id)
            ->where('user_id', $userId)->first();
        abort_unless($project && $project->source_type === 'user_upload', 404,
            'رندر هوش مصنوعی فعلاً فقط برای تصاویر بارگذاری‌شده توسط مالک پروژه فعال است.');
        $media = DB::table('spad_renovation_media')->where('id', $d['media_id'])
            ->where('project_id', $project->id)->where('user_id', $userId)
            ->where('kind', 'source')->first();
        abort_unless($media && Storage::disk('local')->exists($media->path), 404, 'تصویر اصلی پروژه پیدا نشد.');
        $scenes = json_decode($project->tour_scenes ?? '[]', true) ?: [];
        foreach ($scenes as $scene) {
            if ((int) ($scene['media_id'] ?? 0) === (int) $media->id)
                abort_if(($scene['kind'] ?? '') === 'panorama', 422, 'رندر تخصصی تور ۳۶۰ در مرحله بعد فعال می‌شود.');
        }
        $layers = json_decode($project->design_layers ?? '[]', true) ?: [];
        $layer = $layers[$d['layer_index']] ?? null;
        abort_unless($layer && ($layer['scene_key'] ?? '') === 'image:' . $media->id, 422, 'ابتدا طرح و محدوده عکس را ذخیره کنید.');
        $asset = DB::table('spad_renovation_assets')->where('code', $layer['asset_code'] ?? '')
            ->where('enabled', true)->first();
        abort_unless($asset, 422, 'نمونه طراحی انتخابی موجود نیست.');
        abort_if(DB::table('spad_renovation_media')->where('project_id', $project->id)
            ->where('kind', 'output')->where('room', 'like', 'AI:%')
            ->where('created_at', '>=', now()->subDay())->count() >= 6,
            429, 'سقف روزانه رندر هوش مصنوعی این پروژه تکمیل شده است.');
        $source = Storage::disk('local')->get($media->path);
        $imageInfo = @getimagesizefromstring($source);
        abort_unless($imageInfo && $imageInfo[0] >= 300 && $imageInfo[1] >= 200
            && $imageInfo[0] * $imageInfo[1] <= 18000000, 422, 'تصویر برای رندر مناسب نیست.');
        $original = @imagecreatefromstring($source);
        abort_unless($original, 422, 'تصویر پروژه قابل پردازش نیست.');
        $width = imagesx($original); $height = imagesy($original); $ratio = (float) $d['frame_ratio'];
        $cropW = min($width, (int) round($height * $ratio));
        $cropH = min($height, (int) round($width / $ratio));
        $left = (int) floor(($width - $cropW) / 2);
        $top = (int) floor(($height - $cropH) / 2);
        $outW = 1280; $outH = (int) max(426, min(2560, round($outW / $ratio)));
        $photo = imagecreatetruecolor($outW, $outH);
        imagecopyresampled($photo, $original, 0, 0, $left, $top, $outW, $outH, $cropW, $cropH);
        imagedestroy($original);
        ob_start(); imagejpeg($photo, null, 89); $photoData = ob_get_clean(); imagedestroy($photo);
        $mask = imagecreatetruecolor($outW, $outH);
        imagealphablending($mask, false); imagesavealpha($mask, true);
        $white = imagecolorallocatealpha($mask, 255, 255, 255, 0);
        $transparent = imagecolorallocatealpha($mask, 0, 0, 0, 127);
        imagefilledrectangle($mask, 0, 0, $outW, $outH, $white);
        $x1 = (int) floor($outW * (float) $layer['x'] / 100);
        $y1 = (int) floor($outH * (float) $layer['y'] / 100);
        $x2 = (int) ceil($outW * ((float) $layer['x'] + (float) $layer['w']) / 100);
        $y2 = (int) ceil($outH * ((float) $layer['y'] + (float) $layer['h']) / 100);
        abort_unless($x1 >= 0 && $y1 >= 0 && $x2 <= $outW && $y2 <= $outH
            && $x2 > $x1 && $y2 > $y1, 422, 'محدوده انتخابی معتبر نیست.');
        imagefilledrectangle($mask, $x1, $y1, $x2 - 1, $y2 - 1, $transparent);
        ob_start(); imagepng($mask); $maskData = ob_get_clean(); imagedestroy($mask);
        $reference = $asset->reference_path && Storage::disk('local')->exists($asset->reference_path)
            ? Storage::disk('local')->get($asset->reference_path) : null;
        $images = [['image_url' => 'data:image/jpeg;base64,' . base64_encode($photoData)]];
        if ($reference) $images[] = ['image_url' => 'data:image/jpeg;base64,' . base64_encode($reference)];
        $prompt = 'Photorealistic architectural renovation of the FIRST reference photograph. '
            . 'Modify ONLY the masked target zone (rectangle ' . round($layer['x'], 1) . '%,'
            . round($layer['y'], 1) . '% to ' . round($layer['x'] + $layer['w'], 1) . '%,'
            . round($layer['y'] + $layer['h'], 1) . '%). '
            . 'Replace the selected ' . $asset->category . ' with this exact chosen catalog design: '
            . $asset->title . '. Color ' . $asset->color_hex . '. '
            . ($asset->material_spec ?? '') . '. '
            . ($reference ? 'SECOND image is the selected product design reference. Preserve its style, finish, door pattern and hardware. ' : '')
            . 'Preserve all unmasked architecture, room layout, walls, floor, appliances, windows, dimensions, objects and camera viewpoint. '
            . 'Match perspective, realistic reflections, contact shadows, ambient light and occlusion. '
            . 'No extra furniture or rearrangement, no overlays, no text, no watermarks. '
            . 'A credible real-estate photograph; if the edit would require construction changes, keep surrounding structure unchanged.';
        try {
            $reply = Http::withToken(config('services.spad_renovation_ai.key'))
                ->acceptJson()->timeout(110)->connectTimeout(12)->post('https://api.openai.com/v1/images/edits', [
                    'model' => 'gpt-image-2.6-sunburst',
                    'images' => $images,
                    'mask' => ['image_url' => 'data:image/png;base64,' . base64_encode($maskData)],
                    'prompt' => $prompt,
                    'quality' => 'high',
                    'size' => 'auto',
                    'output_format' => 'png',
                    'n' => 1
                ]);
        } catch (\Throwable $e) {
            abort(503, 'پردازش تصویر موقتاً پاسخ نداد. لطفاً بعداً دوباره امتحان کنید.');
        }
        abort_unless($reply->successful(), 503, 'سرویس پردازش تصویر پاسخ موفق نداد.');
        $encoded = $reply->json('data.0.b64_json');
        $bytes = is_string($encoded) ? base64_decode($encoded, true) : false;
        abort_unless($bytes && strlen($bytes) <= 20000000, 503, 'خروجی تصویر هوش مصنوعی نامعتبر است.');
        $renderInfo = @getimagesizefromstring($bytes);
        abort_unless($renderInfo && $renderInfo[0] * $renderInfo[1] <= 24000000, 503, 'ابعاد تصویر تولیدشده قابل پذیرش نیست.');
        $rendered = @imagecreatefromstring($bytes);
        abort_unless($rendered, 503, 'خروجی قابل ذخیره نیست.');
        $rw = imagesx($rendered); $rh = imagesy($rendered);
        $shade = imagecolorallocatealpha($rendered, 2, 37, 82, 30);
        imagealphablending($rendered, true);
        imagefilledrectangle($rendered, 0, $rh - 56, $rw, $rh, $shade);
        $whiteText = imagecolorallocate($rendered, 255, 255, 255);
        imagestring($rendered, 5, 18, $rh - 44, 'SPAD.ESTATE | AI RENOVATION', $whiteText);
        imagestring($rendered, 3, 18, $rh - 24, 'Concept render - Project #' . $project->id, $whiteText);
        ob_start(); imagejpeg($rendered, null, 90); $output = ob_get_clean();
        imagedestroy($rendered);
        $path = 'private/renovation/' . $project->id . '/ai-' . bin2hex(random_bytes(10)) . '.jpg';
        abort_unless(Storage::disk('local')->put($path, $output), 503, 'ذخیره نتیجه ممکن نشد.');
        $outputId = DB::table('spad_renovation_media')->insertGetId([
            'project_id' => $project->id, 'user_id' => $userId, 'kind' => 'output',
            'room' => 'AI:' . mb_substr($asset->title, 0, 65), 'path' => $path, 'mime' => 'image/jpeg',
            'bytes' => strlen($output), 'created_at' => now(), 'updated_at' => now()
        ]);
        return response()->json(['data' => [
            'id' => $outputId,
            'url' => url('/api/online-renovation/' . $project->id . '/media/' . $outputId),
            'asset_code' => $asset->code,
            'notice' => 'تصویر مفهومی هوش مصنوعی است و جایگزین نقشه یا اندازه‌گیری اجرایی نیست.'
        ]], 201);
    }
}
