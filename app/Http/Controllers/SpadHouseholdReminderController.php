<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SpadHouseholdReminders;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SpadHouseholdReminderController extends Controller
{
    private const COLUMNS = ['id', 'service', 'title', 'due_at', 'status', 'sent_at'];

    public function index(Request $request)
    {
        $reminders = $this->own($request)->orderByDesc('id')->limit(140)->get(self::COLUMNS);
        return response()->json(['data' => $reminders->map([$this, 'serialize']), 'app_inbox_available' => SpadHouseholdReminders::available()])
            ->header('Cache-Control', 'no-store, private');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'service' => ['required', Rule::in(array_keys(SpadHouseholdReminders::SERVICES))],
            'title' => ['required', 'string', 'max:100'],
            'due_at' => ['required', 'date', 'regex:/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(?:\.[0-9]{3})?(?:Z|[+-][0-9]{2}:[0-9]{2})$/'],
            'request_key' => ['required', 'string', 'regex:/^[A-Za-z0-9._-]{20,72}$/'],
        ]);
        $user = $this->user($request);
        $due = Carbon::parse($data['due_at'])->utc()->format('Y-m-d H:i:s');
        $now = now('UTC');
        [$row, $created] = DB::transaction(function () use ($user, $data, $due, $now) {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $query = DB::table('spad_household_reminders')->where('user_id', $user->id);
            $existing = (clone $query)->where('request_key', $data['request_key'])->first(self::COLUMNS);
            if ($existing) {
                abort_unless($existing->service === $data['service'] && $existing->title === $data['title'] && $existing->due_at === $due,
                    409, 'شناسهٔ درخواست قبلاً برای یادآوری دیگری استفاده شده است.');
                return [$existing, false];
            }

            abort_unless(SpadHouseholdReminders::available(), 503, 'صندوق اعلان اپلیکیشن در دسترس نیست.');
            abort_unless($due > $now->format('Y-m-d H:i:s') && $due < $now->copy()->addYears(2)->format('Y-m-d H:i:s'),
                422, 'زمان یادآوری باید در آینده و کمتر از دو سال دیگر باشد.');
            abort_if((clone $query)->where('status', 'scheduled')->where('request_key', 'not like', 'garden:%')->count() >= 60, 429, 'حداکثر ۶۰ یادآوری فعال می‌توانید ثبت کنید.');

            $id = DB::table('spad_household_reminders')->insertGetId([
                'user_id' => $user->id, 'service' => $data['service'], 'title' => $data['title'],
                'request_key' => $data['request_key'], 'due_at' => $due,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            return [$query->where('id', $id)->first(self::COLUMNS), true];
        }, 3);
        return response()->json(['data' => $this->serialize($row)], $created ? 201 : 200)
            ->header('Cache-Control', 'no-store, private');
    }

    public function destroy(Request $request, int $id)
    {
        $this->own($request)->where('id', $id)->delete();
        return response()->noContent();
    }

    public function serialize($row): array
    {
        return [
            'id' => (int) $row->id, 'service' => $row->service, 'title' => $row->title, 'status' => $row->status,
            'due_at' => Carbon::parse($row->due_at, 'UTC')->toISOString(),
            'sent_at' => $row->sent_at ? Carbon::parse($row->sent_at, 'UTC')->toISOString() : null,
        ];
    }

    private function own(Request $request)
    {
        return DB::table('spad_household_reminders')->where('user_id', $this->user($request)->id)
            ->where('request_key', 'not like', 'garden:%');
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && (int) $user->status === User::APPROVED
            && $user->currentAccessToken()?->name !== 'partner-token', 403);
        return $user;
    }
}
