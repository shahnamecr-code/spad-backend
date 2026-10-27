<?php

namespace App\Http\Controllers;

use App\Http\Resources\AdvertisementList;
use App\Http\Resources\AllCategoryResource;
use App\Http\Resources\UserAdvertisementsList;
use App\Http\Resources\UserBlogPostsList;
use App\Models\Advertisement;
use App\Models\AdvertisementProperty;
use App\Models\Blog\BlogPost;
use App\Models\Category;
use App\Models\Property;
use App\Models\Setting;
use App\Models\User;
use App\Support\SpadLocation;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AdvertisementController extends Controller
{
    public function showAllCategoriesWithChildren()
    {
        $categories = Category::with('icon_file:id,name,directory_id')->whereNull('parent')->select(['id', 'name', 'parent', 'slug', 'icon'])->with('customer_children')->get();
        return $categories;
    }

    public function getAllCategoriesV3()
    {
        $categories = Category::with('icon_file:id,name,directory_id')->whereNull('parent')->select(['id', 'name', 'parent', 'slug', 'icon'])->with('customer_children')->get();
        return [
            'data' => $categories,
            'levels' => config('tbt_setting.ad_levels', [])
        ];
    }

    public function getCategoryLayoutV1(Request $request, Category $category)
    {
        $side = $category->layout()->order()->side()->public()->get();
        $normal = $category->layout()->order()->normal()->public()->get();


        $properties = [];
        if ($normal) {
            foreach ($normal as $index => $item) {
                if ($lay = $item->layout) {
                    $cnt = [];
                    foreach ($lay as $layout_item) {
                        if (getLayoutAllProperties($layout_item, $properties)) {
                            if (!isset($value['access']) || $value['access'] == 0) {
                                $cnt[] = $layout_item;
                            }
                        }
                    }
                    $item->layout = $cnt;
                    $normal[$index] = $item;
                }
            }
        }
        if ($side) {
            foreach ($side as $index => $item) {
                if ($lay = $item->layout) {
                    $cnt = [];
                    foreach ($lay as $layout_item) {
                        if (getLayoutAllProperties($layout_item, $properties)) {
                            if (!isset($value['access']) || $value['access'] == 0) {
                                $cnt[] = $layout_item;
                            }
                        }
                    }
                    $item->layout = $cnt;
                    $side[$index] = $item;
                }
            }
        }

        return [
            'layout' => $normal->merge($side),
            'properties' => Property::query()->whereIn('slug', $properties)->get()->map(function ($item) {
                return $item->append('options');
            })->pluck(null, 'slug'),
            'important_places' => config('tbt_setting.important_places', []),
        ];
    }

    public function getCategoryLayout(Request $request, Category $category)
    {
        $side = $category->layout()->order()->side()->public()->get();
        $normal = $category->layout()->order()->normal()->public()->get();


        $properties = [];
        if ($normal) {
            foreach ($normal as $index => $item) {
                if ($lay = $item->layout) {
                    $cnt = [];
                    foreach ($lay as $layout_item) {
                        if (getLayoutAllProperties($layout_item, $properties)) {
                            if (!isset($value['access']) || $value['access'] == 0) {
                                $cnt[] = $layout_item;
                            }
                        }
                    }
                    $item->layout = $cnt;
                    $normal[$index] = $item;
                }
            }
        }
        if ($side) {
            foreach ($side as $index => $item) {
                if ($lay = $item->layout) {
                    $cnt = [];
                    foreach ($lay as $layout_item) {
                        if (getLayoutAllProperties($layout_item, $properties)) {
                            if (!isset($value['access']) || $value['access'] == 0) {
                                $cnt[] = $layout_item;
                            }
                        }
                    }
                    $item->layout = $cnt;
                    $side[$index] = $item;
                }
            }
        }

        return [
            'side' => $side,
            'normal' => $normal,
            'properties' => Property::query()->whereIn('slug', $properties)->get()->map(function ($item) {
                return $item->append('options');
            })->pluck(null, 'slug'),
        ];
    }

    /*    public function registerAdvertisement(Request $request)
        {
            if ($request->filled('category')){
                $category_id=$request->input('category');
                $category=Category::find($category_id);
                if ($category){
                    $checkAdvertisement=isValidAdvertisement($category,$request->except('category'));
                    if (count($checkAdvertisement['errors'])==0){
                        $advertisement=Advertisement::create([
                            'category_id'=>$category_id
                        ]);
                        if ($advertisement){
                            $data=$checkAdvertisement['data'];
                            $properties=[];
                            foreach ($data as $item){
                                $properties[]=new AdvertisementProperty($item);
                            }
                            $advertisement->properties()->saveMany($properties);
                            return response()->json([
                                'status'=>true,
                                'data'=>$data
                            ]);
                        }
                    }else{
                        return response()->json([
                            'status'=>false,
                            'errors'=>$checkAdvertisement['errors'],
                        ]);
                    }
                }
            }
        }*/

    public function showAllAdvertisements()
    {
        $query = Advertisement::query();
        if (request()->filled('category')) {
            $query->where(function ($query) {
                $query->whereHas('category', function ($query) {
                    $query->whereJsonContains('structure', (int)request('category'));
                })->orWhere('category_id', (int)request('category'));
            });
        }
        return AdvertisementList::collection($query->cursorPaginate(24));
    }

    public function registerCustomerAdvertisement(Request $request)
    {
        $shiraz = SpadLocation::city();
        $this->validate($request, [
            'category' => ['required', Rule::exists('categories', 'id')],
            'position.region.id' => ['required', Rule::exists('regions', 'id')->where('city_id', $shiraz->id)],
            'position.address' => 'required',
            'important_places' => ['nullable', 'array']
        ]);
        $category_id = $request->input('category');
        $category = Category::find($category_id);
        $checkAdvertisement = isValidAdvertisement($category, $request->except('category', 'position'));
        $status = Advertisement::WAITING;
        if (count($checkAdvertisement['errors']) == 0) {
            $advertisement = Advertisement::create([
                'category_id' => $category_id,
                'city_id' => $shiraz->id,
                'region_id' => $request->input('position.region.id'),
                'address' => $request->input('position.address'),
                'status' => $status,
                'writable_id' => $request->user()->id,
                'writable_type' => User::class,
                'important_places' => $request->input('important_places', [])
            ]);
            if ($advertisement) {
                $data = $checkAdvertisement['data'];
                $properties = [];
                foreach ($data as $item) {
                    $adProp = new AdvertisementProperty($item);
                    $adProp->images = $advertisement;
                    $properties[] = $adProp;
                }
                $advertisement->properties()->saveMany($properties);
                return response()->json([
                    'status' => true,
                    'data' => $data
                ]);
            }
        } else {
            return response()->json([
                'status' => false,
                'errors' => $checkAdvertisement['errors'],
            ]);
        }
    }

    //

    public function displayCategoryFilters(Request $request)
    {
        if ($request->filled('category') && $category = Category::firstWhere('slug', $request->input('category'))) {
            return response()->json([
                'searchItems' => userShowCategoryFilter($category),
                'categories' => $category->direct_children ?? [],
                'current' => $category->parent ? $category->parent_category : [
                    'id' => 0,
                    'name' => 'همه دسته ها'
                ]
            ]);
        } else {
            return response()->json([
                'categories' => Category::whereNull('parent')->get(),
                'current' => false
            ]);
        }
    }

    public function mainPageFilters()
    {
        $categories = Category::whereNull('parent')->get();
        $list = [];
        foreach ($categories as $category) {
            $list[] = [
                'searchItems' => userShowCategoryFilter($category),
                'name' => $category->name,
                'slug' => $category->slug,

            ];
        }
        return $list;
    }

    public function advertisementsList(Request $request)
    {
        $advertisements = Advertisement::query()->where('city_id', SpadLocation::cityId())->with(['category', 'city', 'region'/*,'properties'=>function($query){
            $query->with(['property','advertisement.category'])->whereIn('property_slug',['title','gallery']);
        }*/])->where('status', Advertisement::APPROVED)->orderBy('advertisements.priority', 'desc');

        if ($request->filled('category') && $category = Category::firstWhere('slug', $request->input('category'))) {
            $advertisements->whereHas('category', function ($query) use ($category) {
                $query->whereJsonContains('structure', $category->id)->orWhere('id', $category->id);
            });
            $filter_groups = userShowCategoryFilter($category, $request);
            $searchItems = [];
            foreach ($filter_groups as $group) {
                if (isset($group['items'])) {
                    $searchItems = array_merge($searchItems, $group['items']);
                }
            }
            if (count($searchItems) > 0) {
                $advertisements->join('advertisement_properties', 'advertisements.id', 'advertisement_properties.advertisement_id')
                    ->join('properties', 'advertisement_properties.property_slug', 'properties.slug')
                    ->select('advertisements.*')
                    ->groupBy('advertisements.id');
                $property_condition = [];
                $property_condition_params = [];
                foreach ($searchItems as $searchItem) {
                    if ($searchItem['range_search']) {
                        $value = explode('-', $request->input($searchItem['code']));
                        if (count($value) > 1) {
                            if ($value[0] && $value[0] != '' && $value[1] && $value[1] != '') {
                                $cnd = "properties.slug= ? and ((`properties`.`type`='integer' and `advertisement_properties`.`integer_value` <= ? and `advertisement_properties`.`integer_value` >= ?) or (`properties`.`type`='select' and `advertisement_properties`.`integer_value` <= ? and `advertisement_properties`.`integer_value` >= ?) or (`properties`.`type`='radio' and `advertisement_properties`.`integer_value` <= ? and `advertisement_properties`.`integer_value` >= ?))";
                                $property_condition[] = "(sum(case when $cnd then 1 else 0 end) > 0)";
                                array_push($property_condition_params, (string)$searchItem['code'], (int)$value[1], (int)$value[0], (int)$value[1], (int)$value[0], (int)$value[1], (int)$value[0]);
                            } elseif ($value[0] && $value[0] != '') {
                                $cnd = "properties.slug= ? and ((`properties`.`type`='integer' and `advertisement_properties`.`integer_value` >= ?) or (`properties`.`type`='select' and `advertisement_properties`.`text_value` >= ?) or (`properties`.`type`='radio' and `advertisement_properties`.`text_value` >= ?))";
                                $property_condition[] = "(sum(case when $cnd then 1 else 0 end) > 0)";
                                array_push($property_condition_params, (string)$searchItem['code'], (int)$value[0], (int)$value[0], (int)$value[0]);
                            } elseif ($value[1] && $value[1] != '') {
                                $cnd = "properties.slug= ? and ((`properties`.`type`='integer' and `advertisement_properties`.`integer_value` <= ?) or (`properties`.`type`='select' and `advertisement_properties`.`integer_value` <= ?) or (`properties`.`type`='radio' and `advertisement_properties`.`integer_value` <= ?))";
                                $property_condition[] = "(sum(case when $cnd then 1 else 0 end) > 0)";
                                array_push($property_condition_params, (string)$searchItem['code'], (int)$value[1], (int)$value[1], (int)$value[1]);
                            }
                        }
                    } else {
                        switch ($searchItem['property_type']) {
                            case 'integer':
                            case 'text':
                            case 'select':
                            case 'radio':
                                switch ($searchItem['search_operator']) {
                                    case 'lt':
                                        $searchOperator = '<';
                                        $value = $request->input($searchItem['code']);
                                        break;
                                    case 'gt':
                                        $searchOperator = '>';
                                        $value = $request->input($searchItem['code']);
                                        break;
                                    case 'like':
                                        $searchOperator = 'like';
                                        $value = "%{$request->input($searchItem['code'])}%";
                                        break;
                                    default:
                                        $searchOperator = '=';
                                        $value = $request->input($searchItem['code']);
                                }
                                $cnd = "properties.slug= ? and ((`properties`.`type`='integer' and `advertisement_properties`.`integer_value` $searchOperator ?) or (`properties`.`type`='text' and `advertisement_properties`.`text_value` $searchOperator ?) or (`properties`.`type`='textarea' and `advertisement_properties`.`textarea_value` $searchOperator ?))";
                                $property_condition[] = "(sum(case when $cnd then 1 else 0 end) > 0)";
                                array_push($property_condition_params, (string)$searchItem['code'], $value, $value, $value);
                                break;
                            case 'switch':
                            case 'boolean':
                                $cnd = "properties.slug= ? and `properties`.`type`='boolean' and `advertisement_properties`.`boolean_value` = ?";
                                $property_condition[] = "(sum(case when $cnd then 1 else 0 end) > 0)";
                                array_push($property_condition_params, (string)$searchItem['code'], true);
                                break;
                        }
                    }
                }
                if (count($property_condition) > 0) {
                    $advertisements->havingRaw(implode(' and ', $property_condition), $property_condition_params);
                }
            }
        }

        $advertisements->where('status', Advertisement::APPROVED);

        if ($request->filled('position')) {
            $position = json_decode($request->input('position'), true);
            if (!empty($position['region'])) {
                $advertisements->whereIn('region_id', $position['region']);
            }
        }

        if ($request->filled('bedrooms')) {
            $bedrooms = (int) $request->input('bedrooms');
            if (in_array($bedrooms, [1, 2, 3], true)) {
                $advertisements->whereHas('properties', function ($query) use ($bedrooms) {
                    $query->where('property_slug', 'number_of_sleeps')
                        ->where(function ($valueQuery) use ($bedrooms) {
                            $valueQuery->where('text_value', (string) $bedrooms)
                                ->orWhere('integer_value', $bedrooms)
                                ->orWhere('textarea_value', (string) $bedrooms);
                        });
                });
            }
        }

        if ($request->input('has_image', false)) {
            $advertisements->whereHas('files', function ($query) {
                $query->where('collection_name', 'gallery');
            });
        }

        if ($request->filled('name')) {
            $title = $request->input('name');
            $advertisements->where(function ($query) use ($title) {
                $query->whereHas('properties', function ($query) use ($title) {
                    $query->where('property_slug', 'title')->where('text_value', 'like', "%$title%");
                })->orWhere('id', $title);
            });
        }

        $count = $advertisements->count();

        return UserAdvertisementsList::collection($advertisements->paginate()->appends(request()->query()))->additional([
            'total' => $count
        ]);
    }

    /**
     * @OA\Get(
     *      path="/advertisements/preview/{advertisement}",
     *      operationId="getAdvertisementSinglePage",
     *      tags={"Advertisemnts"},
     *      summary="get singl page information of advertisement",
     *      @OA\Parameter(
     *          name="advertisement",
     *          description="advertisement id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       )
     *     )
     */
    public function showAdvertisementPreview(Request $request, Advertisement $advertisement)
    {
        if ((int) $advertisement->city_id !== SpadLocation::cityId()) {
            throw new ModelNotFoundException();
        }
        if ($advertisement->status == Advertisement::APPROVED) {
            return userShowCategorySingleLayout($advertisement);
        } else {
            throw new ModelNotFoundException();
        }
    }

    /**
     * @OA\Get(
     *      path="/advertisements/preview/auth/{advertisement}",
     *      operationId="getAdvertisementSinglePageWhenLogin",
     *      tags={"Advertisemnts"},
     *      summary="get singl page information of advertisement for login users",
     *      @OA\Parameter(
     *          name="advertisement",
     *          description="advertisement id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       ),
     *      security={{ "bearer_token": {} }}
     *     )
     */
    public function showAdvertisementPreviewWhenLogin(Request $request, Advertisement $advertisement)
    {
        if ($advertisement->status == Advertisement::APPROVED || ($advertisement->writable_type == User::class && $advertisement->writable_id == auth()->id())) {
            $data = userShowCategorySingleLayout($advertisement);
            $data['status'] = (int) $advertisement->status;
            return $data;
        } else {
            throw new ModelNotFoundException();
        }
    }

    public function latestAdvertisements()
    {
        $latest_advertisements = Advertisement::query()->where('city_id', SpadLocation::cityId())->where('status', Advertisement::APPROVED)->orderBy('created_at', 'desc')->limit(10);
        return UserAdvertisementsList::collection($latest_advertisements->get());
    }

    /**
     * @OA\Get(
     *      path="/homeFilters/v1",
     *      operationId="getHomePageFilters",
     *      tags={"Home"},
     *      summary="list of home page filters",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     *     )
     */
    public function mainPageFiltersV1()
    {
        $categories = Category::whereNull('parent')->get();
        $list = [];
        foreach ($categories as $category) {
            $list[] = [
                'name' => $category->name,
                'slug' => $category->slug,
                'icon' => ($category->icon_file) ? $category->icon_file->url : false,
                'layout' => userShowCategoryMainFilter($category),
                'customer_children' => $category->customer_children,
            ];
        }
        return $list;
    }

    /**
     * @OA\Get(
     *      path="/home-layout",
     *      operationId="getHomeLayout",
     *      tags={"Home"},
     *      summary="get home layout",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     *     )
     */
    public function homeLayout()
    {
        $latest_advertisements = Advertisement::query()->where('city_id', SpadLocation::cityId())->where('status', Advertisement::APPROVED)->orderBy('created_at', 'desc')->limit(10);
        $fast_advertisements = Advertisement::query()->where('city_id', SpadLocation::cityId())->where('status', Advertisement::APPROVED)->whereHas('properties', function ($query) {
            $query->where('property_slug', 'fast')->where('boolean_value', true);
        })->orderBy('created_at', 'desc')->limit(10);
        $special_advertisements = Advertisement::query()->where('city_id', SpadLocation::cityId())->where('status', Advertisement::APPROVED)->whereHas('properties', function ($query) {
            $query->where('property_slug', 'special')->where('boolean_value', true);
        })->orderBy('created_at', 'desc')->limit(10);
        $blog_posts = BlogPost::query()->ready()->latest()->limit(10)->with('categories:name,slug', 'admin:id,first_name,last_name');
        $data = [
            'latest' => UserAdvertisementsList::collection($latest_advertisements->get()),
            'fast' => UserAdvertisementsList::collection($fast_advertisements->get()),
            'special' => UserAdvertisementsList::collection($special_advertisements->get()),
            'news' => UserBlogPostsList::collection($blog_posts->get())
        ];
        $home_settings = Setting::query()->where('group', 'home_page')->get()->pluck('user_show', 'code')->toArray();
        $data = array_merge($data, $home_settings);
        return response()->json($data);
    }

    public function getAllCategories()
    {
        return AllCategoryResource::collection(Category::with(['parent_category' => function ($query) {
            return $query->select(['id', 'name', 'parent', 'slug']);
        }])->select(['id', 'name', 'parent', 'slug'])->get());
    }

    /**
     * @OA\Post (
     *      path="/advertisements/report/{advertisement}",
     *      operationId="advertisementReporting",
     *      tags={"Advertisemnts"},
     *      summary="reporting advertisement",
     *      @OA\Parameter(
     *          name="advertisement",
     *          description="advertisement id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *       @OA\RequestBody(
     *          required=true,
     *          description="Pass user credentials",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="reason",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="description",
     *                  type="string"
     *              )
     *         )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent()
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Not Found Exception",
     *          @OA\JsonContent()
     *      ),
     *     security={{ "bearer_token": {} }}
     *     )
     */
    public function report(Request $request, Advertisement $advertisement)
    {
        $ads_report_reasons = \App\Models\Setting::firstWhere('code', 'ads_report_reasons');
        $reasons = [];
        if ($ads_report_reasons) {
            $reasons = array_column($ads_report_reasons->value, 'title');
        }

        $data = $this->validate($request, [
            'description' => 'nullable',
            'reason' => ['string', Rule::in($reasons)]
        ]);

        $report = $advertisement->reports()->firstWhere('user_id', auth()->id());
        if ($report) {
            return response()->json([
                'status' => false,
                'message' => 'گزارش شما قبلا ثبت شده است'
            ]);
        } else {
            $data['user_id'] = auth()->id();
            $report = $advertisement->reports()->create($data);
            if ($report) {
                return response()->json([
                    'status' => true,
                    'message' => 'با تشکر گزارش شما ثبت شد'
                ]);
            }
        }
    }

    /**
     * @OA\Post (
     *      path="/advertisements/adsListById",
     *      operationId="advertisementListByIds",
     *      tags={"Advertisemnts"},
     *      summary="get advertisements list by array of ids",
     *       @OA\RequestBody(
     *          required=true,
     *          description="Pass user credentials",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="ids",
     *                  type="array",
     *                  @OA\Items(type="integer")
     *              )
     *         )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent()
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Not Found Exception",
     *          @OA\JsonContent()
     *      )
     *     )
     */
    public function adsListById(Request $request)
    {
        $this->validate($request, [
            'ids' => ['required', 'array'],
            'ids.*' => ['numeric']
        ]);

        $ids = array_slice($request->ids, 0, min(count($request->ids), 100));
        $advertisements = Advertisement::query()->orderByRaw(DB::raw('FIELD(id, ' . implode(', ', $ids) . ')'))->whereIn('id', $ids);
        $advertisements->where('status', Advertisement::APPROVED);

        $count = $advertisements->count();
        return UserAdvertisementsList::collection($advertisements->paginate())->additional([
            'total' => $count
        ]);
    }

    /**
     * @OA\Get(
     *      path="/advertisements/checkExist/{advertisement}",
     *      operationId="checkExistAdvertisement",
     *      tags={"Advertisemnts"},
     *      summary="check existance of advertisement",
     *      @OA\Parameter(
     *          name="advertisement",
     *          description="advertisement id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     *     )
     */
    public function checkExistAdvertisement(Request $request, Advertisement $advertisement)
    {
        if ((int) $advertisement->city_id !== SpadLocation::cityId()) {
            throw new ModelNotFoundException();
        }
        if ($advertisement->status == Advertisement::APPROVED) {
            return response()->json([
                'status' => true
            ]);
        }
        throw new ModelNotFoundException();
    }

    /**
     * @OA\Post (
     *      path="/advertisements/point/{advertisement}",
     *      operationId="pointToAdvertisement",
     *      tags={"Advertisemnts"},
     *      summary="point for specific advertisement",
     *      @OA\Parameter(
     *          name="advertisement",
     *          description="advertisement id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="Pass user credentials",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="point",
     *                  type="integer",
     *              )
     *         )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      ),
     *      security={{ "bearer_token": {} }}
     *     )
     */
    public function pointToAdvertisement(Request $request, Advertisement $advertisement)
    {
        $data = $this->validate($request, [
            'point' => ['required']
        ]);

        if ($existPoint = $advertisement->points()->firstWhere('user_id', $request->user()->id)) {
            return response()->json([
                'status' => false,
                'message' => 'امتیاز شما قبلا ثبت شده است'
            ]);
        }

        $point = $advertisement->points()->create([
            'point' => $data['point'],
            'user_id' => $request->user()->id,
        ]);
        if ($point) {
            return response()->json([
                'status' => true,
                'message' => 'امتیاز شما با موفقیت ثبت شد'
            ]);
        }
        return response()->json([
            'status' => false,
            'message' => 'مشکلی رخ داده است کمی بعد دوباره تلاش کنید'
        ]);
    }
}
