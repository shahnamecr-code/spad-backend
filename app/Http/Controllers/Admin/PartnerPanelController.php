<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PartnerPanel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PartnerPanelController extends Controller
{
    public function index(Request $request)
    {
        $query = PartnerPanel::query()->with([
            'user:id,first_name,last_name,mobile,email,status,phone_verified_at',
            'creator:id,first_name,last_name',
            'updater:id,first_name,last_name',
        ]);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('organization_name', 'like', "%{$search}%")
                    ->orWhere('registration_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('mobile', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = max(10, min((int) $request->get('per_page', 20), 100));
        $result = $query->latest('id')->paginate($perPage);

        // Paginator serializes raw models by default. Type/status labels and the
        // admin-only fields live in adminPayload(), so transform the collection
        // before returning it. This also guarantees a freshly created panel
        // immediately shows its type in the admin table.
        $result->setCollection(
            $result->getCollection()->map(function (PartnerPanel $panel) {
                return $this->adminPayload($panel);
            })
        );

        return response()->json([
            'data' => $result,
            'meta' => $this->metaPayload(),
        ]);
    }

    public function meta()
    {
        return response()->json($this->metaPayload());
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request, true);
        $admin = auth('admin')->user() ?: $request->user();

        $panel = DB::transaction(function () use ($data, $admin) {
            $user = $this->resolveUser($data);

            if (!empty($data['verify_mobile']) && is_null($user->phone_verified_at)) {
                $user->update([
                    'phone_verified_at' => now(),
                ]);
            }

            $panel = PartnerPanel::withTrashed()
                ->where('user_id', $user->id)
                ->where('type', $data['type'])
                ->first();

            if ($panel && !$panel->trashed()) {
                abort(422, 'برای این کاربر، این نوع پنل قبلاً ایجاد شده است.');
            }

            $attributes = $this->panelAttributes($data);
            $attributes['user_id'] = $user->id;
            $attributes['created_by'] = $admin ? $admin->id : null;
            $attributes['updated_by'] = $admin ? $admin->id : null;
            if ($attributes['status'] === PartnerPanel::STATUS_ACTIVE) {
                $attributes['activated_at'] = now();
            }

            if ($panel && $panel->trashed()) {
                $panel->restore();
                $panel->fill($attributes)->save();
            } else {
                $panel = PartnerPanel::create($attributes);
            }

            return $panel;
        });

        return response()->json([
            'status' => true,
            'message' => 'پنل همکاری با موفقیت ایجاد شد.',
            'data' => $this->adminPayload($panel->fresh(['user', 'creator', 'updater'])),
        ], 201);
    }

    public function show(PartnerPanel $partnerPanel)
    {
        return response()->json([
            'data' => $this->adminPayload($partnerPanel->load(['user', 'creator', 'updater'])),
            'meta' => $this->metaPayload(),
        ]);
    }

    public function update(Request $request, PartnerPanel $partnerPanel)
    {
        $data = $this->validatePayload($request, false);
        $admin = auth('admin')->user() ?: $request->user();

        $duplicate = PartnerPanel::query()
            ->where('user_id', $partnerPanel->user_id)
            ->where('type', $data['type'])
            ->where('id', '!=', $partnerPanel->id)
            ->exists();
        if ($duplicate) {
            return response()->json([
                'message' => 'این کاربر از قبل پنلی با این نوع دارد.',
                'errors' => ['type' => ['برای هر کاربر، هر نوع پنل فقط یک‌بار قابل ایجاد است.']],
            ], 422);
        }

        $attributes = $this->panelAttributes($data);
        $attributes['updated_by'] = $admin ? $admin->id : null;

        if ($partnerPanel->status !== PartnerPanel::STATUS_ACTIVE && $attributes['status'] === PartnerPanel::STATUS_ACTIVE) {
            $attributes['activated_at'] = now();
        }

        DB::transaction(function () use ($partnerPanel, $attributes, $data) {
            $partnerPanel->update($attributes);

            $userUpdates = [];
            if (!empty($data['password'])) {
                $userUpdates['password'] = $data['password'];
            }
            if (!empty($data['verify_mobile']) && is_null(optional($partnerPanel->user)->phone_verified_at)) {
                $userUpdates['phone_verified_at'] = now();
            }

            if (!empty($userUpdates)) {
                $partnerPanel->user()->update($userUpdates);
            }
        });

        return response()->json([
            'status' => true,
            'message' => 'تنظیمات پنل همکاری به‌روزرسانی شد.',
            'data' => $this->adminPayload($partnerPanel->fresh(['user', 'creator', 'updater'])),
        ]);
    }

    public function destroy(Request $request, PartnerPanel $partnerPanel)
    {
        $admin = auth('admin')->user() ?: $request->user();
        if ($admin) {
            $partnerPanel->updated_by = $admin->id;
            $partnerPanel->save();
        }
        $partnerPanel->delete();

        return response()->json([
            'status' => true,
            'message' => 'پنل همکاری حذف شد. حساب کاربری اصلی حذف نشده است.',
        ]);
    }

    private function validatePayload(Request $request, $creating)
    {
        $rules = [
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'mobile' => [$creating ? 'required_without:user_id' : 'nullable', 'nullable', 'string', 'max:20'],
            'first_name' => [$creating ? 'required_without:user_id' : 'nullable', 'nullable', 'string', 'max:100'],
            'last_name' => [$creating ? 'required_without:user_id' : 'nullable', 'nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:190'],
            'password' => ['nullable', 'string', 'min:6', 'max:100', 'confirmed'],
            'password_confirmation' => ['nullable', 'string', 'min:6', 'max:100'],
            'verify_mobile' => ['nullable', 'boolean'],
            'type' => ['required', Rule::in(array_keys(PartnerPanel::types()))],
            'status' => ['required', Rule::in(array_keys(PartnerPanel::statuses()))],
            'title' => ['nullable', 'string', 'max:190'],
            'organization_name' => ['nullable', 'string', 'max:190'],
            'registration_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];

        return $request->validate($rules);
    }

    private function resolveUser(array $data)
    {
        if (!empty($data['user_id'])) {
            return User::findOrFail($data['user_id']);
        }

        $mobile = fix_mobile($data['mobile']);
        $existing = User::where('mobile', $mobile)->first();
        if ($existing) {
            return $existing;
        }

        if (empty($data['password'])) {
            throw ValidationException::withMessages([
                'password' => ['برای ساخت حساب کاربری جدید، تعیین رمز عبور الزامی است.'],
            ]);
        }

        return User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'mobile' => $mobile,
            'email' => $data['email'] ?? null,
            'status' => User::APPROVED,
            'password' => $data['password'],
        ]);
    }

    private function panelAttributes(array $data)
    {
        return [
            'type' => $data['type'],
            'status' => $data['status'],
            'title' => $data['title'] ?? null,
            'organization_name' => $data['organization_name'] ?? null,
            'registration_number' => $data['registration_number'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];
    }

    private function adminPayload(PartnerPanel $panel)
    {
        return array_merge($panel->publicPayload(), [
            'user_id' => $panel->user_id,
            'user' => $panel->user,
            'phone_verified' => (bool) optional($panel->user)->phone_verified_at,
            'notes' => $panel->notes,
            'settings' => $panel->settings ?: [],
            'created_by' => $panel->creator,
            'updated_by' => $panel->updater,
            'created_at' => optional($panel->created_at)->toIso8601String(),
            'updated_at' => optional($panel->updated_at)->toIso8601String(),
        ]);
    }

    private function metaPayload()
    {
        $counts = PartnerPanel::query()
            ->selectRaw('type, status, COUNT(*) as total')
            ->groupBy('type', 'status')
            ->get();

        return [
            'types' => PartnerPanel::types(),
            'statuses' => PartnerPanel::statuses(),
            'counts' => $counts,
            'total' => PartnerPanel::count(),
            'active' => PartnerPanel::active()->count(),
        ];
    }
}
