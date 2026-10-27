<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminAdvertisementsList;
use App\Models\Advertisement;
use App\Models\Category;
use App\Models\ExpertRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spad\Crm\Models\CrmRequest;

class DashboardController extends Controller
{
    /**
     * @OA\Get(
     *      path="/admin/dashboard",
     *      operationId="getAdminDashboard",
     *      tags={"Admin-Advertisements"},
     *      summary="admin dashboard",
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
    public function index()
    {
        $data=[];
        $categories=Category::query()->whereNull('parent')->select(['id','name','slug'])->get();
        $advertisements = Advertisement::query()->with(['category', 'city', 'region'])
            ->orderBy('advertisements.created_at', 'desc');
        $expert_requests=ExpertRequest::query();
        $crm_requests=CrmRequest::query();

        $user = request()->user();
        $user_role = $user->roles->first()->name;

        switch ($user_role) {
            case 'manager':
                $advertisements->whereHas('category', function ($query) use ($user) {
                    $query->whereIn('id', $user->allowed_categories->pluck('id')->toArray());
                })->whereHas('city', function ($query) use ($user) {
                    $query->whereIn('id', $user->cities->pluck('id')->toArray());
                });
                $expert_requests->whereHas('category', function ($query) use ($user) {
                    $query->whereIn('id', $user->allowed_categories->pluck('id')->toArray());
                })->whereHas('city', function ($query) use ($user) {
                    $query->whereIn('id', $user->cities->pluck('id')->toArray());
                });
                $crm_requests->where('admin_id',$user->id)->orWhere(function ($query)use($user){
                    $query->whereHas('category', function ($query) use ($user) {
                        $query->whereIn('id', $user->allowed_categories->pluck('id')->toArray());
                    })->whereHas('city', function ($query) use ($user) {
                        $query->whereIn('id', $user->cities->pluck('id')->toArray());
                    });
                });
                break;
            case 'supervisor':
                $allowed_admins = $user->experts()->select('id')->pluck('id')->toArray();
                $allowed_admins[] = $user->id;
                $advertisements->whereHas('admins', function ($query) use ($allowed_admins) {
                    $query->whereIn('admin_id', $allowed_admins);
                });
                $expert_requests->whereHas('category', function ($query) use ($user) {
                    $query->whereIn('id', $user->allowed_categories->pluck('id')->toArray());
                })->whereHas('city', function ($query) use ($user) {
                    $query->whereIn('id', $user->cities->pluck('id')->toArray());
                });
                $crm_requests->where('admin_id',$user->id);
                break;
            case 'expert':
                $advertisements->whereHas('admins', function ($query) use ($user) {
                    $query->where('admin_id', $user->id);
                });
                $expert_requests->whereHas('category', function ($query) use ($user) {
                    $query->whereIn('id', $user->allowed_categories->pluck('id')->toArray());
                })->whereHas('city', function ($query) use ($user) {
                    $query->whereIn('id', $user->cities->pluck('id')->toArray());
                });
                $crm_requests->where('admin_id',$user->id);
                break;
            case 'Super Admin':
                break;
        }

        $ads_data_by_category=[
            'label'=>[],
            'value'=>[],
        ];

        foreach ($categories as $category){
            $category_ads=clone $advertisements;
            $ads_data_by_category['label'][]=$category->name;
            $category_ads->whereHas('category',function ($query)use($category){
                $query->where('id',$category->id)->orWhereJsonContains('structure',$category->id);
            });
            $ads_data_by_category['value'][]=$category_ads->count();
        }

        $ads_by_state=[
            'label'=>['تایید شده','رد شده','در انتظار تایید'],
        ];
        $approved_ads=clone $advertisements;
        $approved_ads->where('status',Advertisement::APPROVED);
        $rejected_ads=clone $advertisements;
        $rejected_ads->where('status',Advertisement::REJECTED);
        $waiting_ads=clone $advertisements;
        $waiting_ads->where('status',Advertisement::WAITING);
        $ads_by_state['value']=[
            $approved_ads->count(),
            $rejected_ads->count(),
            $waiting_ads->count(),
        ];


        $crm_requests_data_by_category=[
            'label'=>[],
            'value'=>[],
        ];
        foreach ($categories as $category){
            $category_crm_requests=clone $crm_requests;
            $crm_requests_data_by_category['label'][]=$category->name;
            $category_crm_requests->whereHas('category',function ($query)use($category){
                $query->where('id',$category->id)->orWhereJsonContains('structure',$category->id);
            });
            $crm_requests_data_by_category['value'][]=$category_crm_requests->count();
        }

        $expert_requests_data_by_category=[
            'label'=>[],
            'value'=>[],
        ];
        foreach ($categories as $category){
            $expert_requests=clone $expert_requests;
            $expert_requests_data_by_category['label'][]=$category->name;
            $expert_requests->whereHas('category',function ($query)use($category){
                $query->where('id',$category->id)->orWhereJsonContains('structure',$category->id);
            });
            $expert_requests_data_by_category['value'][]=$expert_requests->count();
        }

        $times=getTimes('week');
        $ads_by_time=[
            'label'=>[],
            'value'=>[],
        ];
        foreach ($times as $time){
            $category_ads=clone $advertisements;
            $ads_by_time['label'][]=$time['label'];
            $ads_by_time['value'][]=$category_ads->where('created_at','>',$time['start'])
                ->where('created_at','<',$time['end'])->count();
        }

        $data['ads_by_time']=$ads_by_time;

//
        $crm_requests_by_time=[
            'label'=>[],
            'value'=>[],
        ];
        foreach ($times as $time){
            $category_ads=clone $advertisements;
            $crm_requests_by_time['label'][]=$time['label'];
            $crm_requests_by_time['value'][]=$category_ads->where('created_at','>',$time['start'])
                ->where('created_at','<',$time['end'])->count();
        }

        $data['crm_requests_by_time']=$crm_requests_by_time;

        $data['all_ads_count']=$advertisements->count();
        $data['expert_requests_count']=$expert_requests->count();
        $data['crm_requests_count']=$crm_requests->count();
        $data['ads_by_category']=$ads_data_by_category;
        $data['crm_requests_by_category']=$crm_requests_data_by_category;
        $data['expert_requests_by_category']=$expert_requests_data_by_category;
        $data['last_ads']=AdminAdvertisementsList::collection($advertisements->take(8)->get())->resource;
        $data['ads_by_state']=$ads_by_state;

        return response()->json($data);

    }

    /**
     * @OA\Get(
     *      path="/admin/dashboard/AdsStatistics/{type}",
     *      operationId="getAdminDashboardAdsStatistics",
     *      tags={"Admin-Advertisements"},
     *      summary="admin dashboard AdsStatistics",
     *      @OA\Parameter(
     *          name="type",
     *          description="time type week|month|year",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
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
    public function getLastAds(Request $request,$type)
    {
        $this->validate($request,[
            'type'=>Rule::in(['week','month','year'])
        ]);

        $advertisements = Advertisement::query()->with(['category', 'city', 'region'])
            ->orderBy('advertisements.created_at', 'desc');

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

        $times=getTimes($type);
        $ads_by_time=[
            'label'=>[],
            'value'=>[],
        ];
        foreach ($times as $time){
            $category_ads=clone $advertisements;
            $ads_by_time['label'][]=$time['label'];
            $ads_by_time['value'][]=$category_ads->where('created_at','>',$time['start'])
                ->where('created_at','<',$time['end'])->count();
        }

        return $ads_by_time;
    }

    /**
     * @OA\Get(
     *      path="/admin/dashboard/CrmRequestsStatistics/{type}",
     *      operationId="getAdminDashboardCrmRequestsStatistics",
     *      tags={"Admin-Advertisements"},
     *      summary="admin dashboard CrmRequestsStatistics",
     *      @OA\Parameter(
     *          name="type",
     *          description="time type week|month|year",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
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
    public function getLastCrmRequests(Request $request,$type)
    {
        $this->validate($request,[
            'type'=>Rule::in(['week','month','year'])
        ]);

        $crm_requests=CrmRequest::query();


        $user = request()->user();
        $user_role = $user->roles->first()->name;

        switch ($user_role) {
            case 'manager':
                $crm_requests->where('admin_id',$user->id)->orWhere(function ($query)use($user){
                    $query->whereHas('category', function ($query) use ($user) {
                        $query->whereIn('id', $user->allowed_categories->pluck('id')->toArray());
                    })->whereHas('city', function ($query) use ($user) {
                        $query->whereIn('id', $user->cities->pluck('id')->toArray());
                    });
                });
                break;
            case 'supervisor':
                $allowed_admins = $user->experts()->select('id')->pluck('id')->toArray();
                $allowed_admins[] = $user->id;
                $crm_requests->where('admin_id',$user->id);
                break;
            case 'expert':
                $crm_requests->where('admin_id',$user->id);
                break;
            case 'Super Admin':
                break;
        }

        $times=getTimes($type);
        $crm_requests_by_time=[
            'label'=>[],
            'value'=>[],
        ];
        foreach ($times as $time){
            $category_ads=clone $crm_requests;
            $crm_requests_by_time['label'][]=$time['label'];
            $crm_requests_by_time['value'][]=$category_ads->where('created_at','>',$time['start'])
                ->where('created_at','<',$time['end'])->count();
        }

        return $crm_requests_by_time;
    }
}
