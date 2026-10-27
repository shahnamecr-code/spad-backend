<?php

namespace Spad\Crm\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserAdvertisementsList;
use App\Models\Admin;
use App\Models\Advertisement;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Spad\Crm\Models\CrmRequest;
use Spad\Crm\Models\CrmRequestProperties;
use Spad\Crm\Resources\AdminCrmRequestSingle;
use Spad\Crm\Resources\AdminCrmRequestsResource;
use Spad\Crm\Resources\UserCrmRequestResource;

class CrmController extends Controller
{
    public function addCrmRequest(Request $request, Category $category)
    {
        $data=$this->validate($request, [
            'req' => 'required',
            'user' => ['required', Rule::exists('users', 'id')],
            'req.position' => ['required'],
            'req.position.city' => ['required']
        ]);

        $crm_request = CrmRequest::create([
            'category_id' => $category->id,
            'city_id' => $request->req['position']['city']['id'],
            'regions' => isset($request->req['position']['region']) ? array_column($request->req['position']['region'], 'id') : null,
            'user_id' => $request->user,
            'admin_id' => auth()->id(),
        ]);
        if ($crm_request) {
            $layouts=userShowCategoryCrmLayout($category);
            $crm_items=[];
            foreach ($layouts as $loop_layout){
                if (isset($loop_layout['items'])){
                    $crm_items=array_merge($crm_items,$loop_layout['items']);
                }
            }
            $crmFields = array_column($crm_items, null, 'code');            if ($crmFields) {
                foreach ($request->req as $index => $item) {
                    if (isset($crmFields[$index])) {
                        $crmField = $crmFields[$index];
                        $cred = [
                            'property_slug' => $index,
                            'crm_request_id' => $crm_request->id,
                            'type' => $crmField['search_type']
                        ];
                        if ($crmField['property_type'] == 'geo') {
                            $cred['json_value'] = $item;
                        } else if ($crmField['range_search']) {
                            $cred['json_value'] = $item;
                        } else {
                            switch ($crmField['search_type']) {
                                case 'text':
                                case 'select':
                                    $cred['text_value'] = $item;
                                    break;
                                case 'switch':
                                    $cred['boolean_value'] = $item;
                                    break;
                                case 'multi-select':
                                    $cred['json_value'] = $item;
                                    break;
                            }
                        }
                        CrmRequestProperties::create($cred);
                    }
                }
            }
        }

        return response()->json([
            'status'=>true,
            'message'=>'درخواست شما ثبت شد'
        ]);
    }

    public function crmAllCustomers()
    {
        $crm_request = CrmRequest::with(['user:id,first_name,last_name', 'category:id,name', 'city:id,name', 'properties' => function ($query) {
            $query->where('property_slug', 'position');
        }])->orderBy('created_at','desc');
        $type = request('type', 'all');
        switch ($type) {
            case 'my-requests';
                $crm_request->where('admin_id', auth()->id());
                break;
            case 'my-region-requests';
                if (auth()->user()->is_super_admin) {

                } elseif (auth()->user()->is_manager) {
                    $allowed_categories = auth()->user()->allowed_categories->pluck('id')->toArray();
                    $crm_request->whereHas('category', function ($query) use ($allowed_categories) {
                        $query->whereIn('id', $allowed_categories);
                    });
                    $allowed_cities = auth()->user()->cities->pluck('id')->toArray();
                    $crm_request->whereHas('city', function ($query) use ($allowed_cities) {
                        $query->whereIn('id', $allowed_cities);
                    });
                } elseif (auth()->user()->is_supervisor) {
                    $experts = auth()->user()->experts->pluck('id')->toArray();
                    $crm_request->whereIn('admin_id', $experts);
                }
                else{
                    $regions=auth()->user()->regions->pluck('id')->toArray();
                    $crm_request->whereJsonContains('regions',$regions);
                }
            default:

        }
        return AdminCrmRequestsResource::collection($crm_request->cursorPaginate());
    }

    public function showCrmRequest(Request $request, CrmRequest $crm_request)
    {
        return new AdminCrmRequestSingle($crm_request);
    }

    public function getCrmRequestForm(Request $request, CrmRequest $crm_request)
    {
        $properties = $crm_request->properties->pluck(null, 'property_slug');
        $crmFieldsBoxes = $crm_request->category->crmFields;
        $crmFields=[];
        foreach ($crmFieldsBoxes as $crmFieldsBox){
            if (isset($crmFieldsBox['filters'])){
                $crmFields=array_merge($crmFields,$crmFieldsBox['filters']);
            }
        }
        if ($crmFields)
            $crmFields = array_column($crmFields, null, 'property_id');
        $info = [];
        foreach ($properties as $index => $property) {
            if (isset($crmFields[$index])) {
                $info[$index] = crmEditPageProperties($property, $crmFields[$index]);
            }
        }
        return [
            'crmFields' => userShowCategoryCrmLayout($crm_request->category),
            'data' => $info
        ];
    }

    public function editCrmRequest(Request $request, CrmRequest $crm_request)
    {
        $this->validate($request, [
            'req' => 'required',
            'req.position' => ['required'],
            'req.position.city' => ['required']
        ]);

        try {
//            DB::transaction(function () use ($request, $crm_request) {
                $category = $crm_request->category;
                $crmFieldsWrappers = userShowCategoryCrmLayout($category);
                $crmFields=[];
                foreach ($crmFieldsWrappers as $wrapper){
                    if (isset($wrapper['items'])){
                        $crmFields=array_merge($crmFields,$wrapper['items']);
                    }
                }
                $crmFields=array_column($crmFields, null, 'code');
                if ($crmFields) {
                    foreach ($request->req as $index => $item) {
                        if (isset($crmFields[$index])) {
                            $crmField = $crmFields[$index];
                            $cred = [
                                'type' => $crmField['search_type']
                            ];
                            if ($crmField['property_type'] == 'geo') {
                                $cred['json_value'] = $item;
                            } else if ($crmField['range_search']) {
                                $cred['json_value'] = $item;
                            } else {
                                switch ($crmField['search_type']) {
                                    case 'text':
                                    case 'select':
                                        $cred['text_value'] = $item;
                                        break;
                                    case 'switch':
                                        $cred['boolean_value'] = $item;
                                        break;
                                    case 'multi-select':
                                        $cred['json_value'] = $item;
                                        break;
                                }
                            }
                            CrmRequestProperties::updateOrCreate(
                                ['property_slug' => $index, 'crm_request_id' => $crm_request->id,], $cred
                            );
                        }
                    }
                    $crm_request->update([
                        'city_id' => $request->req['position']['city']['id'],
                        'regions' => isset($request->req['position']['region']) ? array_column($request->req['position']['region'], 'id') : null,
                    ]);
                }
//            });
            return new AdminCrmRequestsResource($crm_request);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'مشکلی در ثبت اطلاعات رخ داده است کمی بعد دوباره تلاش کنید'
            ]);
        }

    }

    public function allAdmins()
    {
        $allowed_cities = request()->user()->cities->pluck('id');
        $allowed_categories = request()->user()->allowed_categories->pluck('id');
        $page = max(1, request('page', 1));
        $per_page = 24;
        $offset = ($page - 1) * $per_page;
        $query = Admin::query()->select(['id', 'first_name', 'last_name', 'mobile', 'manager_id']);
        if (request()->filled('search')) {
            $search = request('search');
            $query->where('first_name', 'like', "%$search%")->orWhere('last_name', 'like', "%$search%");
        }
        /*        $query->whereHas('allowed_categories', function ($query) use ($allowed_categories) {
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
                }*/
        $count = $query->count();
        $query->offset($offset)->limit($per_page);
        return response()->json([
            'data' => $query->get(),
            'total' => $count,
            'page' => $page,
            'hasMore' => $per_page + $offset + 1 <= $count
        ]);
    }

    public function transferRequest(Request $request, CrmRequest $crm_request)
    {
        $this->validate($request, [
            'admin' => 'required|array',
            'admin.id' => 'required'
        ]);

        if ($crm_request->update([
            'admin_id' => $request->admin['id']
        ])) {
            return new AdminCrmRequestsResource($crm_request);
        }

    }

    public function suggest(Request $request,CrmRequest $crm_request)
    {
        $category=$crm_request->category;
        $searchItemsWrappers=userShowCategoryCrmLayout($category);
        $searchItems=[];
        foreach ($searchItemsWrappers as $itemsWrapper){
            if (isset($itemsWrapper['items'])){
                $searchItems=array_merge($searchItems,$itemsWrapper['items']);
            }
        }
        $searchItems=array_column($searchItems,null,'code');
        $advertisements=Advertisement::query()
            ->where('status',Advertisement::APPROVED)
            ->join('advertisement_properties','advertisements.id','advertisement_properties.advertisement_id')
            ->join('properties','advertisement_properties.property_slug','properties.slug')
            ->select('advertisements.*')
            ->groupBy('advertisements.id');
        $property_condition=[];
        $property_condition_params=[];
        foreach ($crm_request->properties as $property){
            if (isset($property['property_slug']) && isset($searchItems[$property['property_slug']])){
                $filter_column=null;
                $advertisement_property=$property->property;
                switch ($advertisement_property->type){
                    case 'textarea':
                        $filter_column='textarea_value';
                        break;
                    case 'integer':
                        $filter_column='integer_value';
                        break;
                    case 'boolean':
                        $filter_column='boolean_value';
                        break;
                    case 'text':
                    case 'select':
                    case 'radio':
                        $filter_column='text_value';
                        break;
                    case 'multiselect':
                    case 'checkbox':
                    case 'images':
                        $filter_column='json_value';
                        break;
                }
                if (!is_null($filter_column)){
                    $item=$searchItems[$property['property_slug']];
                    if (isset($item['range_search']) && $item['range_search']){
                        $value=$property->json_value;

                        if ($value['from'] && $value['from']!='' && $value['to'] && $value['to']!=''){
                            $cnd="properties.slug= ? and (`advertisement_properties`.`integer_value` < ? and `advertisement_properties`.`$filter_column` > ?)";
                            $property_condition[]="(sum(case when $cnd then 1 else 0 end) > 0)";
                            array_push($property_condition_params,(string)$property['property_slug'],(int)$value['to'],(int)$value['from']);
                        }elseif ($value['from'] && $value['from']!=''){
                            $cnd="properties.slug= ? and `advertisement_properties`.`$filter_column` > ?";
                            $property_condition[]="(sum(case when $cnd then 1 else 0 end) > 0)";
                            array_push($property_condition_params,(string)$property['property_slug'],(int)$value['from']);
                        }elseif ($value['to'] && $value['to']!=''){
                            $cnd="properties.slug= ? and `advertisement_properties`.`$filter_column` < ?";
                            $property_condition[]="(sum(case when $cnd then 1 else 0 end) > 0)";
                            array_push($property_condition_params,(string)$property['property_slug'],(int)$value['to']);
                        }
                    }else{
                        $value=$property->{$filter_column};
                        $search_operator='eq';
                        if (isset($item['search_operator'])){
                            $search_operator=$item['search_operator'];
                        }

                        switch ($property->type){
                            case 'text':
                            case 'select':
                            case 'checkbox':
                                switch ($search_operator){
                                    case 'like':
                                        $cnd="properties.slug= ? and `advertisement_properties`.`$filter_column` like ?";
                                        $property_condition[]="(sum(case when $cnd then 1 else 0 end) > 0)";
                                        array_push($property_condition_params,(string)$property['property_slug'],"%$value%");
                                        break;
                                    case 'gt':
                                        $cnd="properties.slug= ? and `advertisement_properties`.`$filter_column` > ?";
                                        $property_condition[]="(sum(case when $cnd then 1 else 0 end) > 0)";
                                        array_push($property_condition_params,(string)$property['property_slug'],$value);
                                        break;
                                    case 'lt':
                                        $cnd="properties.slug= ? and `advertisement_properties`.`$filter_column` < ?";
                                        $property_condition[]="(sum(case when $cnd then 1 else 0 end) > 0)";
                                        array_push($property_condition_params,(string)$property['property_slug'],$value);
                                        break;
                                    default:
                                        $cnd="properties.slug= ? and `advertisement_properties`.`$filter_column` = ?";
                                        $property_condition[]="(sum(case when $cnd then 1 else 0 end) > 0)";
                                        array_push($property_condition_params,(string)$property['property_slug'],$value);
                                        break;
                                }
                                break;
                            case 'multi-select':
                                $value=$property->json_value;
                                $cnd="properties.slug= ? and `advertisement_properties`.`$filter_column` in (?)";
                                $property_condition[]="(sum(case when $cnd then 1 else 0 end) > 0)";
                                array_push($property_condition_params,(string)$property['property_slug'],implode(', ',$value??[]));
                                break;
                        }
                    }
                }
                }
        }
        if (count($property_condition)>0){
            $advertisements->havingRaw(implode(' and ',$property_condition),$property_condition_params);
        }
        if ($crm_request->regions && count($crm_request->regions)>0){
            $advertisements->whereIn('region_id',$crm_request->regions);
        }elseif ($crm_request->city_id){
            $advertisements->where('city_id',$crm_request->city_id);
        }
        $advertisements->whereHas('category',function ($query)use($crm_request){
            $query->where('id',$crm_request->category_id)->orWhereJsonContains('structure',$crm_request->category_id);
        });
        return UserAdvertisementsList::collection($advertisements->paginate());
    }

    public function shareAdsWithCustomer(Request $request,CrmRequest $crm_request)
    {
        return response()->json([
            'status'=>true,
            'message'=>'لینک آگهی ها برای مشتری ارسال گردید'
        ]);
    }

    public function userRequests(Request $request)
    {
        $requests=$request->user()->crm_requests();
        return UserCrmRequestResource::collection($requests->paginate());
    }

}
