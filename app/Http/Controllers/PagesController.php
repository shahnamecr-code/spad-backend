<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Advertisement;
use App\Models\Contact;
use App\Models\Region;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;

class PagesController extends Controller
{
    /**
     * @OA\Get(
     *      path="/about",
     *      operationId="aboutPage",
     *      tags={"Pages"},
     *      summary="get about page",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       )
     *     )
     */
    public function about()
    {
        $options=Setting::where('group','about_page')->orWhere('code','user_default_profile')->get()->pluck('user_show','code');
        $options['experts_count']=Admin::query()->count();
        $options['regions_count']=Region::query()->count();
        $options['users_count']=User::query()->count();
        $options['ads_count']=Advertisement::query()->count();
        return $options;
    }

    /**
     * @OA\Post (
     *      path="/contact",
     *      operationId="submitContact",
     *      tags={"Pages"},
     *      summary="submit contact form",
     *      @OA\RequestBody(
     *          required=true,
     *          description="Pass user credentials",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="name",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="mobile",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="subject",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="content",
     *                  type="string"
     *              )
     *         )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       )
     *     )
     */
    public function create_contact(Request $request)
    {
        $data=$this->validate($request,[
            'name'=>'required',
            'mobile'=>'required',
            'subject'=>'required',
            'content'=>'required',
        ]);

        if ($contact=Contact::create($data)){
            return response()->json([
                'status'=>true,
                'message'=>'درخواست شما با موفقیت ثبت شد'
            ]);
        }else{
            return response()->json([
                'status'=>false,
                'message'=>'مشکلی در ثبت درخواست شما رخ داده است. کمی بعد مجددا تلاش کنید'
            ]);
        }
    }
}
