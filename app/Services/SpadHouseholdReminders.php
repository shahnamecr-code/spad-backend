<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class SpadHouseholdReminders
{
    public const SERVICES = [
        'energy' => ['اسپاد انرژی', '/home-energy'],
        'book' => ['اسپاد هوم‌بوک', '/home-book'],
        'planner' => ['اسپاد خانه‌بان', '/home-planner'],
        'sunlight' => ['اسپاد نور', '/home-sunlight'],
        'garden' => ['اسپاد سبز', '/home-garden'],
        'smart' => ['اسپاد اسمارت', '/smart-home'],
    ];

    public static function available(): bool
    {
        return Schema::hasTable('spad_app_notifications') && Schema::hasTable('spad_household_reminders');
    }

    public static function deliverDue(): void
    {
        if (!self::available()) {
            return;
        }

        $now = now('UTC');
        $ids = DB::table('spad_household_reminders')->where('status', 'scheduled')
            ->where('due_at', '<=', $now)->orderBy('due_at')->orderBy('id')->limit(70)->pluck('id');
        foreach ($ids as $id) {
            DB::transaction(function () use ($id, $now) {
                $item = DB::table('spad_household_reminders')->where('id', $id)
                    ->where('status', 'scheduled')->where('due_at', '<=', $now)->lockForUpdate()->first();
                if (!$item || !isset(self::SERVICES[$item->service])) {
                    return;
                }

                [$name, $path] = self::SERVICES[$item->service];
                DB::table('spad_app_notifications')->insert([
                    'title' => 'یادآوری ' . $name,
                    'body' => 'زمان یادآوری ثبت‌شده در حساب شما فرا رسیده است.',
                    'link' => $path,
                    'audience' => 'user',
                    'user_id' => $item->user_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                DB::table('spad_household_reminders')->where('id', $id)->update([
                    'status' => 'sent', 'sent_at' => $now, 'updated_at' => $now,
                ]);
            }, 3);
        }
    }
}
