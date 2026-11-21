<?php

namespace App\Services;

use App\Models\User;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Morilog\Jalali\CalendarUtils;

class SpadHomePlanner
{
    public const GROUPS = 'spad_home_planner_groups';
    public const MEMBERS = 'spad_home_planner_members';
    public const INVITES = 'spad_home_planner_invites';
    public const TASKS = 'spad_home_planner_tasks';
    public const OCCURRENCES = 'spad_home_planner_occurrences';
    public const ZONE = 'Asia/Tehran';

    public static function installed(): void
    {
        abort_unless(Schema::hasTable(self::OCCURRENCES), 503, 'اسپاد خانه‌بان در حال آماده‌سازی است.');
    }

    public static function user($user): User
    {
        abort_unless($user instanceof User && (int) $user->status === User::APPROVED
            && $user->currentAccessToken()?->name !== 'partner-token', 403);
        return $user;
    }

    public static function member(User $user, int $group, bool $lock = false)
    {
        $query = DB::table(self::MEMBERS)->where('group_id', $group)->where('user_id', $user->id)->whereNull('removed_at');
        if ($lock) $query->lockForUpdate();
        $member = $query->first();
        abort_unless($member, 404);
        return $member;
    }

    public static function manager($member): void
    {
        abort_unless(in_array($member->role, ['owner', 'manager'], true), 403);
    }

    public static function owner($member): void
    {
        abort_unless($member->role === 'owner', 403);
    }

    public static function day(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value.' 12:00:00', new DateTimeZone(self::ZONE));
    }

    public static function matches($task, DateTimeImmutable $date): bool
    {
        $value = $date->format('Y-m-d');
        if ($value < $task->starts_on || ($task->ends_on !== null && $value > $task->ends_on)) return false;
        if ($task->recurrence === 'once') return $value === $task->starts_on;
        $start = self::day($task->starts_on);
        $unit = $task->recurrence === 'custom' ? $task->interval_unit : ['daily' => 'day', 'weekly' => 'week', 'monthly' => 'month'][$task->recurrence];
        $every = $task->recurrence === 'custom' ? max(1, (int) $task->interval_count) : 1;
        if ($unit === 'month') {
            [$year, $month, $day] = CalendarUtils::toJalali((int) $date->format('Y'), (int) $date->format('n'), (int) $date->format('j'));
            [$startYear, $startMonth, $startDay] = CalendarUtils::toJalali((int) $start->format('Y'), (int) $start->format('n'), (int) $start->format('j'));
            $months = ($year - $startYear) * 12 + $month - $startMonth;
            $target = min($startDay, CalendarUtils::jalaliMonthLength($year, $month));
            return $months % $every === 0 && $day === $target;
        }
        $days = (int) $start->diff($date)->days;
        return $days % ($unit === 'week' ? $every * 7 : $every) === 0;
    }

    public static function payload(array $data, int $status = 200)
    {
        return response()->json(['data' => $data], $status)->header('Cache-Control', 'private, no-store');
    }
}
