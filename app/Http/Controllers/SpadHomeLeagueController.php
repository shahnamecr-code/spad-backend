<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\User;
use App\Services\SpadHomeLeague\LeaguePhotos;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Throwable;

class SpadHomeLeagueController extends Controller
{
    private const TIMEZONE = 'Asia/Tehran';
    private const CONTESTS = 'spad_home_league_contests';
    private const ENTRIES = 'spad_home_league_entries';
    private const PHOTOS = 'spad_home_league_photos';
    private const VOTES = 'spad_home_league_votes';
    private const REPORTS = 'spad_home_league_reports';
    private const RESULTS = 'spad_home_league_results';
    private const AUDIT = 'spad_home_league_audit';

    private $images;

    public function __construct(LeaguePhotos $images)
    {
        $this->images = $images;
    }

    public function index()
    {
        abort_unless(Schema::hasTable(self::CONTESTS), 503, 'لیگ خانه در حال راه‌اندازی است.');
        $now = $this->now();
        $contests = DB::table(self::CONTESTS)->where('published', true)
            ->orderByDesc('submission_starts_at')->paginate(24);
        return response()->json([
            'data' => $contests->getCollection()->map(function ($contest) use ($now) {
                return $this->contestData($contest, $now);
            })->values(),
            'has_more' => $contests->hasMorePages(),
            'server_now' => $now->toIso8601String(),
        ])->header('Cache-Control', 'no-store');
    }

    public function show(string $slug)
    {
        $contest = $this->contest($slug);
        $now = $this->now();
        $entries = $this->publicEntries($contest)->paginate(16);
        $rows = $entries->getCollection();
        $photos = $this->photoMap($rows->pluck('id')->all());
        $results = DB::table(self::RESULTS . ' as result')->join(self::ENTRIES . ' as entry', 'entry.id', '=', 'result.entry_id')
            ->where('result.contest_id', $contest->id)->where('result.place', '<=', 3)
            ->orderBy('result.place')->orderBy('result.entry_id')->limit(48)
            ->get(['result.entry_id', 'result.place', 'result.vote_count', 'result.badge', 'entry.title']);

        return response()->json(['data' => [
            'contest' => $this->contestData($contest, $now),
            'entries' => $rows->map(function ($entry) use ($contest, $photos) {
                return $this->entryData($entry, $contest, $photos->get($entry->id));
            })->values(),
            'results' => $results,
            'has_more' => $entries->hasMorePages(),
            'server_now' => $now->toIso8601String(),
        ]])->header('Cache-Control', 'no-store');
    }

    public function entry(string $slug, int $id)
    {
        $contest = $this->contest($slug);
        $entry = $this->publicEntries($contest)->where('entry.id', $id)->first();
        abort_unless($entry, 404);
        $photos = $this->photoMap([$entry->id]);
        return response()->json(['data' => [
            'contest' => $this->contestData($contest, $this->now()),
            'entry' => $this->entryData($entry, $contest, $photos->get($entry->id)),
            'server_now' => $this->now()->toIso8601String(),
        ]])->header('Cache-Control', 'no-store');
    }

    public function photo(string $slug, int $id, int $photo)
    {
        $contest = $this->contest($slug);
        $entry = $this->contestEntry($contest, $id);
        abort_unless($entry->status === 'approved', 404);
        return $this->entryPhoto($id, $photo);
    }

    public function myEntries(Request $request)
    {
        $userId = $this->userId($request);
        $entries = DB::table(self::ENTRIES . ' as entry')->join(self::CONTESTS . ' as contest', 'contest.id', '=', 'entry.contest_id')
            ->where('entry.user_id', $userId)->select('entry.*', 'contest.slug', 'contest.title as contest_title')
            ->orderByDesc('entry.id')->paginate(24);
        $rows = $entries->getCollection();
        $photos = $this->photoMap($rows->pluck('id')->all());
        return response()->json([
            'data' => $rows->map(function ($entry) use ($photos) {
                return $this->entryData($entry, $entry, $photos->get($entry->id), 'owner')
                    + ['contest_slug' => $entry->slug, 'contest_title' => $entry->contest_title];
            })->values(),
            'has_more' => $entries->hasMorePages(),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function myVote(Request $request, string $slug)
    {
        $userId = $this->userId($request);
        $contest = $this->contest($slug);
        $vote = DB::table(self::VOTES)->where([
            'contest_id' => $contest->id, 'user_id' => $userId, 'vote_date' => $this->now()->toDateString(),
        ])->first(['entry_id', 'valid']);
        return response()->json(['data' => $vote])->header('Cache-Control', 'private, no-store');
    }

    public function myEntry(Request $request, int $id)
    {
        $entry = $this->entryById($id);
        abort_unless((int) $entry->user_id === $this->userId($request), 404);
        $contest = $this->contestById($entry->contest_id);
        return response()->json(['data' => [
            'contest' => $this->contestData($contest, $this->now()),
            'entry' => $this->entryData($entry, $contest, $this->photoMap([$id])->get($id), 'owner'),
        ]])->header('Cache-Control', 'private, no-store');
    }

    public function myPhoto(Request $request, int $id, int $photo)
    {
        $entry = $this->entryById($id);
        abort_unless((int) $entry->user_id === $this->userId($request), 404);
        return $this->entryPhoto($id, $photo);
    }

    public function submit(Request $request, string $slug)
    {
        return $this->saveEntry($request, $slug, null);
    }

    public function edit(Request $request, string $slug, int $id)
    {
        return $this->saveEntry($request, $slug, $id);
    }

    private function saveEntry(Request $request, string $slug, ?int $id)
    {
        $userId = $this->userId($request);
        $contest = $this->contest($slug);
        abort_unless($this->contestData($contest, $this->now())['submission_open'], 422, 'مهلت ثبت و ویرایش آثار باز نیست.');
        if ($id !== null) {
            $this->editableEntry($contest, $id, $userId);
        }

        $data = $request->validate([
            'title' => 'required|string|min:3|max:150',
            'description' => 'required|string|min:8|max:2500',
            'province' => 'required|string|max:85',
            'city' => 'nullable|string|max:85',
            'ai_disclosed' => 'required|boolean',
            'ownership_confirmed' => 'accepted',
            'publication_confirmed' => 'accepted',
            'photos' => ($id === null ? 'required' : 'sometimes') . '|array|min:1|max:5',
            'photos.*' => 'required|file|max:8192',
        ]);
        $paths = $this->images->store($request->file('photos', []));

        try {
            [$entryId, $oldPaths] = DB::transaction(function () use ($contest, $id, $userId, $data, $paths) {
                $locked = $this->contestById($contest->id, true);
                $now = $this->now();
                abort_unless($this->contestData($locked, $now)['submission_open'], 422, 'مهلت ثبت و ویرایش آثار باز نیست.');
                $values = [
                    'title' => $data['title'], 'description' => $data['description'],
                    'province' => $data['province'], 'city' => $data['city'] ?? null,
                    'ai_disclosed' => (bool) $data['ai_disclosed'], 'attested_at' => $now,
                    'status' => 'pending', 'review_reason' => null, 'reviewed_by' => null, 'reviewed_at' => null,
                    'updated_at' => $now,
                ];
                $oldPaths = [];
                if ($id === null) {
                    $entryId = DB::table(self::ENTRIES)->insertGetId($values + [
                        'contest_id' => $contest->id, 'user_id' => $userId, 'created_at' => $now,
                    ]);
                } else {
                    $previousEntry = $this->editableEntry($locked, $id, $userId);
                    $entryId = $id;
                    DB::table(self::ENTRIES)->where('id', $entryId)->update($values + ['revision' => (int) $previousEntry->revision + 1]);
                    if ($paths) {
                        $oldPaths = DB::table(self::PHOTOS)->where('entry_id', $entryId)->pluck('path')->all();
                        DB::table(self::PHOTOS)->where('entry_id', $entryId)->delete();
                    }
                }

                foreach ($paths as $position => $path) {
                    DB::table(self::PHOTOS)->insert(['entry_id' => $entryId, 'path' => $path, 'position' => $position]);
                }
                return [$entryId, $oldPaths];
            }, 3);
        } catch (Throwable $error) {
            $this->images->delete($paths);
            throw $error;
        }

        $this->images->delete($oldPaths);
        return response()->json([
            'data' => ['id' => $entryId, 'status' => 'pending'],
            'message' => 'اثر برای بررسی ثبت شد.',
        ], $id === null ? 201 : 200);
    }

    public function withdraw(Request $request, string $slug, int $id)
    {
        $userId = $this->userId($request);
        DB::transaction(function () use ($slug, $id, $userId) {
            $contest = $this->contest($slug, true, false);
            $entry = $this->contestEntry($contest, $id);
            abort_unless((int) $entry->user_id === $userId, 404);
            abort_if($contest->frozen_at, 422, 'نتیجهٔ مسابقه نهایی شده است.');
            DB::table(self::ENTRIES)->where('id', $id)->update(['status' => 'withdrawn', 'updated_at' => $this->now()]);
        }, 3);
        return response()->json(['message' => 'اثر از مسابقه خارج شد.']);
    }

    public function vote(Request $request, string $slug, int $id)
    {
        $userId = $this->userId($request);
        DB::transaction(function () use ($slug, $id, $userId) {
            $contest = $this->contest($slug, true);
            $now = $this->now();
            abort_unless($this->contestData($contest, $now)['voting_open'], 422, 'رأی‌گیری فعال نیست.');
            $entry = $this->contestEntry($contest, $id);
            abort_unless($entry->status === 'approved', 404);
            $lookup = ['contest_id' => $contest->id, 'user_id' => $userId, 'vote_date' => $now->toDateString()];
            abort_if(DB::table(self::VOTES)->where($lookup)->exists(), 409, 'امروز در این مسابقه رأی داده‌اید.');
            DB::table(self::VOTES)->insert($lookup + [
                'entry_id' => $id, 'valid' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }, 3);
        return response()->json(['message' => 'رأی ثبت شد.'], 201);
    }

    public function report(Request $request, string $slug, int $id)
    {
        $userId = $this->userId($request);
        $data = $request->validate(['reason' => 'required|string|min:5|max:1000']);
        DB::transaction(function () use ($slug, $id, $userId, $data) {
            $contest = $this->contest($slug, true);
            $entry = $this->contestEntry($contest, $id);
            abort_unless($entry->status === 'approved', 404);
            $lookup = ['entry_id' => $id, 'user_id' => $userId];
            abort_if(DB::table(self::REPORTS)->where($lookup)->exists(), 409, 'گزارش این اثر قبلاً ثبت شده است.');
            $now = $this->now();
            DB::table(self::REPORTS)->insert($lookup + [
                'reason' => $data['reason'], 'status' => 'pending', 'created_at' => $now, 'updated_at' => $now,
            ]);
        }, 3);
        return response()->json(['message' => 'گزارش برای بررسی ثبت شد.'], 201);
    }

    public function adminContests(Request $request)
    {
        $this->adminId($request);
        $now = $this->now();
        $data = DB::table(self::CONTESTS)->orderByDesc('id')->paginate(40);
        return response()->json([
            'data' => $data->getCollection()->map(function ($contest) use ($now) { return $this->contestData($contest, $now); }),
            'has_more' => $data->hasMorePages(),
        ]);
    }

    public function adminCreate(Request $request)
    {
        $adminId = $this->adminId($request);
        $values = $this->contestValues($request);
        $id = DB::transaction(function () use ($adminId, $values) {
            $now = $this->now();
            $id = DB::table(self::CONTESTS)->insertGetId($values + ['created_at' => $now, 'updated_at' => $now]);
            $this->audit($id, $adminId, 'contest_created', $id);
            return $id;
        }, 3);
        return response()->json(['data' => $this->contestData($this->contestById($id), $this->now())], 201);
    }

    public function adminUpdate(Request $request, int $id)
    {
        $adminId = $this->adminId($request);
        $values = $this->contestValues($request, $id);
        DB::transaction(function () use ($values, $id, $adminId) {
            $contest = $this->contestById($id, true);
            abort_if($contest->frozen_at, 422, 'این مسابقه نتیجهٔ نهایی دارد.');
            DB::table(self::CONTESTS)->where('id', $id)->update($values + ['updated_at' => $this->now()]);
            $this->audit($id, $adminId, 'contest_updated', $id);
        }, 3);
        return response()->json(['data' => $this->contestData($this->contestById($id), $this->now())]);
    }

    public function adminReview(Request $request, int $id)
    {
        $this->adminId($request);
        $contest = $this->contestById($id);
        $entryPager = $this->countedEntries($contest)->orderByRaw("case when entry.status = 'pending' then 0 else 1 end")
            ->orderByDesc('entry.id')->paginate(100, ['*'], 'entries_page');
        $rows = $entryPager->getCollection();
        $photos = $this->photoMap($rows->pluck('id')->all());
        $votePager = DB::table(self::VOTES)->where('contest_id', $id)->orderByDesc('id')->paginate(100, ['*'], 'votes_page');
        $reportPager = DB::table(self::REPORTS . ' as report')->join(self::ENTRIES . ' as entry', 'entry.id', '=', 'report.entry_id')
            ->where('entry.contest_id', $id)->select('report.*')->orderByRaw("case when report.status = 'pending' then 0 else 1 end")
            ->orderByDesc('report.id')->paginate(100, ['*'], 'reports_page');

        return response()->json(['data' => [
            'contest' => $this->contestData($contest, $this->now()),
            'entries' => $rows->map(function ($entry) use ($contest, $photos) {
                return $this->entryData($entry, $contest, $photos->get($entry->id), 'admin');
            })->values(),
            'votes' => $votePager->items(),
            'reports' => $reportPager->items(),
            'audit' => DB::table(self::AUDIT)->where('contest_id', $id)->orderByDesc('id')->limit(100)->get(),
            'entries_has_more' => $entryPager->hasMorePages(),
            'votes_has_more' => $votePager->hasMorePages(),
            'reports_has_more' => $reportPager->hasMorePages(),
        ]])->header('Cache-Control', 'private, no-store');
    }

    public function adminPhoto(Request $request, int $id, int $photo)
    {
        $this->adminId($request);
        return $this->entryPhoto($id, $photo);
    }

    public function adminModerate(Request $request, int $id)
    {
        $adminId = $this->adminId($request);
        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
            'reason' => 'required_if:status,rejected|nullable|string|max:1000',
            'revision' => 'required|integer|min:1',
        ]);
        $entry = $this->entryById($id);
        DB::transaction(function () use ($entry, $data, $adminId) {
            $contest = $this->contestById($entry->contest_id, true);
            $fresh = $this->contestEntry($contest, $entry->id);
            abort_if($contest->frozen_at || $fresh->status === 'withdrawn', 422, 'تغییر این اثر ممکن نیست.');
            abort_unless((int) $fresh->revision === (int) $data['revision'], 409, 'اثر پس از باز کردن بررسی تغییر کرده است. نسخهٔ تازه را بررسی کنید.');
            abort_if($data['status'] === 'approved' && !DB::table(self::PHOTOS)->where('entry_id', $entry->id)->exists(), 422, 'اثر بدون تصویر قابل تأیید نیست.');
            $now = $this->now();
            DB::table(self::ENTRIES)->where('id', $entry->id)->update([
                'status' => $data['status'], 'review_reason' => $data['reason'] ?? null,
                'reviewed_by' => $adminId, 'reviewed_at' => $now, 'updated_at' => $now,
            ]);
            $this->audit($contest->id, $adminId, 'entry_' . $data['status'], $entry->id, $data['reason'] ?? null);
        }, 3);
        return response()->json(['message' => 'وضعیت اثر ذخیره شد.']);
    }

    public function adminInvalidate(Request $request, int $id)
    {
        $adminId = $this->adminId($request);
        $data = $request->validate(['reason' => 'required|string|min:5|max:1000']);
        $vote = DB::table(self::VOTES)->where('id', $id)->first();
        abort_unless($vote, 404);
        DB::transaction(function () use ($adminId, $data, $vote) {
            $contest = $this->contestById($vote->contest_id, true);
            abort_if($contest->frozen_at, 422, 'نتیجهٔ مسابقه نهایی شده است.');
            $now = $this->now();
            $changed = DB::table(self::VOTES)->where('id', $vote->id)->where('valid', true)->update([
                'valid' => false, 'invalidated_at' => $now, 'invalidated_by' => $adminId,
                'invalidation_reason' => $data['reason'], 'updated_at' => $now,
            ]);
            abort_unless($changed, 409, 'این رأی قبلاً باطل شده است.');
            $this->audit($contest->id, $adminId, 'vote_invalidated', $vote->id, $data['reason']);
        }, 3);
        return response()->json(['message' => 'رأی باطل شد.']);
    }

    public function adminResolveReport(Request $request, int $id)
    {
        $adminId = $this->adminId($request);
        $data = $request->validate([
            'status' => ['required', Rule::in(['resolved', 'dismissed'])],
            'reason' => 'nullable|string|max:1000',
        ]);
        $report = DB::table(self::REPORTS)->where('id', $id)->first();
        abort_unless($report, 404);
        $entry = $this->entryById($report->entry_id);
        DB::transaction(function () use ($id, $data, $entry, $adminId) {
            $now = $this->now();
            $changed = DB::table(self::REPORTS)->where('id', $id)->where('status', 'pending')->update([
                'status' => $data['status'], 'resolution' => $data['reason'] ?? null,
                'resolved_by' => $adminId, 'resolved_at' => $now, 'updated_at' => $now,
            ]);
            abort_unless($changed, 409, 'این گزارش قبلاً بررسی شده است.');
            $this->audit($entry->contest_id, $adminId, 'report_' . $data['status'], $id, $data['reason'] ?? null);
        }, 3);
        return response()->json(['message' => 'بررسی گزارش ذخیره شد.']);
    }

    public function adminFreeze(Request $request, int $id)
    {
        $adminId = $this->adminId($request);
        $count = DB::transaction(function () use ($id, $adminId) {
            $contest = $this->contestById($id, true);
            $now = $this->now();
            abort_if($contest->frozen_at, 409, 'نتیجه قبلاً نهایی شده است.');
            abort_unless($this->contestData($contest, $now)['status'] === 'finished', 422, 'تا پایان مهلت ثبت آثار و رأی‌گیری صبر کنید.');

            $rows = $this->publicEntries($contest)->where('counts.vote_count', '>', 0)->get();
            $previous = null;
            $place = 0;
            foreach ($rows as $index => $entry) {
                $votes = (int) $entry->vote_count;
                if ($votes !== $previous) {
                    $place = $index + 1;
                    $previous = $votes;
                }
                DB::table(self::RESULTS)->insert([
                    'contest_id' => $id, 'entry_id' => $entry->id, 'place' => $place, 'vote_count' => $votes,
                    'badge' => [1 => 'gold', 2 => 'silver', 3 => 'bronze'][$place] ?? null,
                ]);
            }
            DB::table(self::CONTESTS)->where('id', $id)->update(['frozen_at' => $now, 'updated_at' => $now]);
            $this->audit($id, $adminId, 'result_frozen', $id);
            return $rows->count();
        }, 3);
        return response()->json(['data' => ['ranked_count' => $count], 'message' => 'نتیجه بر اساس رأی‌های معتبر نهایی شد.']);
    }

    private function contestValues(Request $request, ?int $id = null): array
    {
        $slug = Rule::unique(self::CONTESTS, 'slug');
        if ($id !== null) {
            $slug->ignore($id);
        }
        $data = $request->validate([
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::notIn(['my-entries']), $slug],
            'title' => 'required|string|min:3|max:180',
            'theme' => 'required|string|min:2|max:130',
            'description' => 'required|string|max:5000',
            'rules' => 'required|string|max:12000',
            'published' => 'required|boolean',
            'submission_starts_at' => 'required|date',
            'submission_ends_at' => 'required|date|after:submission_starts_at',
            'voting_starts_at' => 'required|date',
            'voting_ends_at' => 'required|date|after:voting_starts_at',
        ]);
        foreach (['submission_starts_at', 'submission_ends_at', 'voting_starts_at', 'voting_ends_at'] as $field) {
            $data[$field] = $this->date($data[$field])->toDateTimeString();
        }
        return $data;
    }

    private function contestData($contest, Carbon $now): array
    {
        $submissionStarts = $this->date($contest->submission_starts_at);
        $submissionEnds = $this->date($contest->submission_ends_at);
        $votingStarts = $this->date($contest->voting_starts_at);
        $votingEnds = $this->date($contest->voting_ends_at);
        $open = (bool) $contest->published && !$contest->frozen_at;
        $status = $contest->frozen_at || ($now->gte($submissionEnds) && $now->gte($votingEnds))
            ? 'finished'
            : ($now->lt($submissionStarts) && $now->lt($votingStarts) ? 'upcoming' : 'active');

        return [
            'id' => (int) $contest->id, 'slug' => $contest->slug, 'title' => $contest->title, 'theme' => $contest->theme,
            'description' => $contest->description, 'rules' => $contest->rules, 'published' => (bool) $contest->published,
            'submission_starts_at' => $submissionStarts->toIso8601String(),
            'submission_ends_at' => $submissionEnds->toIso8601String(),
            'voting_starts_at' => $votingStarts->toIso8601String(), 'voting_ends_at' => $votingEnds->toIso8601String(),
            'status' => $status,
            'submission_open' => $open && $now->gte($submissionStarts) && $now->lt($submissionEnds),
            'voting_open' => $open && $now->gte($votingStarts) && $now->lt($votingEnds),
            'frozen_at' => $contest->frozen_at ? $this->date($contest->frozen_at)->toIso8601String() : null,
        ];
    }

    private function entryData($entry, $contest, $photos, string $scope = 'public'): array
    {
        $data = [
            'id' => (int) $entry->id, 'contest_id' => (int) $entry->contest_id, 'title' => $entry->title,
            'description' => $entry->description, 'province' => $entry->province, 'city' => $entry->city,
            'ai_disclosed' => (bool) $entry->ai_disclosed, 'vote_count' => (int) ($entry->vote_count ?? 0),
            'place' => isset($entry->place) ? (int) $entry->place : null, 'badge' => $entry->badge ?? null,
            'photos' => collect($photos)->map(function ($photo) use ($entry, $contest, $scope) {
                $base = 'api/home-league/' . $contest->slug . '/entries/' . $entry->id;
                if ($scope === 'admin') {
                    $base = 'api/admin/home-league/entries/' . $entry->id;
                } elseif ($scope === 'owner') {
                    $base = 'api/home-league/my-entries/' . $entry->id;
                }
                return [
                    'id' => (int) $photo->id,
                    'url' => url($base . '/photos/' . $photo->id),
                ];
            })->values(),
        ];
        if ($scope !== 'public') {
            $data += [
                'status' => $entry->status, 'review_reason' => $entry->review_reason,
                'created_at' => $this->date($entry->created_at)->toIso8601String(),
            ];
        }
        if ($scope === 'admin') {
            $data['user_id'] = (int) $entry->user_id;
            $data['revision'] = (int) $entry->revision;
        }
        return $data;
    }

    private function countedEntries($contest)
    {
        $counts = DB::table(self::VOTES)->where('contest_id', $contest->id)->where('valid', true)
            ->selectRaw('entry_id, COUNT(*) AS vote_count')->groupBy('entry_id');
        return DB::table(self::ENTRIES . ' as entry')->where('entry.contest_id', $contest->id)
            ->leftJoinSub($counts, 'counts', function ($join) { $join->on('counts.entry_id', '=', 'entry.id'); })
            ->leftJoin(self::RESULTS . ' as result', 'result.entry_id', '=', 'entry.id')
            ->select(['entry.*', 'result.place', 'result.badge'])
            ->selectRaw('COALESCE(counts.vote_count, 0) AS vote_count');
    }

    private function publicEntries($contest)
    {
        return $this->countedEntries($contest)->where('entry.status', 'approved')
            ->orderByDesc('vote_count')->orderBy('entry.id');
    }

    private function photoMap(array $entryIds)
    {
        return DB::table(self::PHOTOS)->whereIn('entry_id', $entryIds)->orderBy('position')->get()->groupBy('entry_id');
    }

    private function entryPhoto(int $id, int $photo)
    {
        $record = DB::table(self::PHOTOS)->where('entry_id', $id)->where('id', $photo)->first();
        abort_unless($record, 404);
        return $this->images->response($record->path);
    }

    private function editableEntry($contest, int $id, int $userId)
    {
        $entry = $this->contestEntry($contest, $id);
        abort_unless((int) $entry->user_id === $userId, 404);
        abort_if($entry->status === 'withdrawn', 422, 'اثر از مسابقه خارج شده است.');
        abort_if(DB::table(self::VOTES)->where('entry_id', $id)->exists(), 422, 'اثری که رأی دریافت کرده قابل ویرایش نیست.');
        return $entry;
    }

    private function contest(string $slug, bool $lock = false, bool $publishedOnly = true)
    {
        abort_unless(Schema::hasTable(self::CONTESTS), 503, 'لیگ خانه در حال راه‌اندازی است.');
        $query = DB::table(self::CONTESTS)->where('slug', $slug);
        if ($publishedOnly) {
            $query->where('published', true);
        }
        $contest = ($lock ? $query->lockForUpdate() : $query)->first();
        abort_unless($contest, 404);
        return $contest;
    }

    private function contestById(int $id, bool $lock = false)
    {
        $query = DB::table(self::CONTESTS)->where('id', $id);
        $contest = ($lock ? $query->lockForUpdate() : $query)->first();
        abort_unless($contest, 404);
        return $contest;
    }

    private function entryById(int $id)
    {
        $entry = DB::table(self::ENTRIES)->where('id', $id)->first();
        abort_unless($entry, 404);
        return $entry;
    }

    private function contestEntry($contest, int $id)
    {
        $entry = DB::table(self::ENTRIES)->where('contest_id', $contest->id)->where('id', $id)->first();
        abort_unless($entry, 404);
        return $entry;
    }

    private function publicUser($user): ?int
    {
        if (!$user instanceof User) {
            return null;
        }
        $token = $user->currentAccessToken();
        return ($token->name ?? null) === 'partner-token' ? null : (int) $user->id;
    }

    private function userId(Request $request): int
    {
        $id = $this->publicUser($request->user());
        abort_unless($id, 403, 'با حساب عمومی اسپاد وارد شوید.');
        return $id;
    }

    private function adminId(Request $request): int
    {
        $admin = auth('admin')->user() ?: $request->user();
        abort_unless($admin instanceof Admin, 403, 'دسترسی مدیریت لازم است.');
        return (int) $admin->id;
    }

    private function audit(int $contest, int $admin, string $action, int $subject, ?string $reason = null): void
    {
        DB::table(self::AUDIT)->insert([
            'contest_id' => $contest, 'admin_id' => $admin, 'action' => $action,
            'subject_id' => $subject, 'reason' => $reason, 'created_at' => $this->now(),
        ]);
    }

    private function now(): Carbon
    {
        return Carbon::now(self::TIMEZONE);
    }

    private function date(string $value): Carbon
    {
        return Carbon::parse($value, self::TIMEZONE)->setTimezone(self::TIMEZONE);
    }
}
