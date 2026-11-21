<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SpadHomePlanner as Planner;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SpadHomePlannerController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            Planner::installed();
            return $next($request);
        });
    }

    public function groups(Request $request)
    {
        $user = Planner::user($request->user());
        $groups = DB::table(Planner::MEMBERS.' as member')->join(Planner::GROUPS.' as family', 'family.id', '=', 'member.group_id')
            ->where('member.user_id', $user->id)->whereNull('member.removed_at')->orderByDesc('family.id')
            ->get(['family.id', 'family.name', 'member.role']);
        return Planner::payload(['groups' => $groups, 'today' => now(Planner::ZONE)->toDateString()]);
    }

    public function createGroup(Request $request)
    {
        $user = Planner::user($request->user());
        $input = $request->validate(['name' => ['required', 'string', 'min:2', 'max:100']]);
        $id = DB::transaction(function () use ($user, $input) {
            DB::table('users')->where('id', $user->id)->lockForUpdate()->first(['id']);
            abort_if(DB::table(Planner::GROUPS)->where('owner_id', $user->id)->count() >= 12, 422, 'سقف خانواده‌های این حساب تکمیل شده است.');
            $now = now();
            $id = DB::table(Planner::GROUPS)->insertGetId(['name' => $input['name'], 'owner_id' => $user->id, 'created_at' => $now, 'updated_at' => $now]);
            DB::table(Planner::MEMBERS)->insert(['group_id' => $id, 'user_id' => $user->id, 'role' => 'owner', 'created_at' => $now, 'updated_at' => $now]);
            return $id;
        }, 3);
        return Planner::payload(['id' => $id, 'name' => $input['name'], 'role' => 'owner'], 201);
    }

    public function dashboard(Request $request, int $group)
    {
        $user = Planner::user($request->user());
        $member = Planner::member($user, $group);
        $family = DB::table(Planner::GROUPS)->where('id', $group)->first(['id', 'name']);
        abort_unless($family, 404);
        $input = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:2000-01-01', 'before_or_equal:2100-12-31'],
            'until' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:2000-01-01', 'before_or_equal:2100-12-31'],
        ]);
        $today = now(Planner::ZONE);
        $start = Planner::day($input['from'] ?? $today->copy()->subDays(($today->dayOfWeekIso + 1) % 7)->toDateString());
        $end = isset($input['until']) ? Planner::day($input['until']) : $start->modify('+6 days');
        abort_unless($end >= $start && $start->diff($end)->days <= 61, 422, 'حداکثر بازهٔ برنامه ۶۲ روز است.');
        $from = $start->format('Y-m-d');
        $until = $end->format('Y-m-d');

        $members = DB::table(Planner::MEMBERS.' as membership')->join('users as account', 'account.id', '=', 'membership.user_id')
            ->where('membership.group_id', $group)->whereNull('membership.removed_at')->whereNull('account.deleted_at')
            ->where('account.status', User::APPROVED)->orderBy('membership.id')->get(['membership.id', 'membership.role', 'account.first_name']);
        $tasks = DB::table(Planner::TASKS)->where('group_id', $group)->whereNull('archived_at')->orderByDesc('id')
            ->get(['id', 'title', 'notes', 'assignee_id', 'starts_on', 'ends_on', 'recurrence', 'interval_count', 'interval_unit', 'points']);
        $completionQuery = DB::table(Planner::OCCURRENCES)->where('group_id', $group)->whereBetween('due_on', [$from, $until]);
        $done = $completionQuery->get()->keyBy(fn ($row) => $row->task_id.':'.$row->due_on);
        $points = DB::table(Planner::OCCURRENCES)->where('group_id', $group)->selectRaw('member_id, SUM(points_awarded) AS total')
            ->groupBy('member_id')->pluck('total', 'member_id');
        $occurrences = [];
        foreach ($tasks as $task) {
            if ($task->starts_on > $until || ($task->ends_on !== null && $task->ends_on < $from)) continue;
            for ($date = $start; $date <= $end; $date = $date->modify('+1 day')) {
                if (!Planner::matches($task, $date)) continue;
                $due = $date->format('Y-m-d');
                $key = $task->id.':'.$due;
                $completed = $done->pull($key);
                $occurrences[] = $completed ? $this->completed($completed) : [
                    'task_id' => $task->id, 'due_on' => $due, 'title' => $task->title,
                    'assignee_id' => $task->assignee_id, 'points' => (int) $task->points,
                    'completed' => false, 'completed_by' => null, 'completed_at' => null,
                    'can_complete' => $due <= $today->toDateString() && (!$task->assignee_id || $task->assignee_id == $member->id),
                ];
            }
        }
        foreach ($done as $row) $occurrences[] = $this->completed($row);
        usort($occurrences, fn ($a, $b) => [$a['due_on'], $a['task_id']] <=> [$b['due_on'], $b['task_id']]);
        $completedCount = count(array_filter($occurrences, fn ($item) => $item['completed']));
        $visibleMembers = $members->map(fn ($row) => [
            'id' => $row->id, 'name' => $this->name($row->first_name),
            'role' => $row->role, 'points' => (int) ($points[$row->id] ?? 0),
        ]);
        $invites = in_array($member->role, ['owner', 'manager'], true)
            ? DB::table(Planner::INVITES)->where('group_id', $group)->whereNull('accepted_at')->whereNull('revoked_at')
                ->where('expires_at', '>', now())->orderByDesc('id')->limit(25)->get(['id', 'role', 'expires_at', 'created_by'])
                ->map(fn ($invite) => ['id' => $invite->id, 'role' => $invite->role, 'expires_at' => $invite->expires_at,
                    'can_revoke' => $member->role === 'owner' || $invite->created_by == $member->id])
            : collect();
        return Planner::payload([
            'group' => ['id' => $family->id, 'name' => $family->name, 'role' => $member->role, 'member_id' => $member->id],
            'today' => $today->toDateString(), 'from' => $from, 'until' => $until,
            'tasks' => $tasks, 'members' => $visibleMembers, 'occurrences' => $occurrences, 'invitations' => $invites,
            'progress' => ['scheduled' => count($occurrences), 'completed' => $completedCount,
                'percent' => $occurrences ? (int) round(100 * $completedCount / count($occurrences)) : 0,
                'points' => (int) $completionQuery->sum('points_awarded')],
        ]);
    }

    public function updateGroup(Request $request, int $group)
    {
        $input = $request->validate(['name' => ['required', 'string', 'min:2', 'max:100']]);
        $this->mutateGroup($request, $group, function ($member) use ($group, $input) {
            Planner::owner($member);
            DB::table(Planner::GROUPS)->where('id', $group)->update(['name' => $input['name'], 'updated_at' => now()]);
        });
        return Planner::payload(['id' => $group, 'name' => $input['name']]);
    }

    public function invite(Request $request, int $group)
    {
        $input = $request->validate(['role' => ['required', Rule::in(['member', 'manager'])]]);
        $token = bin2hex(random_bytes(24));
        $expires = now()->addHours(72);
        $id = $this->mutateGroup($request, $group, function ($member) use ($group, $input, $expires, $token) {
            Planner::manager($member);
            abort_if($input['role'] === 'manager' && $member->role !== 'owner', 403);
            $invites = DB::table(Planner::INVITES);
            $open = (clone $invites)->where('group_id', $group)->whereNull('accepted_at')->whereNull('revoked_at')->where('expires_at', '>', now())->count();
            abort_if($open >= 25, 422, 'سقف دعوت‌های باز تکمیل شده است.');
            return $invites->insertGetId([
                'group_id' => $group, 'created_by' => $member->id, 'token_hash' => hash('sha256', $token), 'role' => $input['role'],
                'expires_at' => $expires, 'created_at' => now(), 'updated_at' => now(),
            ]);
        });
        return Planner::payload(['id' => $id, 'token' => $token, 'role' => $input['role'], 'expires_at' => $expires->toIso8601String()], 201);
    }

    public function revoke(Request $request, int $group, int $invite)
    {
        $this->mutateGroup($request, $group, function ($member) use ($group, $invite) {
            Planner::manager($member);
            $query = DB::table(Planner::INVITES)->where('group_id', $group)->where('id', $invite)->whereNull('accepted_at');
            if ($member->role !== 'owner') $query->where('created_by', $member->id);
            abort_unless($query->exists(), 404);
            $query->whereNull('revoked_at')->update(['revoked_at' => now(), 'updated_at' => now()]);
        });
        return Planner::payload(['revoked' => true]);
    }

    public function accept(Request $request)
    {
        $user = Planner::user($request->user());
        $input = $request->validate(['token' => ['required', 'string', 'size:48', 'regex:/^[a-f0-9]+$/D']]);
        $hash = hash('sha256', $input['token']);
        $group = DB::table(Planner::INVITES)->where('token_hash', $hash)->value('group_id');
        abort_unless($group, 404, 'دعوت معتبر یا قابل استفاده نیست.');
        $result = DB::transaction(function () use ($group, $hash, $user) {
            $family = DB::table(Planner::GROUPS)->where('id', $group)->lockForUpdate()->first();
            $invite = DB::table(Planner::INVITES)->where('group_id', $group)->where('token_hash', $hash)->lockForUpdate()->first();
            abort_unless($family && $invite && !$invite->revoked_at, 404, 'دعوت معتبر یا قابل استفاده نیست.');
            $members = DB::table(Planner::MEMBERS)->where('group_id', $group);
            $existing = (clone $members)->where('user_id', $user->id)->first();
            if ($invite->accepted_at) {
                abort_unless($invite->accepted_by == $user->id && $existing && !$existing->removed_at, 404, 'دعوت معتبر یا قابل استفاده نیست.');
                return ['id' => $family->id, 'name' => $family->name, 'role' => $existing->role];
            }
            $issuer = (clone $members)->where('id', $invite->created_by)->whereNull('removed_at')->first();
            abort_unless(Carbon::parse($invite->expires_at)->isFuture() && $issuer && in_array($issuer->role, ['owner','manager'], true)
                && ($invite->role === 'member' || $issuer->role === 'owner'), 404, 'دعوت معتبر یا قابل استفاده نیست.');
            $role = $existing && !$existing->removed_at ? $existing->role : $invite->role;
            if (!$existing || $existing->removed_at) {
                abort_if((clone $members)->whereNull('removed_at')->count() >= 30, 422, 'ظرفیت این خانواده تکمیل شده است.');
                DB::table(Planner::MEMBERS)->upsert([[
                    'group_id' => $group, 'user_id' => $user->id, 'role' => $role, 'removed_at' => null,
                    'created_at' => now(), 'updated_at' => now(),
                ]], ['group_id', 'user_id'], ['role', 'removed_at', 'updated_at']);
            }
            DB::table(Planner::INVITES)->where('id', $invite->id)->update(['accepted_at' => now(), 'accepted_by' => $user->id, 'updated_at' => now()]);
            return ['id' => $family->id, 'name' => $family->name, 'role' => $role];
        }, 3);
        return Planner::payload($result);
    }

    public function updateMember(Request $request, int $group, int $member)
    {
        $input = $request->validate(['role' => ['required', Rule::in(['member', 'manager'])]]);
        $this->mutateGroup($request, $group, function ($actor) use ($group, $member, $input) {
            Planner::owner($actor);
            $target = $this->targetMember($group, $member);
            abort_if($target->role === 'owner', 403);
            DB::table(Planner::MEMBERS)->where('id', $member)->update(['role' => $input['role'], 'updated_at' => now()]);
            if ($input['role'] === 'member') $this->revokeIssuer($group, $member);
        });
        return Planner::payload(['updated' => true]);
    }

    public function removeMember(Request $request, int $group, int $member)
    {
        $this->mutateGroup($request, $group, function ($actor) use ($group, $member) {
            if ($actor->id != $member) Planner::owner($actor);
            $target = $this->targetMember($group, $member);
            abort_if($target->role === 'owner', 403);
            DB::table(Planner::MEMBERS)->where('id', $member)->update(['removed_at' => now(), 'updated_at' => now()]);
            DB::table(Planner::TASKS)->where('group_id', $group)->where('assignee_id', $member)->whereNull('archived_at')
                ->update(['assignee_id' => null, 'updated_at' => now()]);
            $this->revokeIssuer($group, $member);
        });
        return Planner::payload(['removed' => true]);
    }

    public function saveTask(Request $request, int $group, ?int $task = null)
    {
        $input = $request->validate([
            'title' => ['required', 'string', 'min:2', 'max:120'], 'notes' => ['nullable', 'string', 'max:1500'],
            'assignee_id' => ['nullable', 'integer', 'min:1'], 'points' => ['required', 'integer', 'min:0', 'max:100'],
            'recurrence' => ['required', Rule::in(['once', 'daily', 'weekly', 'monthly', 'custom'])],
            'starts_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:2000-01-01', 'before_or_equal:2100-12-31'],
            'ends_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:starts_on', 'before_or_equal:2100-12-31'],
            'interval_count' => [Rule::requiredIf($request->input('recurrence') === 'custom'), 'nullable', 'integer', 'min:1', 'max:365'],
            'interval_unit' => [Rule::requiredIf($request->input('recurrence') === 'custom'), 'nullable', Rule::in(['day', 'week', 'month'])],
        ]);
        $values = $input + ['notes' => null, 'assignee_id' => null, 'ends_on' => null];
        $values['interval_count'] = $input['recurrence'] === 'custom' ? $input['interval_count'] : 1;
        $values['interval_unit'] = $input['recurrence'] === 'custom' ? $input['interval_unit'] : null;
        $values['updated_at'] = now();
        $id = $this->mutateGroup($request, $group, function ($member) use ($task, $group, $values) {
            Planner::manager($member);
            if ($values['assignee_id']) {
                $this->targetMember($group, (int) $values['assignee_id']);
            }
            $tasks = DB::table(Planner::TASKS)->where('group_id', $group)->whereNull('archived_at');
            if ($task !== null) {
                $target = $tasks->where('id', $task);
                abort_unless($target->exists(), 404);
                $target->update($values);
                return $task;
            }
            abort_if($tasks->count() >= 200, 422, 'سقف کارهای فعال این خانواده تکمیل شده است.');
            return DB::table(Planner::TASKS)->insertGetId($values + ['group_id' => $group, 'created_by' => $member->id, 'created_at' => now()]);
        });
        return Planner::payload(['id' => $id], $task === null ? 201 : 200);
    }

    public function archiveTask(Request $request, int $group, int $task)
    {
        $this->mutateGroup($request, $group, function ($member) use ($group, $task) {
            Planner::manager($member);
            $query = DB::table(Planner::TASKS)->where('group_id', $group)->where('id', $task);
            abort_unless($query->exists(), 404);
            $query->whereNull('archived_at')->update(['archived_at' => now(), 'updated_at' => now()]);
        });
        return Planner::payload(['archived' => true]);
    }

    public function complete(Request $request, int $group, int $task, string $date)
    {
        validator(['date' => $date], ['date' => ['required', 'date_format:Y-m-d']])->validate();
        $result = $this->mutateGroup($request, $group, function ($member) use ($group, $task, $date) {
            $row = DB::table(Planner::TASKS)->where('group_id', $group)->where('id', $task)->first();
            abort_unless($row, 404);
            $completion = DB::table(Planner::OCCURRENCES)->where('task_id', $task)->where('due_on', $date)->first();
            if ($completion && $completion->member_id == $member->id) return $this->completed($completion);
            abort_if($row->assignee_id && $row->assignee_id != $member->id, 403, 'این کار به عضو دیگری واگذار شده است.');
            abort_if($completion, 409, 'این کار در این تاریخ انجام شده است.');
            abort_unless(!$row->archived_at && Planner::matches($row, Planner::day($date)), 404);
            abort_if($date > now(Planner::ZONE)->toDateString(), 422, 'هنوز موعد این کار نرسیده است.');
            DB::table(Planner::OCCURRENCES)->insert([
                'task_id' => $task, 'group_id' => $group, 'member_id' => $member->id, 'due_on' => $date,
                'title' => $row->title, 'points_awarded' => $row->points, 'completed_at' => now(),
            ]);
            return $this->completed(DB::table(Planner::OCCURRENCES)->where('task_id', $task)->where('due_on', $date)->first());
        });
        return Planner::payload($result);
    }

    private function mutateGroup(Request $request, int $group, callable $action)
    {
        $user = Planner::user($request->user());
        Planner::member($user, $group);
        return DB::transaction(function () use ($user, $group, $action) {
            abort_unless(DB::table(Planner::GROUPS)->where('id', $group)->lockForUpdate()->first(['id']), 404);
            return $action(Planner::member($user, $group, true));
        }, 3);
    }

    private function targetMember(int $group, int $member)
    {
        $row = DB::table(Planner::MEMBERS.' as member')->join('users as account', 'account.id', '=', 'member.user_id')
            ->where('member.group_id', $group)->where('member.id', $member)->whereNull('member.removed_at')
            ->whereNull('account.deleted_at')->where('account.status', User::APPROVED)->first(['member.*']);
        abort_unless($row, 404, 'عضو فعال خانواده پیدا نشد.');
        return $row;
    }

    private function revokeIssuer(int $group, int $member): void
    {
        DB::table(Planner::INVITES)->where('group_id', $group)->where('created_by', $member)->whereNull('revoked_at')
            ->whereNull('accepted_at')->update(['revoked_at' => now(), 'updated_at' => now()]);
    }

    private function completed($row): array
    {
        return ['task_id' => $row->task_id, 'due_on' => $row->due_on, 'title' => $row->title, 'assignee_id' => $row->member_id,
            'points' => (int) $row->points_awarded, 'completed' => true, 'completed_by' => $row->member_id,
            'completed_at' => $row->completed_at, 'can_complete' => false];
    }

    private function name(?string $name): string
    {
        return $name && preg_match('/^[\p{L}\p{M} \x{200c}]{2,40}$/u', trim($name)) ? trim($name) : 'عضو خانواده';
    }
}
