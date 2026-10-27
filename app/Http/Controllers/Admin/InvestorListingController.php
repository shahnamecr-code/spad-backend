<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvestorOpportunityResource;
use App\Models\Advertisement;
use App\Models\InvestorListing;
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
        if (InvestorListing::withTrashed()->where('advertisement_id', $advertisement->id)->exists()) {
            return response()->json(['message' => 'برای این ملک قبلاً رکورد سرمایه‌گذاری ساخته شده است.'], 422);
        }
        if (($data['status'] ?? InvestorListing::STATUS_ACTIVE) === InvestorListing::STATUS_ACTIVE && $advertisement->status !== Advertisement::APPROVED) {
            return response()->json(['message' => 'برای انتشار فرصت، آگهی ابتدا باید تایید شده باشد.'], 422);
        }

        $data['source'] = InvestorListing::SOURCE_ADMIN;
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();
        if (($data['status'] ?? null) === InvestorListing::STATUS_ACTIVE) $data['published_at'] = now();
        $listing = InvestorListing::create($data);

        return response()->json([
            'status' => true,
            'message' => 'فرصت سرمایه‌گذاری ایجاد شد و از کاتالوگ عمومی خارج شد.',
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
        if ($advertisement->status !== Advertisement::APPROVED) {
            $advertisement->update(['status' => Advertisement::APPROVED]);
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
        if ($investorListing->source === InvestorListing::SOURCE_INVESTOR) {
            $investorListing->advertisement->update(['status' => Advertisement::REJECTED]);
        }
        $investorListing->update(['status' => InvestorListing::STATUS_REJECTED, 'updated_by' => auth()->id()]);
        return response()->json(['status' => true, 'message' => 'فرصت رد شد.']);
    }

    public function destroy(InvestorListing $investorListing)
    {
        // Investor-submitted properties must never become public as a side effect of
        // removing the private deal record. Admin-origin deals return to their former
        // public catalogue state only through this explicit delete action.
        if ($investorListing->source === InvestorListing::SOURCE_INVESTOR &&
            $investorListing->advertisement->status === Advertisement::APPROVED) {
            $investorListing->advertisement->update(['status' => Advertisement::REJECTED]);
        }
        $investorListing->forceDelete();
        return response()->json([
            'status' => true,
            'message' => $investorListing->source === InvestorListing::SOURCE_INVESTOR
                ? 'رکورد حذف شد و ملک سرمایه‌گذار از انتشار عمومی نیز محافظت شد.'
                : 'فرصت از بازار خصوصی حذف شد و آگهی اصلی می‌تواند دوباره در کاتالوگ عمومی نمایش داده شود.',
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
            'investor_price' => 'nullable|integer|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:99.99',
            'investment_score' => 'nullable|integer|min:1|max:100',
            'investment_horizon_months' => 'nullable|integer|min:1|max:240',
            'deal_summary' => 'nullable|string|max:5000',
            'risk_note' => 'nullable|string|max:5000',
            'expires_at' => 'nullable|date',
        ];
        if ($creating) $rules['advertisement_id'] = ['required', Rule::exists('advertisements', 'id')];
        return $this->validate($request, $rules);
    }
}
