<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * Read-only, public, minimal SEO inventory for SPAD's Next.js sitemap.
 * No personal information, drafts, rejected ads or private URLs are exposed.
 * This API returns only slugs/ids and real update times.
 */
Route::get('seo/public-urls', function (Request $request) {
    $types = [
        'property' => 'advertisements',
        'news' => 'blog_posts',
        'news-category' => 'blog_categories',
        'raffle' => 'property_raffles',
        'builder' => 'spad_builder_profiles',
        'auction' => 'spad_property_auctions',
        'virtual-tour' => 'virtual_tours',
        'property-affairs' => 'spad_property_affair_services',
        'inquiry' => 'inquiry_services',
    ];

    $queryFor = function ($kind) use ($types) {
        $table = $types[$kind];
        if (!Schema::hasTable($table)) return null;
        switch ($kind) {
            case 'property':
                return DB::table($table)->where('status', \App\Models\Advertisement::APPROVED);
            case 'news':
                return \App\Models\Blog\BlogPost::query()->ready()->whereNotNull('slug')->where('slug','!=','');
            case 'news-category':
                return DB::table($table)->where('status',1)->whereNotNull('slug')->where('slug','!=','');
            case 'raffle':
                return DB::table($table)->whereIn('status',['published','completed'])->whereNotNull('slug')->where('slug','!=','');
            case 'builder':
                return DB::table($table)->where('published',1);
            case 'auction':
                return DB::table($table)->where('status','published');
            case 'virtual-tour':
                return DB::table('virtual_tours as v')
                    ->join('advertisements as a','a.id','=','v.advertisement_id')
                    ->where('a.status',\App\Models\Advertisement::APPROVED)
                    ->where('v.status','published')
                    ->whereNotNull('v.slug')->where('v.slug','!=','');
            case 'property-affairs':
                return DB::table($table)->where('enabled',1)->whereNotNull('slug')->where('slug','!=','');
            case 'inquiry':
                return DB::table($table)->where('enabled',1)->whereNotNull('slug')->where('slug','!=','');
        }
        return null;
    };

    if ($request->query('summary') === '1') {
        $counts = [];
        foreach ($types as $kind => $table) {
            $q = $queryFor($kind);
            $counts[$kind] = $q ? (int) $q->count() : 0;
        }
        return response()->json(['status'=>true,'per_page'=>500,'counts'=>$counts])
            ->header('Cache-Control','public, max-age=300, s-maxage=300');
    }

    $kind = (string) $request->query('kind','');
    if (!isset($types[$kind])) abort(404);
    $page = filter_var($request->query('page',1), FILTER_VALIDATE_INT, ['options'=>['min_range'=>1,'max_range'=>100000]]);
    if (!$page) abort(422);
    $query = $queryFor($kind);
    if (!$query) return response()->json(['status'=>true,'items'=>[],'page'=>$page,'per_page'=>500]);
    $column = in_array($kind,['property','builder','auction'],true) ? 'id' : 'slug';
    $id = $kind === 'virtual-tour' ? 'v.id' : 'id';
    $time = $kind === 'virtual-tour' ? 'v.updated_at' : 'updated_at';
    $field = $kind === 'virtual-tour' ? 'v.slug' : $column;
    $items = $query->orderBy($id)
        ->forPage($page,500)->get([$field.' as loc', $time.' as updated_at'])
        ->map(function ($row) {
            return ['loc'=>(string) $row->loc, 'lastmod'=>$row->updated_at ? (string) $row->updated_at : null];
        })->values();
    return response()->json(['status'=>true,'items'=>$items,'page'=>$page,'per_page'=>500])
        ->header('Cache-Control','public, max-age=300, s-maxage=300');
})->middleware('throttle:90,1');
