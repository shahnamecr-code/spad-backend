<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvestorOpportunityResource extends JsonResource
{
    public function toArray($request)
    {
        $advertisement = $this->advertisement;
        return [
            'id' => $this->id,
            'advertisement_id' => $this->advertisement_id,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'source' => $this->source,
            'market_price' => $this->market_price,
            'investor_price' => $this->investor_price,
            'discount_percent' => $this->calculated_discount,
            'price_gap' => $this->price_gap,
            'investment_score' => $this->investment_score,
            'investment_horizon_months' => $this->investment_horizon_months,
            'deal_summary' => $this->deal_summary,
            'risk_note' => $this->risk_note,
            'published_at' => optional($this->published_at)->toIso8601String(),
            'expires_at' => optional($this->expires_at)->toIso8601String(),
            'advertisement' => $advertisement ? (new UserAdvertisementsList($advertisement))->resolve($request) : null,
        ];
    }
}
