<?php

namespace App\Http\Controllers;

use App\Http\Resources\InvestorOpportunityResource;
use App\Http\Resources\OwnerAdvertisementsList;
use App\Models\Advertisement;
use App\Models\AdvertisementProperty;
use App\Models\Category;
use App\Models\InvestorListing;
use App\Models\PartnerPanel;
use App\Models\User;
use App\Support\SpadLocation;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class InvestorPanelController extends Controller
{
    private function investorPanel(Request $request, PartnerPanel $partnerPanel): PartnerPanel
    {
        $resolved = $request->attributes->get('partner_panel');
        if (!$resolved || (int)$resolved->id !== (int)$partnerPanel->id || $resolved->type !== PartnerPanel::TYPE_INVESTOR) {
            abort(403, 'دسترسی به پنل سرمایه‌گذاران برای این حساب فعال نیست.');
        }
        return $resolved;
    }

    public function dashboard(Request $request, PartnerPanel $partnerPanel)
    {
        $panel = $this->investorPanel($request, $partnerPanel);
        $active = InvestorListing::query()->active()
            ->whereHas('advertisement', fn ($q) => $q->where('status', Advertisement::APPROVED));

        $avgDiscount = (clone $active)->get()->pluck('calculated_discount')->filter()->avg();
        $myPending = InvestorListing::query()
            ->where('submitted_by_partner_panel_id', $panel->id)
            ->where('status', InvestorListing::STATUS_PENDING)->count();

        return response()->json([
            'status' => true,
            'data' => [
                'stats' => [
                    'active_opportunities' => (clone $active)->count(),
                    'new_7_days' => (clone $active)->where('published_at', '>=', now()->subDays(7))->count(),
                    'average_discount' => $avgDiscount ? round($avgDiscount, 1) : 0,
                    'my_pending' => $myPending,
                ],
                'featured' => InvestorOpportunityResource::collection(
                    (clone $active)->with(['advertisement.category', 'advertisement.city', 'advertisement.region'])
                        ->orderByDesc('investment_score')->orderByDesc('published_at')->limit(4)->get()
                ),
            ]
        ]);
    }

    public function opportunities(Request $request, PartnerPanel $partnerPanel)
    {
        $this->investorPanel($request, $partnerPanel);
        $query = InvestorListing::query()->active()
            ->whereHas('advertisement', function ($query) {
                $query->where('status', Advertisement::APPROVED)
                    ->where('city_id', SpadLocation::cityId());
            })
            ->with(['advertisement.category', 'advertisement.city', 'advertisement.region'])
            ->orderByDesc('investment_score')->orderByDesc('published_at');

        if ($request->filled('min_discount')) {
            $query->where('discount_percent', '>=', (float)$request->input('min_discount'));
        }
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->whereHas('advertisement', function ($q) use ($search) {
                $q->where('id', $search)->orWhereHas('properties', function ($p) use ($search) {
                    $p->where('property_slug', 'title')->where('text_value', 'like', "%{$search}%");
                });
            });
        }

        return InvestorOpportunityResource::collection($query->paginate(12));
    }

    public function showOpportunity(Request $request, PartnerPanel $partnerPanel, Advertisement $advertisement)
    {
        $this->investorPanel($request, $partnerPanel);
        $listing = InvestorListing::query()->active()
            ->where('advertisement_id', $advertisement->id)->firstOrFail();
        if ($advertisement->status !== Advertisement::APPROVED) throw new ModelNotFoundException();

        $property = userShowCategorySingleLayout($advertisement);
        // Public similar-list logic is intentionally removed from the private deal room.
        $property['similar_ads'] = [];

        return response()->json([
            'status' => true,
            'data' => [
                'deal' => (new InvestorOpportunityResource($listing))->resolve($request),
                'property' => $property,
                'property_tools' => app(PropertyToolsController::class)->payload($advertisement),
            ]
        ]);
    }

    public function myListings(Request $request, PartnerPanel $partnerPanel)
    {
        $panel = $this->investorPanel($request, $partnerPanel);
        $listings = InvestorListing::query()
            ->where('submitted_by_partner_panel_id', $panel->id)
            ->with(['advertisement.category', 'advertisement.city', 'advertisement.region'])
            ->latest()->paginate(12);

        return InvestorOpportunityResource::collection($listings);
    }

    public function storeSubmission(Request $request, PartnerPanel $partnerPanel)
    {
        $panel = $this->investorPanel($request, $partnerPanel);
        $shiraz = SpadLocation::city();
        $this->validate($request, [
            'category' => ['required', Rule::exists('categories', 'id')],
            'position.region.id' => ['required', Rule::exists('regions', 'id')->where('city_id', $shiraz->id)],
            'position.address' => 'required',
            'important_places' => ['nullable', 'array'],
        ]);

        $category = Category::findOrFail($request->input('category'));
        $checkAdvertisement = isValidAdvertisement($category, $request->except('category', 'position'));
        if (count($checkAdvertisement['errors']) > 0) {
            return response()->json(['status' => false, 'errors' => $checkAdvertisement['errors']], 422);
        }

        $listing = DB::transaction(function () use ($request, $panel, $shiraz, $category, $checkAdvertisement) {
            $advertisement = Advertisement::create([
                'category_id' => $category->id,
                'city_id' => $shiraz->id,
                'region_id' => $request->input('position.region.id'),
                'address' => $request->input('position.address'),
                'status' => Advertisement::WAITING,
                'writable_id' => $request->user()->id,
                'writable_type' => User::class,
                'important_places' => $request->input('important_places', []),
            ]);

            $properties = [];
            foreach ($checkAdvertisement['data'] as $item) {
                $properties[] = new AdvertisementProperty($item);
            }
            $advertisement->properties()->saveMany($properties);

            return InvestorListing::create([
                'advertisement_id' => $advertisement->id,
                'submitted_by_partner_panel_id' => $panel->id,
                'source' => InvestorListing::SOURCE_INVESTOR,
                'status' => InvestorListing::STATUS_PENDING,
                'deal_summary' => 'ملک ثبت‌شده توسط سرمایه‌گذار؛ در انتظار بررسی مدیریت SPAD.',
            ]);
        });

        return response()->json([
            'status' => true,
            'message' => 'ملک برای بررسی اختصاصی سرمایه‌گذاران ثبت شد.',
            'data' => (new InvestorOpportunityResource($listing->load(['advertisement.category', 'advertisement.city', 'advertisement.region'])))->resolve($request),
        ]);
    }
}
