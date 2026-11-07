<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvestorOpportunityResource;
use App\Models\Advertisement;
use App\Models\InvestorListing;
use App\Support\SpadLocation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InvestorListingController extends Controller
{
    public function index(Request $request)
    {
        $query = InvestorListing::query()->with([
            'advertisement.category', 'advertisement.city', 'advertisement.region',
            'submittedByPanel.user:id,first_name,last_name,mobile'
        ])->latest();

        if ($request->filled('status')) $query->where('status', $request->input('status'));
        if ($request->filled('source')) $query->where('source', $request->input('source'));
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('advertisement_id', $search)
                  ->orWhereHas('advertisement.properties', function ($p) use ($search) {
                      $p->where('property_slug', 'title')->where('text_value', 'like', "%{$search}%");
                  });
            });
        }

        return InvestorOpportunityResource::collection($query->paginate($request->input('per_page', 20)))
            ->additional(['meta' => $this->metaPayload()]);
    }

    /**
     * Only approved, publicly visible properties may receive a special
     * investor price. This endpoint is inside the admin role middleware.
     */
    public function availableProperties(Request $request)
    {
        $data = $this->validate($request, [
            'search' => 'nullable|string|max:80',
        ]);
        $search = trim($data['search'] ?? '');
        $query = Advertisement::query()
            ->publicCatalog()
            ->where('city_id', SpadLocation::cityId())
            ->whereDoesntHave('investorListing')
            ->with(['city', 'region', 'properties' => function ($query) {
                $query->where('property_slug', 'title')
                    ->orWhereHas('property', function ($p) {
                        $p->where('type', 'integer')
                            ->where(function ($price) {
                                $price->where('title', 'like', '%قیمت%')
                                    ->orWhere('title', 'like', '%مبلغ فروش%');
                            });
                    })
                    ->with('property');
            }])
            ->orderByDesc('updated_at');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                if (ctype_digit($search)) $q->orWhere('id', (int)$search);
                $q->orWhereHas('properties', function ($p) use ($search) {
                    $p->where('property_slug', 'title')
                        ->where('text_value', 'like', '%' . $search . '%');
                });
            });
        }
        return response()->json([
            'status' => true,
            'data' => $query->limit(30)->get()->map(function ($ad) {
                $title = optional($ad->properties->firstWhere('property_slug', 'title'))->text_value;
                $priceProperty = $ad->properties->first(function ($p) {
                    $label = $p->property->title ?? '';
                    return $p->property && $p->property->type === 'integer'
                        && (int) $p->integer_value > 0
                        && preg_match('/قیمت|مبلغ فروش/u', $label)
                        && !preg_match('/متر|وام|رهن|اجاره|ودیعه|قسط/u', $label);
                });
                $publicPrice = $priceProperty
                    ? userShowAdvertisementProperty($priceProperty)
                    : null;
                return [
                    'id' => $ad->id,
                    'title' => $title ?: 'ملک کد ' . $ad->id,
                    'city' => optional($ad->city)->name,
                    'region' => optional($ad->region)->name,
                    'public_price_label' => $publicPrice
                        ? $publicPrice['title'] . ': ' . $publicPrice['value'] . ' ' . ($publicPrice['unit'] ?? '')
                        : 'قیمت عمومی از صفحه آگهی قابل مشاهده است',
                ];
            })->values(),
        ]);
    }

    public function meta()
    {
        return response()->json(['status' => true, 'data' => $this->metaPayload()]);
    }

    private function metaPayload(): array
    {
        return [
            'statuses' => InvestorListing::statuses(),
            'total' => InvestorListing::count(),
            'active' => InvestorListing::where('status', InvestorListing::STATUS_ACTIVE)->count(),
            'pending' => InvestorListing::where('status', InvestorListing::STATUS_PENDING)->count(),
            'investor_submissions' => InvestorListing::where('source', InvestorListing::SOURCE_INVESTOR)->count(),
        ];
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, true);
        $data = $this->normalizePricing($data);
        $advertisement = Advertisement::findOrFail($data['advertisement_id']);
        $existing = InvestorListing::withTrashed()
            ->where('advertisement_id', $advertisement->id)->first();
        if ($existing && !$existing->trashed()) {
            return response()->json(['message' => 'این ملک قبلاً به فهرست سرمایه‌گذاری اضافه شده است. از بخش ویرایش، قیمت ویژه را تغییر دهید.'], 422);
        }
        if (($data['status'] ?? InvestorListing::STATUS_ACTIVE) === InvestorListing::STATUS_ACTIVE && $advertisement->status !== Advertisement::APPROVED) {
            return response()->json(['message' => 'برای انتشار فرصت، آگهی ابتدا باید تایید شده باشد.'], 422);
        }

        $data['source'] = InvestorListing::SOURCE_ADMIN;
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();
        if (($data['status'] ?? InvestorListing::STATUS_ACTIVE) === InvestorListing::STATUS_ACTIVE) $data['published_at'] = now();
        if ($existing) {
            $existing->restore();
            $existing->update($data);
            $listing = $existing;
        } else {
            $listing = InvestorListing::create($data);
        }

        return response()->json([
            'status' => true,
            'message' => 'ملک عمومی با قیمت اختصاصی سرمایه‌گذاری ثبت شد؛ آگهی اصلی همچنان عمومی است.',
            'data' => (new InvestorOpportunityResource($listing->load(['advertisement.category','advertisement.city','advertisement.region'])))->resolve($request),
        ]);
    }

    public function update(Request $request, InvestorListing $investorListing)
    {
        $data = $this->validated($request, false);
        $data = $this->normalizePricing($data, $investorListing);
        $nextStatus = $data['status'] ?? $investorListing->status;
        if ($nextStatus === InvestorListing::STATUS_ACTIVE && $investorListing->advertisement->status !== Advertisement::APPROVED) {
            return response()->json(['message' => 'برای انتشار فرصت، آگهی ابتدا باید تایید شده باشد.'], 422);
        }
        $data['updated_by'] = auth()->id();
        if ($nextStatus === InvestorListing::STATUS_ACTIVE && !$investorListing->published_at) $data['published_at'] = now();
        $investorListing->update($data);
        return response()->json(['status' => true, 'message' => 'فرصت سرمایه‌گذاری بروزرسانی شد.']);
    }

    public function approve(Request $request, InvestorListing $investorListing)
    {
        $advertisement = $investorListing->advertisement;
        if (!$advertisement || (int)$advertisement->status !== Advertisement::APPROVED) {
            return response()->json(['message' => 'ابتدا آگهی عمومی را تأیید کنید؛ وضعیت قیمت اختصاصی نباید وضعیت آگهی اصلی را تغییر دهد.'], 422);
        }
        if (!(int)$investorListing->investor_price) {
            return response()->json(['message' => 'قبل از فعال‌سازی، قیمت سرمایه‌گذاری را وارد کنید.'], 422);
        }
        $investorListing->update([
            'status' => InvestorListing::STATUS_ACTIVE,
            'published_at' => now(),
            'updated_by' => auth()->id(),
        ]);
        return response()->json(['status' => true, 'message' => 'فرصت تایید و در پنل سرمایه‌گذاران منتشر شد.']);
    }

    public function reject(Request $request, InvestorListing $investorListing)
    {
        // Reject the special investor offer only, never the public property.
        $investorListing->update(['status' => InvestorListing::STATUS_REJECTED, 'updated_by' => auth()->id()]);
        return response()->json(['status' => true, 'message' => 'فرصت رد شد.']);
    }

    public function destroy(InvestorListing $investorListing)
    {
        // The investment record is a supplementary price; deleting it must not
        // hide, reject, or otherwise mutate the shared public advertisement.
        $investorListing->forceDelete();
        return response()->json([
            'status' => true,
            'message' => 'قیمت ویژه سرمایه‌گذاری حذف شد؛ آگهی اصلی عمومی بدون تغییر باقی ماند.',
        ]);
    }

    private function normalizePricing(array $data, InvestorListing $existing = null): array
    {
        $market = array_key_exists('market_price', $data)
            ? (int)($data['market_price'] ?? 0)
            : (int)optional($existing)->market_price;
        $investor = array_key_exists('investor_price', $data)
            ? (int)($data['investor_price'] ?? 0)
            : (int)optional($existing)->investor_price;

        if ($market > 0 && $investor > 0) {
            if ($investor >= $market) {
                throw ValidationException::withMessages([
                    'investor_price' => ['قیمت ویژه سرمایه‌گذار باید کمتر از قیمت بازار باشد.'],
                ]);
            }
            $data['discount_percent'] = round((($market - $investor) / $market) * 100, 2);
        } elseif (array_key_exists('market_price', $data) || array_key_exists('investor_price', $data)) {
            $data['discount_percent'] = null;
        }

        return $data;
    }

    private function validated(Request $request, bool $creating): array
    {
        $rules = [
            'status' => ['sometimes', Rule::in(array_keys(InvestorListing::statuses()))],
            'market_price' => 'nullable|integer|min:0',
            'investor_price' => $creating ? 'required|integer|min:1' : 'sometimes|required|integer|min:1',
            'discount_percent' => 'nullable|numeric|min:0|max:99.99',
            'investment_score' => 'nullable|integer|min:1|max:100',
            'investment_horizon_months' => 'nullable|integer|min:1|max:240',
            'deal_summary' => 'nullable|string|max:5000',
            'risk_note' => 'nullable|string|max:5000',
            'expires_at' => 'nullable|date',
        ];
        if ($creating) $rules['advertisement_id'] = [
            'required', 'integer',
            Rule::exists('advertisements', 'id')->where('status', Advertisement::APPROVED),
        ];
        return $this->validate($request, $rules);
    }
}
