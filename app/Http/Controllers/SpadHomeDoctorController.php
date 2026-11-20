<?php

namespace App\Http\Controllers;

use App\Jobs\AssessSpadHomeDoctorCase;
use App\Models\SpadHomeDoctorCase;
use App\Models\User;
use App\Services\HomeDoctor\Catalog;
use App\Services\HomeDoctor\PrivatePhoto;
use App\Services\HomeDoctor\Safety;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SpadHomeDoctorController extends Controller
{
    public function categories()
    {
        return response()->json(['data' => array_values(Catalog::enabled())]);
    }

    public function index(Request $request)
    {
        $owner = $this->user($request);
        $query = SpadHomeDoctorCase::where('user_id', $owner->getKey());
        $this->expireAttempts(clone $query);
        $list = $query->latest()->orderByDesc('id')->paginate(12);
        return $this->json($list->getCollection()->map(function ($case) {
            return $this->summary($case);
        })->values(), 200, ['page' => $list->currentPage(), 'last_page' => $list->lastPage()]);
    }

    public function store(Request $request)
    {
        $user = $this->user($request);
        $data = $request->validate([
            'category' => ['required', Rule::in(array_keys(Catalog::enabled()))],
            'description' => ['required', 'string', 'min:8', 'max:1400'],
        ], [
            'category.*' => 'دستهٔ مشکل را انتخاب کنید.',
            'description.*' => 'شرح مشکل باید بین ۸ تا ۱۴۰۰ نویسه باشد.',
        ]);
        $referral = Safety::referral($data['description']);
        $case = SpadHomeDoctorCase::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->getKey(),
            'category' => $data['category'],
            'description' => $data['description'],
            'status' => $referral ? 'referral' : 'draft',
            'result' => $referral,
        ]);
        return $this->json($this->detail($case), 201);
    }

    public function show(Request $request, string $case)
    {
        $record = $this->owned($request, $case);
        $this->expireAttempts(SpadHomeDoctorCase::whereKey($record->id));
        $this->expireAttempts($record->followUps());
        return $this->json($this->detail($record->refresh()));
    }

    public function upload(Request $request, string $case)
    {
        // Check ownership before processing or saving any server-side photo.
        $record = $this->owned($request, $case);
        abort_unless(in_array($record->status, ['draft', 'unavailable'], true), 409, 'عکس‌های این درخواست دیگر قابل تغییر نیستند.');
        $data = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ], ['image.*' => 'عکس JPG، PNG یا WebP با حجم حداکثر ۸ مگابایت انتخاب کنید.']);
        [$content, $width, $height] = PrivatePhoto::process($data['image']);
        $id = (string) Str::uuid();
        $path = 'home-doctor/' . $record->id . '/' . $id . '.jpg';

        try {
            $updated = DB::transaction(function () use ($request, $case, $content, $width, $height, $id, $path) {
                $record = $this->owned($request, $case, true);
                abort_unless(in_array($record->status, ['draft', 'unavailable'], true), 409, 'عکس‌های این درخواست دیگر قابل تغییر نیستند.');
                abort_if($record->media()->count() >= 3, 422, 'برای هر درخواست می‌توانید حداکثر ۳ عکس ثبت کنید.');
                abort_unless(PrivatePhoto::disk()->put($path, $content), 503, 'ذخیرهٔ عکس انجام نشد.');
                $record->media()->create(compact('id', 'path', 'width', 'height'));
                return $record;
            });
        } catch (\Throwable $error) {
            PrivatePhoto::disk()->delete($path);
            throw $error;
        }

        return $this->json($this->detail($updated), 201);
    }

    public function photo(Request $request, string $case, string $media)
    {
        $record = $this->owned($request, $case);
        $photo = $record->media()->whereKey($media)->firstOrFail();
        $disk = PrivatePhoto::disk();
        abort_unless($disk->exists($photo->path), 404);
        return response($disk->get($photo->path), 200, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function removePhoto(Request $request, string $case, string $media)
    {
        $record = DB::transaction(function () use ($request, $case, $media) {
            $record = $this->owned($request, $case, true);
            abort_unless(in_array($record->status, ['draft', 'unavailable'], true), 409, 'عکس‌های این درخواست دیگر قابل تغییر نیستند.');
            $photo = $record->media()->whereKey($media)->firstOrFail();
            $disk = PrivatePhoto::disk();
            abort_if($disk->exists($photo->path) && !$disk->delete($photo->path), 503, 'حذف عکس انجام نشد. دوباره تلاش کنید.');
            $photo->delete();
            return $record;
        });
        return $this->json($this->detail($record));
    }

    public function analyze(Request $request, string $case)
    {
        $data = $request->validate([
            'consent' => ['accepted'],
            'answers' => ['nullable', 'array:symptom,location'],
            'answers.*' => ['nullable', 'string', 'max:420'],
        ], [
            'consent.accepted' => 'برای ارسال به سرویس تحلیل هوشمند باید رضایت خود را ثبت کنید.',
            'answers.*' => 'هر پاسخ باید حداکثر ۴۲۰ نویسه باشد.',
        ]);
        $answers = array_filter($data['answers'] ?? [], 'is_string');
        $attempt = null;
        $record = DB::transaction(function () use ($request, $case, $answers, &$attempt) {
            $record = $this->owned($request, $case, true);
            if (!in_array($record->status, ['draft', 'unavailable'], true)) {
                return $record;
            }

            $referral = Safety::referral(implode(' ', array_merge([$record->description], $answers)));
            if (!$referral) {
                $this->consumeQuota($record);
                $attempt = (string) Str::uuid();
            }
            $record->update([
                'answers' => $answers,
                'status' => $referral ? 'referral' : 'queued',
                'result' => $referral,
                'analysis_attempt' => $attempt,
                'consented_at' => $referral ? null : now(),
                'analysis_started_at' => null,
            ]);
            return $record;
        });

        if ($attempt !== null) {
            $this->dispatch($record, $attempt);
        }
        return $this->json($this->detail($record->refresh()));
    }

    public function followUp(Request $request, string $case)
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'min:3', 'max:700'],
            'consent' => ['accepted'],
        ], [
            'question.*' => 'سؤال پیگیری باید بین ۳ تا ۷۰۰ نویسه باشد.',
            'consent.accepted' => 'برای ارسال به سرویس تحلیل هوشمند باید رضایت خود را ثبت کنید.',
        ]);
        [$record, $followUp] = DB::transaction(function () use ($request, $case, $data) {
            $record = $this->owned($request, $case, true);
            abort_unless($record->status === 'analyzed', 409, 'پس از تکمیل تحلیل می‌توانید سؤال پیگیری ثبت کنید.');
            $count = $record->followUps()->count();
            abort_if($count >= 10, 422, 'سقف تعداد پرسش‌های این پرونده تکمیل شده است.');
            abort_if($record->followUps()->where('status', 'referral')->exists(), 409, 'ابتدا راهنمای اقدام فوری را دنبال کنید و برای بازدید حضوری کمک بگیرید.');
            abort_if($record->followUps()->whereIn('status', ['queued', 'analyzing'])->exists(), 409, 'پاسخ سؤال قبلی هنوز آماده نشده است.');

            $referral = Safety::referral($data['question']);
            if (!$referral) {
                $this->consumeQuota($record);
            }
            return [$record, $record->followUps()->create([
                'id' => (string) Str::uuid(),
                'sequence' => $count + 1,
                'question' => $data['question'],
                'status' => $referral ? 'referral' : 'queued',
                'result' => $referral,
                'consented_at' => now(),
            ])];
        });
        if ($followUp->status === 'queued') {
            $this->dispatch($record, $followUp->id, $followUp->id);
        }
        return $this->json($this->detail($record->refresh()), 201);
    }

    public function feedback(Request $request, string $case)
    {
        $record = $this->owned($request, $case);
        abort_unless(in_array($record->status, ['analyzed', 'referral'], true), 409, 'نتیجهٔ این درخواست هنوز آماده نیست.');
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:500'],
        ], ['rating.*' => 'امتیاز باید بین یک تا پنج باشد.', 'comment.*' => 'نظر باید حداکثر ۵۰۰ نویسه باشد.']);
        $record->update(['feedback_rating' => $data['rating'], 'feedback_comment' => $data['comment'] ?? null]);
        return $this->json($this->detail($record));
    }

    public function destroy(Request $request, string $case)
    {
        DB::transaction(function () use ($request, $case) {
            $record = $this->owned($request, $case, true);
            $disk = PrivatePhoto::disk();
            foreach ($record->media()->pluck('path') as $path) {
                abort_if($disk->exists($path) && !$disk->delete($path), 503, 'حذف عکس‌‌های درخواست انجام نشد. دوباره تلاش کنید.');
            }
            $record->delete();
        });

        return response()->noContent()->header('Cache-Control', 'private, no-store');
    }

    private function dispatch(SpadHomeDoctorCase $record, string $attempt, ?string $followUp = null): void
    {
        $job = new AssessSpadHomeDoctorCase($record->id, $attempt, $followUp);
        try {
            Bus::dispatch($job);
        } catch (\Throwable $error) {
            $job->failed($error);
            Log::warning('Home Doctor could not queue request', ['class' => get_class($error)]);
        }
    }

    private function consumeQuota(SpadHomeDoctorCase $record): void
    {
        $key = 'spad-home-doctor:ai:' . $record->user_id;
        abort_if(RateLimiter::tooManyAttempts($key, 10), 429, 'ظرفیت تحلیل حساب شما فعلاً تکمیل شده است.');
        RateLimiter::hit($key, 3600);
    }

    private function expireAttempts($query): void
    {
        (clone $query)->where('status', 'queued')->where('updated_at', '<', now()->subMinutes(20))->update(['status' => 'unavailable']);
        $query->where('status', 'analyzing')->where('analysis_started_at', '<', now()->subMinutes(3))->update(['status' => 'unavailable']);
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        return $user;
    }

    private function owned(Request $request, string $id, bool $lock = false): SpadHomeDoctorCase
    {
        abort_unless(preg_match('/\A[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}\z/i', $id) === 1, 404);
        $query = SpadHomeDoctorCase::where('user_id', $this->user($request)->getKey());
        return $lock ? $query->lockForUpdate()->findOrFail($id) : $query->findOrFail($id);
    }

    private function summary(SpadHomeDoctorCase $case): array
    {
        return [
            'id' => $case->id,
            'category' => $case->category,
            'status' => $case->status,
            'description' => $case->description,
            'created_at' => $case->created_at->toISOString(),
            'updated_at' => $case->updated_at->toISOString(),
        ];
    }

    private function detail(SpadHomeDoctorCase $case): array
    {
        return $this->summary($case) + [
            'questions' => Catalog::defaults()[$case->category]['questions'] ?? [],
            'answers' => $case->answers ?: (object) [],
            'result' => $case->result,
            'media' => $case->media()->oldest()->get(['id', 'width', 'height'])->toArray(),
            'follow_ups' => $case->followUps()->orderBy('sequence')->get(['id', 'question', 'status', 'result', 'created_at'])->toArray(),
            'feedback' => $case->feedback_rating === null ? null : [
                'rating' => (int) $case->feedback_rating, 'comment' => $case->feedback_comment,
            ],
        ];
    }

    private function json($data, int $status = 200, ?array $meta = null)
    {
        return response()->json(['data' => $data] + ($meta ? ['meta' => $meta] : []), $status)
            ->header('Cache-Control', 'private, no-store');
    }
}
