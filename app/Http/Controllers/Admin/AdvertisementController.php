<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminAdvertisementReport;
use App\Http\Resources\AdminAdvertisementsList;
use App\Http\Resources\AdvertisementList;
use App\Models\Admin;
use App\Models\Advertisement;
use App\Models\AdvertisementProperty;
use App\Models\Category;
use App\Models\City;
use App\Models\LandType;
use App\Models\Property;
use App\Models\PropertyWrapper;
use App\Models\Region;
use App\Models\Transaction;
use App\Models\User;
use App\Support\SpadLocation;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AdvertisementController extends Controller
{
    //    property wrapper
    public function index()
    {
        $propertySideWrappers = PropertyWrapper::query()->order()->side()->get();
        $propertyNormalWrappers = PropertyWrapper::query()->order()->normal()->get();
        return response()->json([
            'normal' => $propertyNormalWrappers,
            'side' => $propertySideWrappers
        ]);
    }

    public function getPropertyWrapper(PropertyWrapper $wrapper)
    {
        return [
            'data' => $wrapper,
            'properties' => Property::select(['id', 'title', 'type', 'slug'])->get()
        ];
    }

    public function addPropertyWrapper(Request $request)
    {
        $data = $this->validate($request, [
            'title' => 'required',
            'order' => 'integer|required',
            'position' => ['required', Rule::in(['side', 'normal'])],
            'layout' => 'nullable|array'
        ]);
        if ($propertyWrapper = PropertyWrapper::create($data)) {
            return response()->json([
                'status' => true,
                'message' => 'بخش جدید به آگهی ها افزوده شد'
            ]);
        }
        return response()->json([
            'status' => false,
            'message' => 'مشکلی در سمت سرور رخ داده است کمی بعد مجددا تلاش کنید...'
        ]);
    }

    public function editPropertyWrapper(Request $request, PropertyWrapper $wrapper)
    {
        return view('admin.property.index', compact(['wrapper']));
    }

    public function updatePropertyWrapper(Request $request, PropertyWrapper $wrapper)
    {
        $data = $this->validate($request, [
            'title' => 'required',
            'order' => 'integer|required',
            'position' => ['required', Rule::in(['side', 'normal'])],
            'layout' => 'nullable|array'
        ]);


        if ($wrapper->update($data)) {
            return response()->json([
                'status' => true,
                'message' => 'تغییرات با موفقیت اعمال شد'
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'مشکلی در سمت سرور رخ داده است کمی بعد مجددا تلاش کنید...'
        ]);
    }

    //    property wrapper

    public function createProperty()
    {
        $wrappers = PropertyWrapper::select('id', 'title')->order()->get();
        $properties = Property::select('id', 'title')->order()->get();
        return view('admin.property.index', compact(['wrappers', 'properties']));
    }

    //    property wrapper

//    transaction controller

    public function TransactionsList()
    {
        $transactions = Transaction::query()->get();
        return $transactions;
    }

    public function transactions()
    {
        $transactions = Transaction::select(['id', 'name'])->get();
        return $transactions;
    }

    public function getTransactionEditForm(Transaction $transaction)
    {
        return [
            'id' => $transaction->id,
            'name' => $transaction->name,
            'land_types' => $transaction->land_types->pluck('id')->toArray()
        ];
    }

    public function addTransaction(Request $request)
    {
        $data = $this->validate($request, [
            'name' => 'required',
            'land_types' => 'array'
        ]);

        $land_types = (isset($data['land_types'])) ? $data['land_types'] : [];
        unset($data['land_types']);

        if ($transaction = Transaction::create($data)) {
            $transaction->land_types()->sync($land_types);
            return response()->json([
                'status' => true,
                'message' => 'نوع معامله جدید افزوده شد.'
            ]);
        }
    }

    public function updateTransaction(Request $request, Transaction $transaction)
    {
        $data = $this->validate($request, [
            'name' => 'required',
            'land_types' => 'array'
        ]);

        $land_types = (isset($data['land_types'])) ? $data['land_types'] : [];
        unset($data['land_types']);

        if ($transaction->update($data)) {
            $transaction->land_types()->sync($land_types);
            return response()->json([
                'status' => true,
                'message' => 'معامله ویرایش شد.'
            ]);
        }
    }


//    transaction controller

// land type controller
    public function landTypesList()
    {
        if (request()->has('transaction') && $transaction = Transaction::find(request('transaction'))) {
            $landTypes = $transaction->land_types();
        } else {
            $landTypes = LandType::query();
        }
        return $landTypes->get();
    }

    public function getLandTypeEditForm(LandType $landType)
    {
        return [
            'id' => $landType->id,
            'name' => $landType->name,
            'transactions' => $landType->transactions->pluck('id')->toArray()
        ];
    }

    public function addLandType(Request $request)
    {
        $data = $this->validate($request, [
            'name' => 'required',
            'transactions' => 'array'
        ]);

        $transactions = (isset($data['transactions'])) ? $data['transactions'] : [];
        unset($data['transactions']);

        if ($landType = LandType::create($data)) {
            $landType->transactions()->sync($transactions);
            return response()->json([
                'status' => true,
                'message' => 'ملک جدید افزوده شد.'
            ]);
        }
    }

    public function updateLandType(Request $request, LandType $landType)
    {
        $data = $this->validate($request, [
            'name' => 'required',
            'transactions' => 'array'
        ]);

        $transactions = (isset($data['transactions'])) ? $data['transactions'] : [];
        unset($data['transactions']);

        if ($landType->update($data)) {
            $landType->transactions()->sync($transactions);
            return response()->json([
                'status' => true,
                'message' => 'ملک ویرایش شد.'
            ]);
        }
    }

// land type controller

    public function searchCities(Request $request)
    {
        $cities = City::select(['id', 'name']);
        if ($request->has('search')) {
            $cities->where('name', 'like', "%{$request->input('search')}%");
        }
        return $cities->get();
    }

    public function searchExperts()
    {
        $page = max(1, request('page', 1));
        $per_page = 1;
        $offset = ($page - 1) * $per_page;
        $query = Admin::role('expert')->select(['id', 'first_name', 'last_name']);
        if (request()->filled('search')) {
            $search = request('search');
            $query->where('first_name', 'like', "%$search%")->orWhere('last_name', 'like', "%$search%");
        }
        $count = $query->count();
        $query->offset($offset)->limit($per_page);
        return response()->json([
            'data' => $query->get()->each->append('display_name'),
            'total' => $count,
            'page' => $page,
            'hasMore' => $per_page + $offset + 1 <= $count
        ]);
    }

//    properties
    public function addProperty(Request $request)
    {
        $data = $this->validate($request, [
            'title' => 'required',
            'order' => 'required|integer',
            'slug' => ['required', Rule::unique('properties'),Rule::notIn(config('spad_setting.block_property', []))],
            'type' => [Rule::in(['text', 'textarea', 'integer', 'boolean', 'select', 'multiselect', 'radio', 'checkbox', 'images', 'geo'])],
            'enum' => 'nullable|array',
            'enumNames' => 'nullable|array',
            'unit' => 'nullable',
            'icon' => ['nullable','array'],
            'separator'=>'boolean'
//            'show_in_crm' => 'boolean',
//            'searchable' => 'boolean',
//            'search_enum' => '',
//            'search_enumNames' => '',
//            'search_operator' => [Rule::requiredIf($request->input('searchable', false)), Rule::in(['lt', 'eq', 'gt', 'like'])],
//            'search_level' => [Rule::requiredIf($request->input('searchable', false)), 'integer'],
        ]);

        if ($data['icon'] && is_array($data['icon']) && count($data['icon'])>0){
            $data['icon']=$data['icon'][0]['id'];
        }else{
            $data['icon']=null;
        }


        if ($property = Property::create($data)) {
            return response()->json([
                'status' => true,
                'message' => 'ویژگی جدید افزوده شد'
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'مشکلی غیر منظره پیش آمده است کمی بعد دوباره تلاش کنید'
        ]);
    }

    public function getEditPropertyForm(Request $request, Property $property)
    {
        $property->load('icon_file');
        if ($property->icon_file){
            $property->icon=[$property->icon_file->only(['id','url'])];
        }else{
            $property->icon=null;
        }
        return $property->withoutRelations('icon_file');
    }

    public function editProperty(Request $request, Property $property)
    {
        $data = $this->validate($request, [
            'title' => 'required',
            'order' => 'required|integer',
            'slug' => ['required', Rule::unique('properties')->ignore($property),Rule::notIn(config('spad_setting.block_property', []))],
            'type' => [Rule::in(['text', 'textarea', 'integer', 'boolean', 'select', 'multiselect', 'radio', 'checkbox', 'images', 'geo'])],
            'enum' => 'nullable|array',
            'enumNames' => 'nullable|array',
            'unit' => 'nullable',
            'icon' => ['nullable','array'],
            'separator'=>'boolean'

//            'show_in_crm' => 'boolean',
//            'show_in_crm' => 'boolean',
//            'searchable' => 'boolean',
//            'search_enum' => [],
//            'search_enumNames' => [],
//            'search_operator' => [Rule::requiredIf($request->input('searchable', false)), Rule::in(['lt', 'eq', 'gt', 'like'])],
//            'search_level' => [Rule::requiredIf($request->input('searchable', false)), 'integer'],
        ]);

        if ($data['icon'] && is_array($data['icon']) && count($data['icon'])>0 && count($data['icon'])>0){
            $data['icon']=$data['icon'][0]['id'];
        }else{
            $data['icon']=null;
        }

        if ($property->update($data)) {
            return response()->json([
                'status' => true,
                'message' => 'ویژگی ویرایش شد'
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'مشکلی غیر منظره پیش آمده است کمی بعد دوباره تلاش کنید'
        ]);
    }

    public function properties_select()
    {
        return Property::select(['id', 'title', 'type', 'slug'])->get();
    }

//    advertisement
    public function advertisementsList(Request $request)
    {
        $advertisements = Advertisement::query()->with(['category', 'city', 'region'/*,'properties'=>function($query){
            $query->with(['property','advertisement.category'])->whereIn('property_slug',['title','gallery']);
        }*/])->orderBy('advertisements.created_at', 'desc');

        if ($request->filled('category') && $category=Category::find($request->input('category'))){
            $advertisements->whereHas('category',function ($query)use($request){
                $query->whereJsonContains('structure',(int)$request->input('category'))->orWhere('id',$request->input('category'));
            });
            $searchItems=userShowCategoryFilter($category,$request);
            if (count($searchItems)>0){
                $advertisements->join('advertisement_properties','advertisements.id','advertisement_properties.advertisement_id')
                    ->join('properties','advertisement_properties.property_slug','properties.slug')
                    ->select('advertisements.*')
                    ->groupBy('advertisements.id');
                $property_condition=[];
                $property_condition_params=[];
                foreach ($searchItems as $searchItem){
                    if (isset($searchItem['range_search']) && $searchItem['range_search']){
                        $value=explode('-',$request->input($searchItem['code']));
                        if (count($value)>1){
                            if ($value[0] && $value[0]!='' && $value[1] && $value[1]!=''){
                                $cnd="properties.slug= ? and ((`properties`.`type`='integer' and `advertisement_properties`.`integer_value` < ? and `advertisement_properties`.`integer_value` > ?) or (`properties`.`type`='select' and `advertisement_properties`.`integer_value` < ? and `advertisement_properties`.`integer_value` > ?) or (`properties`.`type`='radio' and `advertisement_properties`.`integer_value` < ? and `advertisement_properties`.`integer_value` > ?))";
                                $property_condition[]="(sum(case when $cnd then 1 else 0 end) > 0)";
                                array_push($property_condition_params,(string)$searchItem['code'],(int)$value[1],(int)$value[0],(int)$value[1],(int)$value[0],(int)$value[1],(int)$value[0]);
                            }elseif ($value[0] && $value[0]!=''){
                                $cnd="properties.slug= ? and ((`properties`.`type`='integer' and `advertisement_properties`.`integer_value` > ?) or (`properties`.`type`='select' and `advertisement_properties`.`text_value` > ?) or (`properties`.`type`='radio' and `advertisement_properties`.`text_value` > ?))";
                                $property_condition[]="(sum(case when $cnd then 1 else 0 end) > 0)";
                                array_push($property_condition_params,(string)$searchItem['code'],(int)$value[0],(int)$value[0],(int)$value[0]);
                            }elseif ($value[1] && $value[1]!=''){
                                $cnd="properties.slug= ? and ((`properties`.`type`='integer' and `advertisement_properties`.`integer_value` < ?) or (`properties`.`type`='select' and `advertisement_properties`.`integer_value` < ?) or (`properties`.`type`='radio' and `advertisement_properties`.`integer_value` < ?))";
                                $property_condition[]="(sum(case when $cnd then 1 else 0 end) > 0)";
                                array_push($property_condition_params,(string)$searchItem['code'],(int)$value[1],(int)$value[1],(int)$value[1]);
                            }
                        }
                    }else{
                        if (isset($searchItem['property_type'])){
                            switch ($searchItem['property_type']){
                                case 'integer':
                                case 'text':
                                case 'select':
                                case 'radio':
                                    switch ($searchItem['search_operator']){
                                        case 'lt':
                                            $searchOperator='<';
                                            $value=$request->input($searchItem['code']);
                                            break;
                                        case 'gt':
                                            $searchOperator='>';
                                            $value=$request->input($searchItem['code']);
                                            break;
                                        case 'like':
                                            $searchOperator='like';
                                            $value="%{$request->input($searchItem['code'])}%";
                                            break;
                                        default:
                                            $searchOperator='=';
                                            $value=$request->input($searchItem['code']);
                                    }
                                    $cnd="properties.slug= ? and ((`properties`.`type`='integer' and `advertisement_properties`.`integer_value` $searchOperator ?) or (`properties`.`type`='text' and `advertisement_properties`.`text_value` $searchOperator ?) or (`properties`.`type`='textarea' and `advertisement_properties`.`textarea_value` $searchOperator ?))";
                                    $property_condition[]="(sum(case when $cnd then 1 else 0 end) > 0)";
                                    array_push($property_condition_params,(string)$searchItem['code'],$value,$value,$value);
                                    break;
                            }
                        }
                    }
                }
                if (count($property_condition)>0){
                    $advertisements->havingRaw(implode(' and ',$property_condition),$property_condition_params);
                }
            }
        }

        if ($request->filled('id')){
            $advertisements->firstWhere('id',(int)$request->input('id'));
        }

        $page = request('type', 'all');
        $user = request()->user();
        $user_role = $user->roles->first()->name;
        switch ($page) {
            case 'unapproved':
                // Dedicated moderation inbox: only ads that are still waiting
                // for an admin decision. Rejected ads have already been reviewed.
                $advertisements->where('status', Advertisement::WAITING);
                break;
            case 'skill':
                switch ($user_role) {
                    case 'manager':
                        $advertisements->whereHas('category', function ($query) use ($user) {
                            $query->whereIn('id', $user->allowed_categories->pluck('id')->toArray());
                        })->whereHas('city', function ($query) use ($user) {
                            $query->whereIn('id', $user->cities->pluck('id')->toArray());
                        });
                        break;
                    case 'supervisor':
                        $allowed_admins = $user->experts()->select('id')->pluck('id')->toArray();
                        $allowed_admins[] = $user->id;
                        $advertisements->whereHas('admins', function ($query) use ($allowed_admins) {
                            $query->whereIn('admin_id', $allowed_admins);
                        });
                        break;
                    case 'expert':
                        $advertisements->whereHas('admins', function ($query) use ($user) {
                            $query->where('admin_id', $user->id);
                        });
                        break;
                    case 'Super Admin':
                        break;
                }
                break;
            default:
                $advertisements->where('status', Advertisement::APPROVED);
                break;
        }
        return AdminAdvertisementsList::collection($advertisements->cursorPaginate(36)->appends(request()->query()));
    }

    public function getCategories()
    {
        return Category::whereNull('parent')->select(['id','name','parent','slug','icon'])->with('customer_children')->get();
    }

    public function registerAdvertisementForm()
    {
        $propertySideWrappers = PropertyWrapper::query()->order()->side()->get();
        $propertyNormalWrappers = PropertyWrapper::query()->order()->normal()->get();
        $properties = Property::query()->get()->pluck(null, 'slug');
        return response()->json([
            'normal' => $propertyNormalWrappers,
            'side' => $propertySideWrappers,
            'properties' => $properties,
            'important_places'=>config('spad_setting.important_places',[]),
        ]);
    }

    public function panelAdvertisementList(Category $category)
    {
        $layouts = $category->layout;
        $properties = [];
        foreach ($layouts as $layout) {
            if ($layout->layout) {
                foreach ($layout->layout as $item) {
                    getLayoutAllProperties($item, $properties);
                }
            }
        }
        return response()->json([
            'properties' => Property::whereIn('slug', $properties)->select(['title', 'slug'])->get(),
            'ListSetting' => $category->listSetting
        ]);
    }

    public function savePanelAdvertisementList(Request $request, Category $category)
    {
        $category->update([
            'listSetting' => $request->all()
        ]);
    }

    public function showCategoryFilter(Request $request, Category $category)
    {
        $layouts = $category->layout;
        $properties = [];
        foreach ($layouts as $layout) {
            if ($layout->layout) {
                foreach ($layout->layout as $item) {
                    getLayoutAllProperties($item, $properties);
                }
            }
        }
        return response()->json([
            'properties' => Property::whereIn('slug', $properties)->select(['title', 'slug'])->get(),
            'searchItems' => $category->searchItems,
        ]);
    }

    public function saveCategoryFilter(Request $request, Category $category)
    {
        $category->update([
            'searchItems' => $request->input('items')
        ]);
    }

    public function registerAdvertisement(Request $request)
    {
        $shiraz = SpadLocation::city();
        $this->validate($request, [
            'category' => ['required', Rule::exists('categories', 'id')],
            'position.region.id' => ['required', Rule::exists('regions', 'id')->where('city_id', $shiraz->id)],
            'position.address' => 'required',
            'important_places'=>['nullable','array']
        ]);
        $category_id = $request->input('category');
        $category = Category::find($category_id);
        $checkAdvertisement = isValidAdvertisement($category, $request->except('category', 'position'),true,true);
        $status = Advertisement::WAITING;
        if (count($checkAdvertisement['errors']) == 0) {
            $user = $request->user();
            $user_role = $user->roles->first()->name;
            if ($user_role == 'Super Admin'
                || ($user_role == 'manager' && $user->allowed_categories->contains($category) && $user->cities->contains($shiraz->id))
                || (($user_role == 'expert' || $user_role == 'supervisor') && $user->can('reject.advertisement') && $user->allowed_categories->contains($category) &&
                    ((Region::query()->where('city_id', $shiraz->id)->count() == 0 && $user->cities->contains($shiraz->id)) || $user->regions->contains($request->input('position.region.id'))))) {
                $status = Advertisement::APPROVED;
            }

            $advertisement = Advertisement::create([
                'category_id' => $category_id,
                'city_id' => $shiraz->id,
                'region_id' => $request->input('position.region.id'),
                'address' => $request->input('position.address'),
                'status' => $status,
                'writable_id' => $request->user()->id,
                'writable_type' => Admin::class,
                'important_places'=>$request->input('important_places',[])
            ]);
            if ($advertisement) {
                if ($status) {
                    $advertisement->admins()->sync([$user->id]);
                }
                $data = $checkAdvertisement['data'];
                $properties = [];
                foreach ($data as $item) {
                    $adProp=new AdvertisementProperty($item);
                    $adProp->images=$advertisement;
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

    public function rejectAdvertisements(Request $request)
    {
        $user = $request->user();
        $ads = Advertisement::whereIn('id', $request->ads);
        $adsToReject = $ads->pluck('id')->toArray();
        $adsToRejectLength = count($adsToReject);
        DB::table('advertisements')
            ->whereIn('id', $adsToReject)
            ->update([
                'status' => Advertisement::REJECTED,
                'reject_reason' => $request->input('reason', null)
            ]);
        return AdminAdvertisementsList::collection($ads->get())->additional([
            'status' => true,
            'message' => "تعداد {$adsToRejectLength} آگهی رد شد"
        ]);
    }

    public function approveAdvertisements(Request $request)
    {
        $user = $request->user();
        $ads = Advertisement::whereIn('id', $request->ads);
        $adsToReject = $ads->pluck('id')->toArray();
        $adsToRejectLength = count($adsToReject);
        DB::table('advertisements')
            ->whereIn('id', $adsToReject)
            ->update([
                'status' => Advertisement::APPROVED,
                'reject_reason' => null,
            ]);
        return AdminAdvertisementsList::collection($ads->get())->additional([
            'status' => true,
            'message' => "تعداد {$adsToRejectLength} آگهی تایید شد"
        ]);
    }

    public function priorityAdvertisements(Request $request)
    {
        $user = $request->user();
        $ads = Advertisement::whereIn('id', $request->ads);
        $adsToReject = $ads->pluck('id')->toArray();
        $adsToRejectLength = count($adsToReject);
        DB::table('advertisements')
            ->whereIn('id', $adsToReject)
            ->update([
                'priority' => now(),
            ]);
        return AdminAdvertisementsList::collection($ads->get())->additional([
            'status' => true,
            'message' => "تعداد {$adsToRejectLength} آگهی نردبان شد"
        ]);
    }

    public function showAdvertisementExperts(Request $request, Advertisement $advertisement)
    {
        $admins = Admin::query()->role('expert')->whereHas('allowed_categories', function ($query) use ($advertisement) {
            $query->where('category_id', $advertisement->category_id);
        });
        $city = $advertisement->city;
        $region = $advertisement->region;
        if ($region) {
            $admins->whereHas('regions', function ($query) use ($region) {
                $query->where('region_id', $region->id);
            });
        } elseif ($city && $city->regions()->count() == 0) {
            $admins->whereHas('cities', function ($query) use ($city) {
                $query->where('city_id', $city->id);
            });
        }
        $suggestedExperts = $admins->with(['manager' => function ($query) {
            $query->select(['id', 'first_name', 'last_name', 'mobile']);
        }])->select(['id', 'first_name', 'last_name', 'mobile', 'manager_id'])->get();
        $advertisementAdmins = $advertisement->admins->map->only(['id', 'first_name', 'last_name', 'mobile', 'manager_id']);
        return response()->json([
            'suggested' => $suggestedExperts,
            'admins' => $advertisementAdmins
        ]);
    }

    public function allManagerExperts()
    {
        $allowed_cities = request()->user()->cities->pluck('id');
        $allowed_categories = request()->user()->allowed_categories->pluck('id');
        $page = max(1, request('page', 1));
        $per_page = 24;
        $offset = ($page - 1) * $per_page;
        $query = Admin::query()->role('expert')->select(['id', 'first_name', 'last_name', 'mobile', 'manager_id']);
        if (request()->filled('search')) {
            $search = request('search');
            $query->where('first_name', 'like', "%$search%")->orWhere('last_name', 'like', "%$search%");
        }
        $query->whereHas('allowed_categories', function ($query) use ($allowed_categories) {
            $query->whereIn('category_id', $allowed_categories);
        });
        if (!request()->user()->hasRole('Super Admin')) {
            $query->where(function ($query) use ($allowed_cities, $allowed_categories) {
                $query->whereHas('regions', function ($query) use ($allowed_cities) {
                    $query->whereHas('city', function ($query) use ($allowed_cities) {
                        $query->whereIn('id', $allowed_cities);
                    });
                })->orWhereHas('cities', function ($query) use ($allowed_cities) {
                    $query->whereIn('city_id', $allowed_cities);
                });
            });
        }
        $count = $query->count();
        $query->offset($offset)->limit($per_page);
        return response()->json([
            'data' => $query->get(),
            'total' => $count,
            'page' => $page,
            'hasMore' => $per_page + $offset + 1 <= $count
        ]);
    }

    public function transformAdvertisement(Request $request, Advertisement $advertisement)
    {
        $this->validate($request, [
            'experts' => 'array',
        ]);
        $experts = $request->input('experts');
        $advertisement->admins()->sync(array_column($experts, 'id'));
        return response()->json([
            'status' => true,
            'message' => 'آگهی با موفقیت انتقال یافت'
        ]);
    }

    public function editAdvertisementInfo(Request $request, Advertisement $advertisement)
    {
        $category=$advertisement->category;
        $data=$advertisement->properties->each(function ($property){
            return $property->append('value');
        })->pluck('panel_value','property_slug');

        $user = $request->user();
        $user_role = $user->roles->first()->name;
        if ($user_role != 'Super Admin'
            && ($user_role != 'manager' || !$user->allowed_categories->contains($category) || !$user->cities->contains($advertisement->city_id))
            && (!($user_role == 'expert' || $user_role == 'supervisor') || !$user->can('reject.advertisement') || !$user->allowed_categories->contains($category) ||
                !((Region::query()->where('city_id', $advertisement->city_id)->count() == 0 && $user->cities->contains($advertisement->city_id)) || $user->regions->contains($advertisement->region_id)))) {
            abort('403');
        }

        $position=[
            'city'=>$advertisement->city()->select(['id','name'])->withCount('regions')->first(),
            'address'=>$advertisement->address,
        ];

        if ($advertisement->region_id){
            $position['region']=$advertisement->region()->select(['id','name'])->first();
        }

        $data['position']=$position;
        $data['important_places']=$advertisement->important_places;

        return [
            'side'=>$category->layout()->order()->side()->get(),
            'normal'=>$category->layout()->order()->normal()->get(),
            'properties'=>Property::query()->get()->pluck(null,'slug'),
            'data'=>$data,
            'category'=>$category->only(['id','name','slug']),
            'important_places'=>config('spad_setting.important_places',[]),
        ];
    }

    public function editAdvertisement(Request $request, Advertisement $advertisement)
    {
        $shiraz = SpadLocation::city();
        $this->validate($request, [
            'position.region.id' => ['required', Rule::exists('regions', 'id')->where('city_id', $shiraz->id)],
            'position.address' => 'required',
            'important_places'=>['nullable','array']
        ]);
        $category = $advertisement->category;
        $user = $request->user();
        $user_role = $user->roles->first()->name;

        if ($user_role != 'Super Admin'
            && ($user_role != 'manager' || !$user->allowed_categories->contains($category) || !$user->cities->contains($advertisement->city_id))
            && (!($user_role == 'expert' || $user_role == 'supervisor') || !$user->can('reject.advertisement') || !$user->allowed_categories->contains($category) ||
                !((Region::query()->where('city_id', $advertisement->city_id)->count() == 0 && $user->cities->contains($advertisement->city_id)) || $user->regions->contains($advertisement->region_id)))) {
            abort('403');
        }

        $checkAdvertisement = isValidAdvertisement($category, $request->except('category', 'position'),true,true);
        $status = Advertisement::WAITING;
        if (count($checkAdvertisement['errors']) == 0) {
            if ($user_role == 'Super Admin'
                || ($user_role == 'manager' && $user->allowed_categories->contains($category) && $user->cities->contains($shiraz->id))
                || (($user_role == 'expert' || $user_role == 'supervisor') && $user->can('reject.advertisement') && $user->allowed_categories->contains($category) &&
                    ((Region::query()->where('city_id', $shiraz->id)->count() == 0 && $user->cities->contains($shiraz->id)) || $user->regions->contains($request->input('position.region.id'))))) {
                $status = Advertisement::APPROVED;
            }

            $advertisement->update([
                'city_id' => $shiraz->id,
                'region_id' => $request->input('position.region.id'),
                'address' => $request->input('position.address'),
                'status' => $status,
                'important_places'=>$request->input('important_places',null)
            ]);

            $data = $checkAdvertisement['data'];
            $validSlugs = collect($data)->pluck('property_slug')->filter()->values()->all();

            // Keep edit behavior identical to the current registration form: properties that are
            // no longer active because of a dependency/layout change must not remain on the ad.
            $staleProperties = AdvertisementProperty::query()
                ->where('advertisement_id', $advertisement->id);
            if (count($validSlugs) > 0) {
                $staleProperties->whereNotIn('property_slug', $validSlugs);
            }
            $staleProperties->delete();

            foreach ($data as $item) {
                $adProp=AdvertisementProperty::updateOrCreate(
                    ['advertisement_id'=>$advertisement->id,'property_slug'=>$item['property_slug']],
                    $item,
                );
                $adProp->images=$advertisement;
                $adProp->save();
            }

            return response()->json([
                'status' => true,
                'message' => 'تغییرات آگهی با موفقیت ذخیره شد',
                'data' => $data,
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'برخی اطلاعات آگهی معتبر نیستند',
            'errors' => $checkAdvertisement['errors'],
        ]);
    }

    public function displayCategoryFilters(Request $request)
    {
        if ($request->filled('category') && $category = Category::find($request->input('category'))) {
            return response()->json([
                'searchItems' => userShowCategoryFilter($category),
                'categories' => $category->direct_children??[],
                'current' => $category->parent ?$category->parent_category: [
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

    public function displaySingleLayout(Request $request,Category $category)
    {
        $layouts = $category->layout;
        $properties = [];
        foreach ($layouts as $layout) {
            if ($layout->layout) {
                foreach ($layout->layout as $item) {
                    getLayoutAllProperties($item, $properties);
                }
            }
        }
        return response()->json([
            'properties' => Property::whereIn('slug', $properties)->select(['title', 'slug'])->get(),
            'singleLayout' => $category->singleLayout,
        ]);
    }

    public function saveSingleLayout(Request $request, Category $category)
    {
        $category->update([
            'singleLayout' => $request->input('single_layout')
        ]);
    }

    public function showAdvertisementPreview(Request $request,Advertisement $advertisement)
    {
        return userShowCategorySingleLayout($advertisement);
    }

    public function showCategoryMainFilter(Request $request, Category $category)
    {
        $layouts = $category->layout;
        $properties = [];
        foreach ($layouts as $layout) {
            if ($layout->layout) {
                foreach ($layout->layout as $item) {
                    getLayoutAllProperties($item, $properties);
                }
            }
        }
        return response()->json([
            'properties' => Property::whereIn('slug', $properties)->select(['title', 'slug'])->get(),
            'searchItems' => $category->mainFilters,
        ]);
    }

    public function saveCategoryMainFilter(Request $request, Category $category)
    {
        $category->update([
            'mainFilters' => $request->input('home_filters')
        ]);
    }

    /**
     * @OA\Get(
     *      path="/admin/advertisements/all-reports",
     *      operationId="getAdvertisementsReports",
     *      tags={"Admin-Advertisements"},
     *      summary="get advertisements has report",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       )
     *     )
     */
    public function advertisementsHAsReporting()
    {
        $advertisements=Advertisement::query()->whereHas('reports')->withCount('reports');
        return AdminAdvertisementsList::collection($advertisements->cursorPaginate(36)->appends(request()->query()));
    }

    /**
     * @OA\Get(
     *      path="/admin/advertisements/reports/{advertisement}",
     *      operationId="getSpecificAdvertisementReports",
     *      tags={"Admin-Advertisements"},
     *      summary="get specific advertisement reports",
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
    public function advertisementReports(Request $request,Advertisement $advertisement)
    {
        $reports=$advertisement->reports()->with('user:id,first_name,last_name,mobile');
        return AdminAdvertisementReport::collection($reports->paginate());
    }

    /**
     * @OA\Get(
     *      path="/admin/advertisements/find/{advertisement}",
     *      operationId="findMyAdById",
     *      tags={"Admin-Advertisements"},
     *      summary="get specific advertisement by id",
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
    public function findAdById(Request $request,$id)
    {
        $advertisements = Advertisement::query()->with(['category', 'city', 'region'/*,'properties'=>function($query){
            $query->with(['property','advertisement.category'])->whereIn('property_slug',['title','gallery']);
        }*/])->orderBy('advertisements.created_at', 'desc');
        $user = request()->user();
        $user_role = $user->roles->first()->name;
        switch ($user_role) {
            case 'manager':
                $advertisements->whereHas('category', function ($query) use ($user) {
                    $query->whereIn('id', $user->allowed_categories->pluck('id')->toArray());
                })->whereHas('city', function ($query) use ($user) {
                    $query->whereIn('id', $user->cities->pluck('id')->toArray());
                });
                break;
            case 'supervisor':
                $allowed_admins = $user->experts()->select('id')->pluck('id')->toArray();
                $allowed_admins[] = $user->id;
                $advertisements->whereHas('admins', function ($query) use ($allowed_admins) {
                    $query->whereIn('admin_id', $allowed_admins);
                });
                break;
            case 'expert':
                $advertisements->whereHas('admins', function ($query) use ($user) {
                    $query->where('admin_id', $user->id);
                });
                break;
            case 'Super Admin':
                break;
        }
        $ads=$advertisements->firstWhere('id',$id);
        if ($ads){
            return new AdvertisementList($ads);
        }
        throw new ModelNotFoundException();
    }
}
