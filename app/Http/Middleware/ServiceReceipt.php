<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class ServiceReceipt
{
    private const SERVICES = [
        'inquiry' => ['api/inquiries/*/submit', 'api/my-inquiries/*/correct'],
        'renovation' => ['api/online-renovation/projects', 'api/online-renovation/*/ai-preview',
            'api/online-renovation/*/finalize', 'api/online-renovation/*/outputs',
            'api/online-renovation/studio/designs/*/submit'],
        'scan' => ['api/online-renovation/scans', 'api/online-renovation/scans/*/renovation'],
        'valuation' => ['api/property-valuation/estimate'],
        'portfolio' => ['api/my-property-assets'],
        'property_request' => ['api/property-acceptance/submit', 'api/my-property-assets/*/acceptance', 'api/my-property-assets/*/sale-request'],
        'property_affairs' => ['api/property-affairs/services/*/requests'],
        'mortgage' => ['api/mortgage/requests'],
        'handover' => ['api/handover-checklists', 'api/handover-checklists/*/complete'],
        'home_doctor' => ['api/home-doctor/cases', 'api/home-doctor/cases/*/analysis'],
        'home_book' => ['api/home-book/devices', 'api/home-book/devices/*/maintenance'],
        'home_garden' => ['api/home-garden/plants', 'api/home-garden/plants/*/photos/*/identify'],
        'home_energy' => ['api/home-energy/accounts/*/bills'],
        'home_planner' => ['api/home-planner/groups/*/tasks'],
        'home_league' => ['api/home-league/*/entries'],
        'home_sunlight' => ['api/home-sunlight/projects'],
        'neighbors' => ['api/neighbors/posts'],
        'building' => ['api/building-management', 'api/building-management/*/tickets'],
        'auction' => ['api/property-auctions', 'api/property-auctions/*/bids', 'api/property-auctions/*/participation'],
        'zoning' => ['api/construction-tools/zoning'],
    ];

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        if (!$request->isMethod('POST') || !($response instanceof JsonResponse) || !$response->isSuccessful()) {
            return $response;
        }

        foreach (self::SERVICES as $service => $paths) {
            if (!$request->is(...$paths)) {
                continue;
            }
            $body = json_decode($response->getContent(), true);
            if (is_array($body) && !empty($body) && ($body['status'] ?? null) !== false &&
                empty($body['errors']) && empty($body['already_submitted'])) {
                $response->headers->set('X-Spad-Service-Receipt', $service);
            }
            break;
        }

        return $response;
    }
}
