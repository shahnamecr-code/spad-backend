<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PropertyHandoverController extends Controller
{
    private function ready()
    {
        abort_unless(Schema::hasTable('spad_handover_checklists') && Schema::hasTable('spad_handover_photos'), 503, 'بایگانی چک‌لیست‌های تحویل در حال راه‌اندازی است.');
    }

    private function uid(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->id, 401, 'ابتدا وارد حساب کاربری اسپاد شوید.');
        return (int) $user->id;
    }

    private function owned(Request $request, $id)
    {
        $item = DB::table('spad_handover_checklists')
            ->where('user_id', $this->uid($request))->where('id', $id)->first();
        abort_unless($item, 404, 'چک‌لیست پیدا نشد.');
        return $item;
    }

    private function serialize($row, $photos = false)
    {
        $row->details = json_decode($row->details ?: '{}', true) ?: [];
        $row->items = json_decode($row->items ?: '{}', true) ?: [];
        if ($photos) {
            $row->photos = DB::table('spad_handover_photos')
                ->where('checklist_id', $row->id)
                ->get(['id', 'item_key', 'original_name', 'mime', 'bytes', 'created_at']);
        }
        return $row;
    }

    public function index(Request $request)
    {
        $this->ready();
        $rows = DB::table('spad_handover_checklists')
            ->where('user_id', $this->uid($request))
            ->orderByDesc('updated_at')->limit(150)
            ->get(['id', 'type', 'status', 'title', 'address', 'handover_date', 'updated_at', 'created_at']);
        return response()->json(['data' => $rows]);
    }

    public function store(Request $request)
    {
        $this->ready();
        $uid = $this->uid($request);
        $input = $request->validate([
            'type' => 'required|in:sale,rental_in,rental_out',
            'title' => 'required|string|min:3|max:150',
            'address' => 'nullable|string|max:500',
        ]);
        abort_if(DB::table('spad_handover_checklists')->where('user_id', $uid)->count() >= 200, 422, 'سقف ۲۰۰ پرونده برای این حساب تکمیل شده است.');
        $id = DB::table('spad_handover_checklists')->insertGetId([
            'user_id' => $uid, 'type' => $input['type'],
            'status' => 'draft', 'title' => $input['title'],
            'address' => $input['address'] ?? null,
            'details' => '{}', 'items' => '{}',
            'created_at' => now(), 'updated_at' => now()
        ]);
        return response()->json(['data' => $this->serialize($this->owned($request, $id), true)], 201);
    }

    public function show(Request $request, $id)
    {
        $this->ready();
        return response()->json(['data' => $this->serialize($this->owned($request, $id), true)]);
    }

    public function update(Request $request, $id)
    {
        $this->ready();
        $row = $this->owned($request, $id);
        abort_if($row->status !== 'draft', 422, 'پرونده نهایی شده و برای حفاظت از گزارش قابل ویرایش نیست.');
        $input = $request->validate([
            'title' => 'sometimes|required|string|min:3|max:150',
            'address' => 'nullable|string|max:500',
            'unit' => 'nullable|string|max:70',
            'contract_reference' => 'nullable|string|max:100',
            'handover_from' => 'nullable|string|max:150',
            'handover_to' => 'nullable|string|max:150',
            'handover_date' => 'nullable|date_format:Y-m-d',
            'details' => 'sometimes|array|max:25',
            'items' => 'sometimes|array|max:80',
        ]);
        if (array_key_exists('details', $input)) {
            $allowed = ['water', 'electricity', 'gas', 'electricity_id', 'water_id', 'gas_id',
                'keys', 'remotes', 'access_cards', 'parking', 'storage', 'utility_debts',
                'building_charge', 'deposit_settlement', 'inventory', 'repairs', 'commitments', 'general_notes'];
            $details = [];
            foreach ($input['details'] as $key => $value) {
                if (!in_array($key, $allowed, true) || (!is_string($value) && !is_numeric($value)) || mb_strlen((string)$value) > 1600) {
                    throw ValidationException::withMessages(['details' => ['جزئیات ثبت‌شده معتبر نیست.']]);
                }
                $details[$key] = (string)$value;
            }
            $input['details'] = json_encode($details, JSON_UNESCAPED_UNICODE);
        }
        if (array_key_exists('items', $input)) {
            $items = [];
            foreach ($input['items'] as $key => $value) {
                if (!is_string($key) || !preg_match('/^[a-z0-9_-]{2,70}$/D', $key) ||
                    !is_array($value) || !in_array($value['status'] ?? null, ['ok','issue','na','pending'], true) ||
                    !is_string($value['note'] ?? '') || mb_strlen($value['note'] ?? '') > 500) {
                    throw ValidationException::withMessages(['items' => ['وضعیت یا توضیح یکی از موارد چک‌لیست معتبر نیست.']]);
                }
                $items[$key] = ['status' => $value['status'], 'note' => $value['note'] ?? ''];
            }
            $input['items'] = json_encode($items, JSON_UNESCAPED_UNICODE);
        }
        $input['updated_at'] = now();
        DB::table('spad_handover_checklists')->where('id', $row->id)->where('user_id', $row->user_id)->update($input);
        return $this->show($request, $id);
    }

    public function complete(Request $request, $id)
    {
        $this->ready();
        $row = $this->owned($request, $id);
        abort_if($row->status !== 'draft', 422, 'این گزارش قبلاً نهایی شده است.');
        $items = json_decode($row->items ?: '{}', true) ?: [];
        $verified = count(array_filter($items, function ($v) { return ($v['status'] ?? 'pending') !== 'pending'; }));
        abort_if($verified < 1, 422, 'پیش از نهایی‌کردن، حداقل یک مورد را بررسی کنید.');
        DB::table('spad_handover_checklists')->where('id', $row->id)->where('user_id', $row->user_id)
            ->update(['status' => 'completed', 'completed_at' => now(), 'updated_at' => now()]);
        return $this->show($request, $id);
    }

    public function uploadPhoto(Request $request, $id)
    {
        $this->ready();
        $row = $this->owned($request, $id);
        abort_if($row->status !== 'draft', 422, 'برای پرونده نهایی‌شده نمی‌توان عکس جدید ثبت کرد.');
        $input = $request->validate([
            'photo' => 'required|file|mimes:jpg,jpeg,png,webp|max:6144',
            'item_key' => 'nullable|string|regex:/^[a-z0-9_-]{2,70}$/D|max:70'
        ]);
        abort_if(DB::table('spad_handover_photos')->where('checklist_id', $row->id)->count() >= 24, 422, 'حداکثر ۲۴ عکس در هر پرونده مجاز است.');
        $file = $input['photo'];
        $path = $file->store('private/handover/'.$row->id, 'local');
        abort_unless($path, 503, 'ذخیره امن عکس انجام نشد.');
        try {
            $photoId = DB::table('spad_handover_photos')->insertGetId([
                'checklist_id' => $row->id, 'user_id' => $row->user_id,
                'item_key' => $input['item_key'] ?? null, 'path' => $path,
                'original_name' => mb_substr($file->getClientOriginalName(), 0, 160),
                'mime' => $file->getMimeType(), 'bytes' => $file->getSize(),
                'created_at' => now(), 'updated_at' => now()
            ]);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }
        return response()->json(['data' => ['id' => $photoId]], 201);
    }

    private function ownedPhoto(Request $request, $id, $photoId)
    {
        $row = $this->owned($request, $id);
        $photo = DB::table('spad_handover_photos')
            ->where('checklist_id', $row->id)->where('user_id', $row->user_id)
            ->where('id', $photoId)->first();
        abort_unless($photo, 404, 'عکس در این پرونده یافت نشد.');
        return [$row, $photo];
    }

    public function photo(Request $request, $id, $photoId)
    {
        $this->ready();
        list($row, $photo) = $this->ownedPhoto($request, $id, $photoId);
        abort_unless(Storage::disk('local')->exists($photo->path), 404);
        return response()->file(Storage::disk('local')->path($photo->path), [
            'Content-Type' => $photo->mime, 'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff'
        ]);
    }

    public function removePhoto(Request $request, $id, $photoId)
    {
        $this->ready();
        list($row, $photo) = $this->ownedPhoto($request, $id, $photoId);
        abort_if($row->status !== 'draft', 422, 'عکس گزارش نهایی‌شده قابل حذف نیست.');
        DB::table('spad_handover_photos')->where('id', $photo->id)->delete();
        Storage::disk('local')->delete($photo->path);
        return response()->json(['message' => 'عکس از پرونده حذف شد.']);
    }
}
