<?php

namespace App\Services\VirtualTour;

use App\Models\Advertisement;

class VirtualTourAccess
{
    public static function assertAdminCanManage($admin, Advertisement $advertisement)
    {
        if (!$admin) {
            abort(401);
        }

        if ((int) $admin->id === 1) {
            return;
        }

        if (method_exists($admin, 'hasRole') && $admin->hasRole('admin')) {
            return;
        }

        if (method_exists($admin, 'can') && ($admin->can('advertisement.edit') || $admin->can('reject.advertisement'))) {
            return;
        }

        if ($advertisement->admins()->where('admins.id', $admin->id)->exists()) {
            return;
        }

        if ($advertisement->writable_type === get_class($admin) && (int) $advertisement->writable_id === (int) $admin->id) {
            return;
        }

        abort(403, 'شما اجازه مدیریت تور این ملک را ندارید.');
    }
}
