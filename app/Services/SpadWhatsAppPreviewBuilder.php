<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

/**
 * Chrome is never started from PHP-FPM. A cache miss is queued for the
 * server's existing every-minute Laravel scheduler. All outputs remain
 * the genuinely sourced, approved SPAD PNGs, with the normal cache TTL.
 */
class SpadWhatsAppPreviewBuilder
{
    private const TABLE = 'spad_wa_preview_build_queue';

    public function enqueue(string $kind, ?string $province, ?string $city): array
    {
        if (!in_array($kind, ['market', 'city'], true)
            || ($kind === 'city' && (!$province || !$city))) {
            throw new RuntimeException('نوع گزارش یا نام شهر برای پیش‌نمایش معتبر نیست.', 422);
        }
        if (!Schema::hasTable(self::TABLE)) {
            throw new RuntimeException('صف ساخت تصاویر اسپاد هنوز نصب نشده است.', 503);
        }
        $key = hash('sha256', $kind . '|' . ($province ?? '') . '|' . ($city ?? ''));
        $now = now();
        // A single row per city/market makes simultaneous clicks idempotent.
        DB::table(self::TABLE)->insertOrIgnore([
            'target_key' => $key, 'kind' => $kind,
            'province' => $province, 'city' => $city,
            'state' => 'pending', 'requested_at' => $now,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $job = DB::table(self::TABLE)->where('target_key', $key)->first();
        if (!$job) throw new RuntimeException('درخواست ساخت بنر ثبت نشد.', 503);
        $age = $job->completed_at
            ? $now->diffInSeconds(Carbon::parse($job->completed_at)) : 99999;
        if ($job->state === 'completed' && $age > 60) {
            // This branch occurs when a cache is not visible to the FPM worker.
            // Still safe: let CLI refresh, without allowing rapid duplicate builds.
            DB::table(self::TABLE)->where('id', $job->id)
                ->where('state', 'completed')->where('completed_at', '<', $now->copy()->subMinute())
                ->update(['state' => 'pending', 'requested_at' => $now,
                    'error_code' => null, 'updated_at' => $now]);
        } elseif ($job->state === 'failed') {
            $delay = $job->completed_at
                ? $now->diffInSeconds(Carbon::parse($job->completed_at)) : 99999;
            if ($delay > 120) {
                DB::table(self::TABLE)->where('id', $job->id)->where('state', 'failed')
                    ->update(['state' => 'pending', 'requested_at' => $now,
                        'error_code' => null, 'updated_at' => $now]);
            }
        } elseif ($job->state === 'processing' && $job->started_at
            && Carbon::parse($job->started_at)->lt($now->copy()->subMinutes(3))) {
            DB::table(self::TABLE)->where('id', $job->id)->where('state', 'processing')
                ->update(['state'=>'pending','requested_at'=>$now, 'updated_at'=>$now]);
        }
        $state = (string) DB::table(self::TABLE)->where('id', $job->id)->value('state');
        return ['status' => $state === 'failed' ? 'temporarily_unavailable' : 'preparing',
            'retry_after' => $state === 'failed' ? 30 : 5];
    }

    public function process(int $limit = 4): array
    {
        if (!Schema::hasTable(self::TABLE)) return ['status'=>'not_installed'];
        $count=0;$failed=0;
        $jobs = DB::table(self::TABLE)->where('state','pending')
            ->orderBy('requested_at')->limit(max(1,min(4,$limit)))->get();
        foreach ($jobs as $job) {
            $claimed = DB::table(self::TABLE)->where('id', $job->id)->where('state','pending')
                ->update(['state'=>'processing','started_at'=>now(),
                    'attempts'=>DB::raw('attempts + 1'),'updated_at'=>now()]);
            if (!$claimed) continue;
            try {
                $png = app(SpadWhatsAppMarketPublisher::class)
                    ->buildPreviewFresh($job->kind, $job->province, $job->city);
                app(SpadWhatsAppPreviewCache::class)
                    ->put($job->kind, $job->province, $job->city, $png);
                DB::table(self::TABLE)->where('id',$job->id)->update([
                    'state'=>'completed','completed_at'=>now(),
                    'error_code'=>null,'updated_at'=>now(),
                ]);
                $count++;
            } catch (Throwable $e) {
                DB::table(self::TABLE)->where('id',$job->id)->update([
                    'state'=>'failed','completed_at'=>now(),
                    'error_code'=>$e instanceof RuntimeException ? 'SOURCE_OR_RENDER_UNAVAILABLE' : 'INTERNAL_ERROR',
                    'updated_at'=>now(),
                ]);
                Log::warning('SPAD on-demand WhatsApp preview CLI build failed', [
                    'kind'=>$job->kind,'exception_type'=>get_class($e),
                ]);
                $failed++;
            }
        }
        return ['status'=>'processed','built'=>$count,'failed'=>$failed];
    }
}
