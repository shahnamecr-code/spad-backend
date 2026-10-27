<?php

use App\Mail\VerifyEmail;
use App\Models\Admin;
use App\Models\Property;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Morilog\Jalali\Jalalian;

if(!function_exists('createToken')){
    function createToken($tokenLength = 5) {
        return (string)random_int(10 ** ($tokenLength - 1), (10 ** $tokenLength) - 1);
    }
}

if(!function_exists('sendEmailVerifyToken')){
    function sendEmailVerifyToken(Admin $admin){
        $token = createToken();
        Cache::add('user_email_verify_' . $admin->id, $token, now()->addMinutes(5));
        Mail::to($admin)->send(new VerifyEmail($token));
    }
}

if(!function_exists('sendMobileVerifyToken')){
    function sendMobileVerifyToken(Admin $admin){
        $token = createToken();
        Cache::add('user_mobile_verify_' . $admin->id, $token, now()->addMinutes(5));


    }
}

if(!function_exists('date_to_timestamp')){
    function date_to_timestamp($date_array){
        $year=$date_array['year'];
        $month=$date_array['month'];
        $day=$date_array['day'];
        return (new Jalalian($year, $month, $day))->toCarbon();
    }
}

if(!function_exists('time_to_array')){
    function time_to_array($date) {
        $date = Jalalian::fromDateTime($date);
        return [
            'year' => $date->getYear(),
            'month' => $date->getMonth(),
            'day' => $date->getDay(),
        ];
    }
}

if (!function_exists('to_georgian')) {
    function to_georgian($date) {
        return Jalalian::forge($date)->toCarbon();
    }
}

if (!function_exists('to_jalalian')) {
    function to_jalalian($date) {
        return Jalalian::fromDateTime($date)->toArray();
    }
}

if(!function_exists('get_attribute_type')){
    function get_attribute_type($type){
        switch ($type){
            case 'image':
                return 'text_value';
            case 'gallery':
                return 'json_value';
            case 'select':
                return 'text_value';
            case 'boolean':
                return 'boolean_value';
            case 'color':
                return 'text_value';
            case 'textarea':
                return 'longtext_value';
            case 'list-item':
                return 'json_value';
            case 'product-category-multi-select':
                return 'json_value';
            default:
                return 'text_value';
        }

    }
}

if(!function_exists('get_attribute_type_v1')){
    function get_attribute_type_v1($type){
        switch ($type){
            case 'image':
                return 'json_value';
            case 'gallery':
                return 'json_value';
            case 'select':
                return 'text_value';
            case 'boolean':
                return 'boolean_value';
            case 'color':
                return 'text_value';
            case 'textarea':
                return 'longtext_value';
            case 'list-item':
                return 'json_value';
            case 'product-category-multi-select':
                return 'json_value';
            default:
                return 'text_value';
        }

    }
}

if(!function_exists('price')){
    function price($price){
        return number_format($price).' تومان';
    }
}

function fix_mobile($value) {
    $value = (string) trim($value);
    if(!Str::startsWith($value,"0")){
        $value = "0".$value;
    }
    return $value;
}

function get_upload_url($value){
    return url("storage/$value");
}

function user_default_profile(){
    $user_default_profile=\App\Models\Setting::firstWhere('code','user_default_profile');
    if ($user_default_profile && $file=$user_default_profile->files()->wherePivot('collection_name','user_default_profile')->first()){
        return $file->url;
    }
}

function userShowAdvertisementProperty($value){
    $res=[
     'title'=>$value->property->title,
     'separator'=>$value->property->separator,
    ];
    if (!is_null($value->property->icon_file)){
        $res['icon']=$value->property->icon_file->url;
    }
    $res['unit']=getUnitLabel($value->property->unit);
    switch ($value->property->type){
        case 'text':
        case 'select':
        case 'radio':
            $res['value']=$value->text_value;
            return $res;
        case 'textarea':
            $res['value']=$value->textarea_value;
            return $res;
        case 'boolean':
//            $res['value']=$value->boolean_value;
            if ($value->boolean_value){
                $res['value']="دارد";
            }else{
                $res['value']="ندارد";
            }
            return $res;
        case 'integer':
            $res['value']=number_format($value->integer_value);
            return $res;
        case 'multiselect':
        case 'checkbox':
        case 'images':
            $res['value']=$value->json_value;
            return $res;
    }
}

function setAdvertisementProperty($data, $property,$objectImages=false){
    $result=[
        'property_slug'=>$property['slug']
    ];
    switch ($property['type']){
        case 'text':
        case 'select':
        case 'radio':
            $result['text_value']=$data;
            break;
        case 'textarea':
            $result['textarea_value']=$data;
            break;
        case 'boolean':
            $result['boolean_value']=$data;
            break;
        case 'integer':
            $result['integer_value']="$data";
            break;
        case 'multiselect':
        case 'checkbox':
        case 'images':
            if ($objectImages){
                $result['json_value']=array_column($data,'id');
            }else{
                $result['json_value']=$data;
            }
            break;
    }
    return $result;
}

function getLayoutAllProperties($layout,&$result,$public=false){
    if ($public || (!isset($layout['access']) || $layout['access']==0)){
        if (isset($layout['dependency'])){
            foreach ($layout['dependency'] as $dependency){
                if (isset($dependency['items'])){
                    foreach ($dependency['items'] as $item){
                        getLayoutAllProperties($item,$result,$public);
                    }
                }
            }
        }
        $result[]=$layout['property_id'];
        return true;
    }
    return false;
}

function doValidate($item,$properties,$data,&$errors,&$validData,$objectImages=false){
    $slug=$item['property_id'];
    if (isset($properties[$slug])){
        if (isset($data[$slug])){
            $validData[]=setAdvertisementProperty($data[$slug],$properties[$slug],$objectImages);
            if (isset($item['dependency'])){
                foreach ($item['dependency'] as $dependency){
                    $cond=false;
                    if (isset($dependency['dependency_type']) && isset($dependency['dependency_limit']) && isset($dependency['items']) && count($dependency['items'])>0){
                        switch ($dependency['dependency_type']){
                            case 'eq':
                                $cond=isset($data[$slug]) && $data[$slug]==$dependency['dependency_limit'];
                                break;
                            case 'lt':
                                $cond=isset($data[$slug]) && $data[$slug]<$dependency['dependency_limit'];
                                break;
                            case 'gt':
                                $cond=isset($data[$slug]) && $data[$slug]>$dependency['dependency_limit'];
                                break;
                            case 'like':
                                $cond=isset($data[$slug]) && is_array($data[$slug]) && in_array($dependency['dependency_limit'],$data[$slug]);
                                break;
                        }
                        if ($cond){
                            foreach ($dependency['items'] as $dependent_item){
                                doValidate($dependent_item,$properties,$data,$errors,$validData,$objectImages);
                            }
                        }
                    }
                }
            }
        }
    }
}

function isValidAdvertisement(\App\Models\Category $category,$data,$public=false,$objectImages=false){
    $layouts=$category->layout->pluck('layout');
    $result=[];
    foreach ($layouts as $layout){
        foreach ($layout as $item){
            getLayoutAllProperties($item,$result,$public);
        }
    }
    $properties=\App\Models\Property::whereIn('slug',$result)->get()->pluck(null,'slug');
    $errors=[];
    $validData=[];
    foreach ($layouts as $layout){
        foreach ($layout as $item){
            doValidate($item,$properties,$data,$errors,$validData,$objectImages);
        }
    }
    return [
        'errors'=>$errors,
        'data'=>$validData
    ];
}

function getCategoryFilters(\App\Models\Category $category){
    $searchItems=$category->searchItems;
    $properties=\App\Models\Property::whereIn('slug',array_column($searchItems,'property_id'))->get()->pluck(null,'slug');
    $filters=[];
    foreach ($searchItems as $item){
        $property=$properties[$item['property_id']];
        $filter=[
            'key'=>$item['property_id'],
            'search_type'=>$item['search_type'],
            'range_search'=>$item['range_search'],
//            'property'=>$property
        ];
        if (in_array($item['search_type'],['select','checkbox'])){
            if (isset($item['search_enum']) && count($item['search_enum'])>0){
                $filter['enum']=$item['search_enum'];
                if (isset($item['search_enumNames']) && count($item['search_enumNames'])>0){
                    $filter['enumNames']=$item['search_enumNames'];
                }
            }elseif(isset($property['enum']) && count($property['enum'])>0){
                $filter['enum']=$property['enum'];
                if(isset($property['enumNames']) && count($property['enumNames'])>0){
                    $filter['enumNames']=$property['enumNames'];
                }
            }else{
                $filter['enum']=[];
            }
        }
        $filters[]=$filter;
    }
    return $filters;
}

function userShowCategoryFilter(\App\Models\Category $category,$request=null){
    $searchItems=$category->searchItems;
    $allowed_properties = [];
    $layouts = $category->layout;
    foreach ($layouts as $layout) {
        if ($layout->layout) {
            foreach ($layout->layout as $item) {
                getLayoutAllProperties($item, $allowed_properties);
            }
        }
    }
    $properties=Property::whereIn('slug', $allowed_properties)->get()->pluck(null,'slug')->toArray();
    $items=[];
    if ($searchItems){
        foreach ($searchItems as $groupItem){
            if (isset($groupItem['filters']) && count($groupItem['filters'])>0){
                $group=[
                    'label'=>$groupItem['label']??'',
                    'icon'=>(isset($groupItem['icon']) && $groupItem['icon'])?$groupItem['icon'][0]:false,
                    'items'=>[]
                ];
                foreach ($groupItem['filters'] as $searchItem){
                    if ((is_null($request) || $request->filled($searchItem['property_id'])) && isset($properties[$searchItem['property_id']])){
                        $property=$properties[$searchItem['property_id']];
                        $new_item=[
                            'label'=>$property['title'],
                            'code'=>$searchItem['property_id'],
                            'search_type'=>$searchItem['search_type'],
                            'range_search'=>$searchItem['range_search'],
                            'property_type'=>$property['type'],
//                    'property_type'=>$searchItem['search_type'],
                            'search_operator'=>$searchItem['search_operator'],
                            'separator'=>$property['separator'],
                            'unit'=>isset($property['unit'])?getUnitLabel($property['unit']):'',
                            'auto_complete'=> $searchItem['auto_complete'] ?? false,
                        ];
                        switch ($searchItem['search_type']){
                            case 'text':
                                break;
                            case 'select':
                            case 'radio':
                            case 'checkbox':
                                if (isset($searchItem['search_enum'])){
                                    $enum=$searchItem['search_enum'];
                                    if (isset($searchItem['search_enumNames'])){
                                        $enumNames=$searchItem['search_enumNames'];
                                    }else{
                                        $enumNames=$enum;
                                    }
                                }else{
                                    $enum=(array)$property['enum'];
                                    $enumNames=(array)$property['enumNames']??$enum;
                                }
                                $options=[];
                                foreach ($enum as $index=>$enumItem){
                                    $options[]=[
                                        'label'=>(isset($enumNames[$index]))?$enumNames[$index]:$enumItem,
                                        'value'=>$enumItem,
                                    ];
                                }
                                $new_item['options']=$options;
                                break;
                        }
                        $group['items'][]=$new_item;
                    }
                }
                $items[]=$group;
            }
        }
    }
    return $items;
}

function userShowCategorySingleLayout(\App\Models\Advertisement $advertisement){
    $category=$advertisement->category;
    $allowed_properties=advertisementCategorySingleProperties($category);
    array_push($allowed_properties,'title','gallery','position','fast','special','luxury','netting','price_evolution');
    $ad_props=$advertisement->properties()->whereIn('property_slug',$allowed_properties)->with('property')->get();
    $details=[];
    foreach ($ad_props as $ad_prop){
        $details[$ad_prop['property_slug']]=userShowAdvertisementProperty($ad_prop);
    }

    // Boolean badge properties must use the raw database value. The formatted
    // value returned by userShowAdvertisementProperty() is "دارد/ندارد" and
    // both are non-empty strings, so treating that formatted value as a bool
    // would incorrectly keep disabled badges visible.
    $booleanBadges=$ad_props
        ->whereIn('property_slug',['fast','special','luxury','netting'])
        ->pluck('boolean_value','property_slug');
    $layouts=$category->singleLayout;
    $results=[];
    if ($layouts){
        foreach ($layouts as $layout){
            switch ($layout['widget_type']){
                case 'horizontal':
                case 'vertical':
                case 'group':
                    $items=[];
                    if (isset($layout['items'])){
                        foreach ($layout['items'] as $item){
                            if (isset($item['slug']) && isset($details[$item['slug']])){
                                $items[]=[
                                    'info'=>$details[$item['slug']]
                                ];
                            }
                        }
                    }
                    $results[]=[
                        'widget_type'=>$layout['widget_type'],
                        'title'=>(isset($layout['title']))?$layout['title']:'',
                        'front_layout'=>(isset($layout['front_layout']))?$layout['front_layout']:'',
                        'items'=>$items
                    ];
                    break;
                default:
                    $results[]=[
                        'widget_type'=>$layout['widget_type'],
                        'info'=>(isset($details[$layout['slug']]))?$details[$layout['slug']]:''
                    ];

            }
        }
    }
    $admins=$advertisement->admins()->with('manager')->get();
    $experts=[];
    $supervisors=[];
    $managers=Admin::query()->role('manager')->whereHas('allowed_categories',function ($query)use($advertisement){
        $query->where('admin_category.category_id',$advertisement->category_id);
    })->whereHas('cities',function ($query)use($advertisement){
        $query->where('admin_city.city_id',$advertisement->city_id);
    })->get()->map(function ($collect){
        return collect($collect->toArray())
            ->only(['display_name','mobile','internal_phone','avatar']);
    });
    foreach ($admins as $admin){
        $experts[$admin->id]=[
            'display_name'=>$admin->display_name,
            'mobile'=>$admin->mobile,
            'internal_phone'=>$admin->internal_phone,
            'avatar'=>$admin->avatar,
        ];
        if ($admin->manager){
            $supervisors[$admin->manager->id]=[
                'display_name'=>$admin->manager->display_name,
                'mobile'=>$admin->manager->mobile,
                'internal_phone'=>$admin->internal_phone,
                'avatar'=>$admin->avatar,
            ];
        }
    }
    $recursive_parent_category_name=$advertisement->category;
    $breadcrumb=[
        [
            'id'=>$recursive_parent_category_name->id,
            'name'=>$recursive_parent_category_name->name,
            'slug'=>$recursive_parent_category_name->name,
        ]
    ];
    while ($recursive_parent_category_name=$recursive_parent_category_name->recursive_parent_category_name){
        $breadcrumb[]=[
            'id'=>$recursive_parent_category_name->id,
            'name'=>$recursive_parent_category_name->name,
            'slug'=>$recursive_parent_category_name->name,
        ];
    }

    $location=[$advertisement->city->name];
    if ($advertisement->region){
        $location[]=$advertisement->region->name;
    }

//    $managers_list=\App\Models\Setting::firstWhere('code','managers_list');
//    $ads_report_reasons=\App\Models\Setting::firstWhere('code','ads_report_reasons');
    $ads_single_info=\App\Models\Setting::where('group','ads_single')->get()->pluck('value','code')->toArray();
    extract($ads_single_info);

    /*
     * Similar properties: rank approved advertisements by the closest
     * transaction values of the current property. This reuses the existing
     * similar_ads payload, so the public page does not make another API call.
     *
     * We compare both dynamic integer fields (price/deposit/rent) and the
     * dedicated rental values stored in advertisement_tools. Financing/loan
     * amounts are intentionally excluded from similarity scoring.
     */
    $currentMonetaryProperties = $advertisement->properties()
        ->with('property')
        ->get()
        ->filter(function ($item) {
            if (!$item->property || $item->property->type !== 'integer' || !$item->integer_value || $item->integer_value <= 0) {
                return false;
            }

            $searchableName = trim(($item->property->title ?? '') . ' ' . ($item->property_slug ?? ''));
            $isMonetary = preg_match('/قیمت|مبلغ|رهن|ودیعه|اجاره|price|amount|deposit|rent/i', $searchableName);
            $isFinancing = preg_match('/وام|قسط|اوراق|تسهیلات|loan|installment|mortgage/i', $searchableName);

            return $isMonetary && !$isFinancing;
        })
        ->keyBy('property_slug');

    $monetarySlugs = $currentMonetaryProperties->keys()->values()->all();
    $currentMonetaryValues = $currentMonetaryProperties
        ->mapWithKeys(function ($item) {
            return [$item->property_slug => (float)$item->integer_value];
        });

    $currentTool = $advertisement->toolConfig;
    $currentToolValues = collect([
        'rental_deposit_ceiling' => (float)($currentTool->rental_deposit_ceiling ?? 0),
        'rental_monthly_rent' => (float)($currentTool->rental_monthly_rent ?? 0),
    ])->filter(function ($value) {
        return $value > 0;
    });

    if (count($monetarySlugs) > 0 || $currentToolValues->count() > 0) {
        $similarCandidatesQuery = \App\Models\Advertisement::query()
            ->publicCatalog()
            ->where('status', \App\Models\Advertisement::APPROVED)
            ->where('category_id', $advertisement->category_id)
            ->where('id', '!=', $advertisement->id)
            ->with('toolConfig');

        if (count($monetarySlugs) > 0) {
            $similarCandidatesQuery->with(['properties' => function ($query) use ($monetarySlugs) {
                $query->whereIn('property_slug', $monetarySlugs);
            }]);
        } else {
            $similarCandidatesQuery->with('properties');
        }

        // Recent candidates keep this computation bounded on large catalogues.
        $similarCandidates = $similarCandidatesQuery
            ->latest('updated_at')
            ->limit(150)
            ->get();

        $similar_ads = $similarCandidates
            ->map(function ($candidate) use ($currentMonetaryValues, $currentToolValues, $advertisement) {
                $candidateValues = $candidate->properties->keyBy('property_slug');
                $differenceSum = 0;
                $compared = 0;

                foreach ($currentMonetaryValues as $slug => $currentValue) {
                    if (!$candidateValues->has($slug)) {
                        continue;
                    }

                    $candidateValue = (float)($candidateValues->get($slug)->integer_value ?? 0);
                    if ($candidateValue <= 0 || $currentValue <= 0) {
                        continue;
                    }

                    $differenceSum += abs($candidateValue - $currentValue) / max($currentValue, 1);
                    $compared++;
                }

                foreach ($currentToolValues as $field => $currentValue) {
                    $candidateValue = (float)($candidate->toolConfig->{$field} ?? 0);
                    if ($candidateValue <= 0 || $currentValue <= 0) {
                        continue;
                    }

                    $differenceSum += abs($candidateValue - $currentValue) / max($currentValue, 1);
                    $compared++;
                }

                if ($compared === 0) {
                    $candidate->similarity_score = 999;
                    return $candidate;
                }

                $score = $differenceSum / $compared;

                // Price is primary; location is only a small tie-breaker.
                if ($candidate->city_id != $advertisement->city_id) {
                    $score += 0.08;
                }
                if ($advertisement->region_id && $candidate->region_id != $advertisement->region_id) {
                    $score += 0.03;
                }

                $candidate->similarity_score = $score;
                return $candidate;
            })
            ->filter(function ($candidate) {
                return $candidate->similarity_score < 999;
            })
            ->sortBy('similarity_score')
            ->take(20)
            ->values();
    } else {
        $similar_ads = collect();
    }

    // Fallback for legacy ads that do not expose a comparable monetary field,
    // or when no priced candidate exists in the same category.
    if ($similar_ads->count() === 0) {
        $similar_ads = \App\Models\Advertisement::query()
            ->publicCatalog()
            ->where('status', \App\Models\Advertisement::APPROVED)
            ->where('category_id', $advertisement->category_id)
            ->where('id', '!=', $advertisement->id)
            ->orderByRaw('CASE WHEN city_id = ? THEN 0 ELSE 1 END', [$advertisement->city_id])
            ->latest()
            ->take(20)
            ->get();
    }

    $point=$advertisement->points()->average('point');
    $user_point=null;

    if (auth()->check() && $your_point=$advertisement->points()->firstWhere('user_id',auth()->id())){
        $user_point=$your_point->point;
    }

    $gallery=$advertisement->files()->wherePivot('collection_name','gallery')->get()->pluck('url')->toArray();
    if (count($gallery)==0){
        $gallery=[url('/images/default/adds_default.jpg')];
    }

    return [
        'id'=>$advertisement->id,
        'widgets'=>$results,
        'title'=>(isset($details['title']) && isset($details['title']['value']))?$details['title']['value']:'آگهی بدون عنوان',
        'gallery'=>$gallery,
        'admins'=>[
            'experts'=>array_values($experts),
            'supervisors'=>array_values($supervisors),
            'managers'=>$managers,
        ],
        'breadcrumb'=>array_reverse($breadcrumb),
        'category'=>$advertisement->category->name,
        'location'=>implode(' | ',$location),
        'date'=>$advertisement->created_at->diffForHumans(),
        'managers_list'=>$managers_list??[],
        'ads_report_reasons'=>$ads_report_reasons??[],
        'fast'=>(bool)($booleanBadges['fast']??false),
        'special'=>(bool)($booleanBadges['special']??false),
        'lux'=>(bool)($booleanBadges['luxury']??false),
        'netting'=>(bool)($booleanBadges['netting']??false),
        'price_evolution'=>(isset($details['price_evolution']) && $details['price_evolution'])?$details['price_evolution']['value']:null,
        'important_places'=>$advertisement->distance_to_places,
        'evaluation_type'=>$evaluation_type??null,
        'evaluation_params'=>$evaluation_type??[],
        'similar_ads'=>\App\Http\Resources\UserAdvertisementsList::collection($similar_ads),
        'point'=>$point?round($point,2):null,
        'user_point'=>$user_point
    ];
}

function advertisementCategorySingleProperties(\App\Models\Category $category){
    $layouts=$category->singleLayout;

    $properties=[];
    if ($layouts){
        foreach ($layouts as $layout){
            switch ($layout['widget_type']){
                case 'horizontal':
                case 'vertical':
                case 'group':
                    if (isset($layout['items'])){
                        $properties=array_merge($properties,array_column($layout['items'],'slug'));
                    }
                    break;
                default:
                    if (isset($layout['slug'])){
                        $properties[]=$layout['slug'];
                    }
            }
        }
    }
    return $properties;
}

function userShowCategoryMainFilter(\App\Models\Category $category,$request=null){
    $allowed_properties = [];
    $layouts = $category->layout;
    foreach ($layouts as $layout) {
        if ($layout->layout) {
            foreach ($layout->layout as $item) {
                getLayoutAllProperties($item, $allowed_properties);
            }
        }
    }
    $properties=Property::whereIn('slug', $allowed_properties)->get()->pluck(null,'slug')->toArray();
    $main_filters=$category->mainFilters;
    $filters=[];
    if ($main_filters){
        foreach ($main_filters as $filter){
            $items=[];
            if (isset($filter['filters'])){
                $searchItems=$filter['filters'];
                foreach ($searchItems as $searchItem){
                    if ((is_null($request) || $request->filled($searchItem['property_id'])) && isset($properties[$searchItem['property_id']])){
                        $property=$properties[$searchItem['property_id']];
                        $new_item=[
                            'label'=>$property['title'],
                            'separator'=>$property['separator'],
                            'unit'=>isset($property['unit'])?getUnitLabel($property['unit']):'',
                            'code'=>$searchItem['property_id'],
                            'search_type'=>$searchItem['search_type'],
                            'range_search'=>$searchItem['range_search'],
                            'property_type'=>$property['type'],
                            'search_operator'=>$searchItem['search_operator'],
                        ];
                        switch ($searchItem['search_type']){
                            case 'text':
                                break;
                            case 'select':
                            case 'checkbox':
                                if (isset($searchItem['search_enum'])){
                                    $enum=$searchItem['search_enum'];
                                    if (isset($searchItem['search_enumNames'])){
                                        $enumNames=$searchItem['search_enumNames'];
                                    }else{
                                        $enumNames=$enum;
                                    }
                                }else{
                                    $enum=(array)$property['enum'];
                                    $enumNames=(array)$property['enumNames']??$enum;
                                }
                                $options=[];
                                foreach ($enum as $index=>$enumItem){
                                    $options[]=[
                                        'label'=>(isset($enumNames[$index]))?$enumNames[$index]:$enumItem,
                                        'value'=>$enumItem,
                                    ];
                                }
                                $new_item['options']=$options;
                                break;
                        }
                        $items[]=$new_item;
                    }
                }
                $filters[]=[
                    'title'=>(isset($filter['title']))?$filter['title']:'',
                    'filters'=>$items,
                ];
            }
        }
    }
    return $filters;
}

function getUnitLabel($val){
    $units=[
        'm'=>'متر',
        'm2'=>'متر مربع',
        'm3'=>'متر مکعب',
        'IRT'=>'تومان',
        'IRR'=>'ریال',
        'year'=>'سال',
        'month'=>'ماه',
        'day'=>'روز',
        'hour'=>'ساعت',
        'min'=>'دقیقه',
        'second'=>'ثانیه',
    ];
    return $units[$val]??'';
}

function getTimes($type){
    $times=[];
    switch ($type){
        case 'week':
            $ref=now();
            $item=[
                'end'=>$ref->format('Y/m/d H:i:s'),
                'start'=>$ref->startOfDay()->format('Y/m/d H:i:s'),
                'label'=>(Jalalian::fromCarbon($ref))->format("%A")
            ];

            $times[]=$item;
            for ($i=0;$i<6;$i++){
                $ref=$ref->subDay();
                $label=(Jalalian::fromCarbon($ref))->format("%A");
                $times[]=[
                    'start'=>$ref->startOfDay()->format('Y/m/d H:i:s'),
                    'end'=>$ref->endOfDay()->format('Y/m/d H:i:s'),
                    'label'=>$label
                ];
            }
            break;
        case 'month':
            $persian_now=Jalalian::now();
            $days_count=$persian_now->getMonthDays();
            $ref=now();
            $item=[
                'end'=>$ref->format('Y/m/d H:i:s'),
                'start'=>$ref->startOfDay()->format('Y/m/d H:i:s'),
                'label'=>(Jalalian::fromCarbon($ref))->format("%d %B")
            ];

            $times[]=$item;
            for ($i=0;$i<$days_count-1;$i++){
                $ref=$ref->subDay();
                $label=(Jalalian::fromCarbon($ref))->format("%d %B");
                $times[]=[
                    'start'=>$ref->startOfDay()->format('Y/m/d H:i:s'),
                    'end'=>$ref->endOfDay()->format('Y/m/d H:i:s'),
                    'label'=>$label
                ];
            }
            break;
        case 'year':
            $ref=now();
            $item=[
                'end'=>$ref->format('Y/m/d H:i:s'),
                'start'=>$ref->startOfMonth()->format('Y/m/d H:i:s'),
                'label'=>(Jalalian::fromCarbon($ref))->format("%B %Y")
            ];

            $times[]=$item;
            for ($i=0;$i<11;$i++){
                $ref=$ref->subMonth();
                $label=(Jalalian::fromCarbon($ref))->format("%B %Y");
                $times[]=[
                    'end'=>$ref->endOfMonth()->format('Y/m/d H:i:s'),
                    'start'=>$ref->startOfMonth()->format('Y/m/d H:i:s'),
                    'label'=>$label
                ];
            }
            break;
    }
    return array_reverse($times);
}

function appendSubFolders($directory,&$list){
    if ($directory->children){
        foreach ($directory->children as $sub){
            $list[]=$sub->id;
            appendSubFolders($sub,$list);
        }
    }
}

function updateDirectorySubs($directory,$old,$new){
    $list=[];
    appendSubFolders($directory,$list);

    if ($list){
        $params=[];
        for ($x = 0; $x < count($list); $x++) {
            $params[]="?";
        }
        $whereIn=implode(" , ",$params);
        DB::update("update directories set path = replace(path, ?, ?) where id in ($whereIn)",array_merge([
            $old,
            $new,
        ],$list));
    }
}

function checkBuyScript($api,$username,$order_id,$domain,$productId="new Product"){
    $url = 'https://www.rtl-theme.com/oauth/';
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_POSTFIELDS,"api=$api&username=$username&order_id=$order_id&domain=$domain&pid=$productId");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

function checkHasLicense(){
    return true;
    $license=Setting::firstWhere([
        'group'=>'default',
        'code'=>'license'
    ]);
    if ($license){
        $random_hash="7f515218479cdb03a6c9bd0c035cbc2f1";
        $ciphering = "AES-128-CTR";
        $options = 0;
        $encryption_iv = '1825562541011121';
        $encryption_key = "42c5bd69a4e320p1a0b74946e6e05e507";
        $data = json_decode(openssl_decrypt(Crypt::decrypt($license->longtext_value), $ciphering,
            $encryption_key, $options, $encryption_iv),true);
        if (isset($data['hash']) && $data['hash']==$random_hash){
            if (isset($data['domain']) && $data['domain']==env("CLIENT_URL")){
                $last_check=\Carbon\Carbon::now()->timestamp-$data['time'];
                if ($last_check>6*60*60){
                    $result=checkBuyScript($data['api'], $data['username'], $data['order_id'], $data['domain'], $data['productId']);
                    switch ($result) {
                        case '1':
                            $error = NULL;
                            break;
                        case '-1':
                            $error = 'API ‫است‬ ‫اشتباه‬';
                            break;
                        case '-2':
                            $error = '‫است‬ ‫اشتباه‬ ‫ی‬‫کاربر‬ ‫نام‬';
                            break;
                        case '-3':
                            $error = '‫است‬ ‫اشتباه‬ ‫سفارش‬ ‫کد‬';
                            break;
                        case '-4':
                            $error = '‫است‬ ‫شده‬ ‫ثبت‬ ً‫قبلا‬ ‫سفارش‬ ‫کد‬';
                            break;
                        case '-5':
                            $error = '‫باشد‬‫ي‬‫نم‬ ‫ی‬‫کاربر‬ ‫نام‬ ‫ن‬‫ي‬‫ا‬ ‫به‬ ‫مربوطه‬ ‫سفارش‬ ‫کد‬.';
                            break;
                        case '-6':
                            $error = '‫باشند‬‫ي‬‫نم‬ ‫ح‬‫ي‬‫صح‬ ‫فرمت‬ ‫در‬ ‫شده‬ ‫وارد‬ ‫اطلاعات‬!';
                            break;
                        case '-7':
                            $error = '‫ست‬‫ي‬‫ن‬ ‫محصول‬ ‫ن‬‫ي‬‫ا‬ ‫به‬ ‫مربوط‬ ‫سفارش‬ ‫کد‬';
                            break;
                        case '-8':
                            $error = '‫باشد‬‫ي‬‫نم‬ ‫ی‬‫کاربر‬ ‫نام‬ ‫ن‬‫ي‬‫ا‬ ‫به‬ ‫مربوطه‬ ‫سفارش‬ ‫کد‬.';
                            break;
                        default:
                            $error = '‫است‬ ‫داده‬ ‫رخ‬ ‫رمنتظره‬‫ي‬‫غ‬ ‫ی‬‫خطا‬';
                            break;
                    }
                    if (is_null($error)) {
                        $data['time']=\Carbon\Carbon::now()->timestamp;
                        $encryption = openssl_encrypt(json_encode($data), $ciphering,
                            $encryption_key, $options, $encryption_iv);
                        $license = \App\Models\Setting::updateOrCreate([
                            'group' => 'default',
                            'code' => 'license',
                        ], [
                            'longtext_value' => \Illuminate\Support\Facades\Crypt::encrypt($encryption)
                        ]);
                    }
                }
                return true;
            }
        }
    }
    return false;
}
