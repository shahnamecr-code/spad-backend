<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\SpadHomePlanner as Planner;
use Illuminate\Support\Facades\DB;

class SpadHomePlannerController extends Controller
{
    public function overview()
    {
        $admin = auth('admin')->user();
        abort_unless($admin instanceof Admin && (int) $admin->status === Admin::APPROVED
            && $admin->roles()->where('name', 'Super Admin')->exists(), 403);
        Planner::installed();
        $period = now()->subDays(30);
        $completion = DB::table(Planner::OCCURRENCES)->where('completed_at', '>=', $period);
        return Planner::payload([
            'groups' => DB::table(Planner::GROUPS)->count(),
            'memberships' => DB::table(Planner::MEMBERS)->whereNull('removed_at')->count(),
            'active_tasks' => DB::table(Planner::TASKS)->whereNull('archived_at')->count(),
            'completions_30d' => (clone $completion)->count(),
            'points_30d' => (int) (clone $completion)->sum('points_awarded'),
            'active_groups_30d' => (clone $completion)->distinct()->count('group_id'),
            'open_invites' => DB::table(Planner::INVITES)->whereNull('accepted_at')->whereNull('revoked_at')->where('expires_at', '>', now())->count(),
        ]);
    }
}
