<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountServiceResultsController extends Controller
{
    public function __invoke(Request $request)
    {
        $id = (int) $request->user()->getAuthIdentifier();
        $counts = [
            'inquiries' => 0,
            'contracts' => 0,
            'renovation' => 0,
            'propertyAffairs' => 0,
        ];

        if (Schema::hasTable('inquiry_requests') && Schema::hasTable('inquiry_services')) {
            $results = DB::table('inquiry_requests as requests')
                ->leftJoin('inquiry_services as services', 'services.id', '=', 'requests.inquiry_service_id')
                ->where('requests.user_id', $id)->where('requests.status', 'completed')
                ->groupBy('services.category')
                ->selectRaw('services.category, COUNT(*) AS ready_count')->get();

            foreach ($results as $item) {
                $counts['inquiries'] += (int) $item->ready_count;
                if ($item->category === 'قولنامه و کد رهگیری') {
                    $counts['contracts'] += (int) $item->ready_count;
                }
            }
        }

        if (Schema::hasTable('spad_renovation_projects')) {
            $hasMedia = Schema::hasTable('spad_renovation_media');
            $counts['renovation'] = DB::table('spad_renovation_projects as projects')
                ->where('projects.user_id', $id)
                ->where(function ($query) use ($hasMedia) {
                    $query->whereIn('projects.status', ['completed', 'reviewed']);
                    if ($hasMedia) {
                        $query->orWhereExists(function ($media) {
                            $media->selectRaw('1')->from('spad_renovation_media as media')
                                ->whereColumn('media.project_id', 'projects.id')
                                ->whereColumn('media.user_id', 'projects.user_id')
                                ->where('media.kind', 'output');
                        });
                    }
                })->count();
        }

        if (Schema::hasTable('spad_property_affair_requests')) {
            $counts['propertyAffairs'] = DB::table('spad_property_affair_requests')
                ->where('user_id', $id)->where('status', 'completed')->count();
        }

        return response()->json(['data' => $counts])->header('Cache-Control', 'private, no-store');
    }
}
