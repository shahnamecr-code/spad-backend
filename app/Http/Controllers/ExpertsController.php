<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserAdvertisementsList;
use App\Http\Resources\UserExpertInfoSingle;
use App\Http\Resources\UserExpertsList;
use App\Models\Admin;
use App\Models\Advertisement;
use App\Models\Category;
use App\Models\Meeting;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class ExpertsController extends Controller
{
    /**
     * @OA\Get  (
     *      path="/experts/",
     *      operationId="showExperts",
     *      tags={"Experts"},
     *      summary="show all experts",
     *      @OA\Parameter(
     *          name="search",
     *          description="search string",
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *       @OA\Parameter(
     *          name="regions",
     *          description="regions",
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *          )
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
     *     )
     */
    public function index(Request $request)
    {
        $admins=Admin::query()->withAggregate('points as point','point')->withCount(['advertisements'=>function($query){
            $query->publicCatalog()->where('status',Advertisement::APPROVED);
        },'meetings'=>function($query){
            $query->where('status',Meeting::DONE);
        },'crm_requests'])->role([
            'manager',
            'supervisor',
            'expert'
        ]);
        if ($request->filled('search')){
            $admins->where(function ($query)use ($request){
                $search=$request->input('search');
                $query->where('first_name','like',"%$search%")->orWhere('last_name','like',"%$search%");
            });
        }
        if ($request->filled('regions')){
            $regions=$request->input('regions');
            $regions=json_decode($regions,true);
            $admins->where(function ($query)use($regions){
                $query->where(function ($query)use($regions){
                    $query->role('manager')->whereHas('cities',function ($query)use($regions){
                        $query->whereHas('regions',function ($query)use($regions){
                            $query->whereIn('regions.id',$regions);
                        });
                    });
                })->orWhere(function ($query)use($regions){
                    $query->role('supervisor')->whereHas('experts',function ($query)use($regions){
                        $query->whereHas('regions',function ($query)use($regions){
                            $query->whereIn('regions.id',$regions);
                        });
                    });
                })->orWhere(function ($query)use($regions){
                    $query->role('expert')->whereHas('regions',function ($query)use($regions){
                        $query->whereIn('regions.id',$regions);
                    });
                });
            });
        }
        return UserExpertsList::collection($admins->paginate());
    }

    /**
     * @OA\Post (
     *      path="/experts/point/{expert}",
     *      operationId="pointToExpert",
     *      tags={"Experts"},
     *      summary="point for specific expert",
     *      @OA\Parameter(
     *          name="expert",
     *          description="expert id",
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
    public function pointToExpert(Request $request, Admin $admin)
    {
        $data=$this->validate($request,[
            'point'=>['required']
        ]);

        if ($existPoint=$admin->points()->firstWhere('user_id',$request->user()->id)){
            return response()->json([
                'status'=>false,
                'message'=>'امتیاز شما قبلا ثبت شده است'
            ]);
        }

        $point=$admin->points()->create([
            'point'=>$data['point'],
            'user_id'=>$request->user()->id,
        ]);
        if ($point){
            return response()->json([
                'status'=>true,
                'message'=>'امتیاز شما با موفقیت ثبت شد'
            ]);
        }
        return response()->json([
            'status'=>false,
            'message'=>'مشکلی رخ داده است کمی بعد دوباره تلاش کنید'
        ]);
    }

    /**
     * @OA\Get (
     *      path="/experts/info/{expert}",
     *      operationId="ExpertSinglePageInfo",
     *      tags={"Experts"},
     *      summary="get expert single page info",
     *      @OA\Parameter(
     *          name="expert",
     *          description="expert id",
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
     *      ),
     *     )
     */
    public function showExpertInfo(Request $request,Admin $admin)
    {
        return new UserExpertInfoSingle($admin);
    }

    /**
     * @OA\Get (
     *      path="/experts/advertisements/{expert}",
     *      operationId="ExpertAdvertisements",
     *      tags={"Experts"},
     *      summary="get expert advertisements",
     *      @OA\Parameter(
     *          name="expert",
     *          description="expert id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *       @OA\Parameter(
     *          name="category",
     *          description="category slug",
     *          in="query",
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
     *      ),
     *     )
     */
    public function showExpertAds(Request $request, Admin $admin)
    {
        $role=$admin->roles()->first();
        if ($role){
            $role_name=$role->name;
            $advertisements=Advertisement::query()->publicCatalog()->where('status',Advertisement::APPROVED);
            $categories=Category::query()->whereNull('parent')->pluck('name','slug')->toArray();
            if ($categories){
                switch ($role_name){
                    case 'Super Admin':
                        break;
                    case 'manager':
                        $advertisements->whereHas('category', function ($query) use ($admin) {
                            $query->whereIn('id', $admin->allowed_categories->pluck('id')->toArray());
                        })->whereHas('city', function ($query) use ($admin) {
                            $query->whereIn('id', $admin->cities->pluck('id')->toArray());
                        });
                        break;
                    case 'supervisor':
                        $allowed_admins = $admin->experts()->select('id')->pluck('id')->toArray();
                        $allowed_admins[] = $admin->id;
                        $advertisements->whereHas('admins', function ($query) use ($allowed_admins) {
                            $query->whereIn('admin_id', $allowed_admins);
                        });
                        break;
                    case 'expert':
                        $advertisements->whereHas('admins', function ($query) use ($admin) {
                            $query->where('admin_id', $admin->id);
                        });
                        break;
                }
                $category=Category::query()->firstWhere('slug',$request->input('category',array_key_first($categories)));
                if ($category){
                    $advertisements->whereHas('category',function ($query)use ($category){
                        $query->where('id',$category->id)->orWhereJsonContains('structure',$category->id);
                    });
                }else{
                    throw new ModelNotFoundException();
                }
                return UserAdvertisementsList::collection($advertisements->paginate());
            }
        }
    }
}
