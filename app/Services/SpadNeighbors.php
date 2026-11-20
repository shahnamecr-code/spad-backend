<?php

namespace App\Services;

use App\Models\User;
use App\Support\SpadLocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class SpadNeighbors
{
    public const CATEGORIES = [
        'news' => 'اخبار و اطلاع‌رسانی', 'questions' => 'پرسش و پاسخ',
        'services' => 'خدمات محلی', 'lost-found' => 'گم‌شده و پیداشده',
        'events' => 'رویداد', 'help' => 'کمک', 'borrow' => 'وسایل امانی',
        'issues' => 'مشکلات عمومی',
    ];

    public static function installed(): void
    {
        abort_unless(Schema::hasTable('spad_neighbor_posts'), 503, 'اسپاد همسایه در حال آماده‌سازی است.');
    }

    public static function regions()
    {
        return DB::table('regions')->where('city_id', SpadLocation::cityId());
    }

    public static function region(int $id): void
    {
        abort_unless(self::regions()->where('id', $id)->exists(), 422, 'محلهٔ معتبر شیراز را انتخاب کنید.');
    }

    public static function account($user): User
    {
        abort_unless($user instanceof User && (int) $user->status === User::APPROVED, 403);
        return $user;
    }

    public static function preventPrivateContact(string $value, string $field): void
    {
        $normalized = str_replace(
            ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹','٠','١','٢','٣','٤','٥','٦','٧','٨','٩'],
            str_split('01234567890123456789'),
            $value
        );
        if (preg_match('/(?:[0-9][\s.()\x{200c}\x{200d}-]*){8,}/u', $normalized)
            || preg_match('/[^\s@]+@[^\s@]+\.[^\s@]+/u', $normalized)) {
            throw ValidationException::withMessages([$field => 'شماره تماس، ایمیل یا اطلاعات شخصی را در محتوای عمومی وارد نکنید.']);
        }
    }

    public static function notice(int $userId, string $key, string $title, int $postId): void
    {
        $now = now();
        if (!DB::table('spad_neighbor_notices')->insertOrIgnore([
            'user_id' => $userId, 'post_id' => $postId, 'event_key' => $key, 'title' => $title,
            'created_at' => $now, 'updated_at' => $now,
        ])) return;

        // Reuse the app inbox on deployed installations that have it.
        if (Schema::hasTable('spad_app_notifications')) {
            DB::table('spad_app_notifications')->insert([
                'user_id' => $userId, 'audience' => 'user', 'title' => $title,
                'body' => 'برای مشاهدهٔ جزئیات، اسپاد همسایه را باز کنید.',
                'link' => '/neighbors?id='.$postId, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }
}
