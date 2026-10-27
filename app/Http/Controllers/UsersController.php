<?php

namespace App\Http\Controllers;

use App\Events\UserRegisterEvent;
use App\Http\Resources\AdvertisementListResource;
use App\Http\Resources\FavoriteResource;
use App\Http\Resources\LoginUserResource;
use App\Http\Resources\OwnerAdvertisementsList;
use App\Http\Resources\UserExpertRequestResource;
use App\Models\Advertisement;
use App\Models\AdvertisementProperty;
use App\Models\Directory;
use App\Models\EmployRequest;
use App\Models\ExpertRequest;
use App\Models\Favorite;
use App\Models\LoginRequest;
use App\Models\User;
use App\Support\SpadLocation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UsersController extends Controller
{
    private function userPayload(User $user)
    {
        $payload = $user->toArray();
        $payload['partner_panels'] = $user->partnerPanels()
            ->active()
            ->orderBy('id')
            ->get()
            ->map(function ($panel) {
                return $panel->publicPayload();
            })
            ->values()
            ->all();
        $payload['has_partner_panel'] = count($payload['partner_panels']) > 0;

        return $payload;
    }

    /**
     * @OA\Get(
     *      path="/user",
     *      operationId="getUserInfo",
     *      tags={"User"},
     *      summary="user information",
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
    public function getUser(Request $request)
    {
        return response()->json($this->userPayload($request->user()));
    }

    /**
     * @OA\Post (
     *      path="/sendLoginCode",
     *      operationId="sendLoginCode",
     *      tags={"Authorization"},
     *      summary="sms code for login",
     *       @OA\RequestBody(
     *          required=true,
     *          description="SMS Login Code",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="mobile",
     *                  type="string",
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent()
     *     ),
     *      @OA\Response(
     *          response=404,
     *          description="Not Found Exception",
     *          @OA\JsonContent()
     *       )
     *     )
     */
    public function sendLoginCode(Request $request)
    {
        $data = $this->validate($request, [
            'mobile' => ['required'],
        ]);

        $data['mobile'] = fix_mobile($data['mobile']);
        $user = User::firstWhere('mobile', $data['mobile']);
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'قبلا ثبت نام نکرده اید'
            ]);
        }
        $user_request = LoginRequest::firstWhere('mobile', $data['mobile']);
/*        if ($user_request && $user_request->updated_at->addSeconds(120)->isFuture()) {
            return response()->json([
                'status' => false,
                'message' => 'لطفا تا رسیدن کد شکیبا باشید...',
                'waiting'=>true,
                'remaining'=>$user_request->updated_at->addSeconds(120)->diffInSeconds((new Carbon()))
            ]);
        }*/

        $token = createToken(6);
        $login_request = LoginRequest::updateOrCreate(
            ['mobile' => $data['mobile']],
            ['token' => $token],
        );

        if ($login_request) {
//            $msg = "کد تأیید شما:\n";
//            $msg .= "code: $token\n";
//            $msg .= "این کد مختص شماست. لطفاً برای دیگران نفرستید.";
//            sms()->send($msg, function ($sms) use ($data, $token) {
//                $sms->to(array($data['mobile']));
//            });

            $executed=RateLimiter::attempt(
                'send-message:'.$user->id,
                $perDay = 3,
                function()use($data,$token) {
                    sms()->send('hi', function ($sms) use($data,$token){
                        $sms->to(array($data['mobile']));
                        $sms->template(config('sms.login_template'),array(
                            "code" => $token,
                        ));
                    });
                }
            );

            if (!$executed){
                return response()->json([
                    'status' => false,
                    'message' => 'ورود بیش از حد مجاز'
                ]);
            }else{
                return response()->json([
                    'status' => true,
                    'message' => 'جهت ورود کد ارسال شده را وارد نمایید'
                ]);
            }
        }

        return response()->json([
            'status' => false,
            'message' => 'مشکلی در برقراری ارتباط پیش آمده است کمی بعد دوباره امتحان کنید'
        ]);
    }

    /**
     * @OA\Post (
     *      path="/login",
     *      operationId="loginWithSmsCode",
     *      tags={"Authorization"},
     *      summary="login with sms code",
     *       @OA\RequestBody(
     *          required=true,
     *          description="Enter SMS Code to Login",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="mobile",
     *                  type="string",
     *              ),
     *              @OA\Property(
     *                  property="code",
     *                  type="string",
     *              ),
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent()
     *     ),
     *      @OA\Response(
     *          response=404,
     *          description="Not Found Exception",
     *          @OA\JsonContent()
     *       )
     *     )
     */
    public function login(Request $request)
    {
        $data = $this->validate($request, [
            'mobile' => 'required',
            'code' => 'required',
        ]);
        $data['mobile'] = fix_mobile($data['mobile']);
        $user = User::firstWhere('mobile', $data['mobile']);
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'برای ورود به سایت ابتدا باید ثبت نام کنید'
            ]);
        }
        $login_request = LoginRequest::where('token', $data['code'])->firstWhere('mobile', $data['mobile']);
        if (!$login_request) {
            return response()->json([
                'status' => false,
                'message' => 'کد وارد شده معتبر نمی باشد'
            ]);
        }

        if ($login_request->updated_at->addSeconds(120)->isPast()) {
            return response()->json([
                'status' => false,
                'message' => 'کد وارد شده منقضی شده است'
            ]);
        }

//            $user->tokens()->where('name', 'token')->delete();
        if (is_null($user->phone_verified_at)){
            $user->update([
                'phone_verified_at'=>now()
            ]);
        }
        $token = $user->createToken('token')->plainTextToken;
        $login_request->delete();
        return response()->json([
            'status' => true,
            'message' => 'ورود با موفقیت انجام شد',
            'token' => $token,
            'user' => $this->userPayload($user)
        ]);
    }

    /**
     * @OA\Post (
     *      path="/loginWithPass",
     *      operationId="loginWithPhoneAndPass",
     *      tags={"Authorization"},
     *      summary="login with phone and pass",
     *       @OA\RequestBody(
     *          required=true,
     *          description="Enter SMS Code to Login",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="mobile",
     *                  type="string",
     *              ),
     *              @OA\Property(
     *                  property="password",
     *                  type="string",
     *              ),
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent()
     *     ),
     *      @OA\Response(
     *          response=404,
     *          description="Not Found Exception",
     *          @OA\JsonContent()
     *       )
     *     )
     */
    public function loginWithPhoneAndPass(Request $request)
    {
        $data=$this->validate($request,[
            'mobile'=>'required',
            'password'=>'required'
        ]);

        $user=User::query()->firstWhere('mobile',fix_mobile($data['mobile']));
        if ($user && Hash::check($data['password'],$user->password)){
            if (is_null($user->phone_verified_at)){
                return response()->json([
                    'status'=>false,
                    'redirect'=>true,
                    'message'=>'ابتدا شماره تلفن خود را تایید کنید'
                ]);
            }
            $token = $user->createToken('token')->plainTextToken;
            return response()->json([
                'status' => true,
                'message' => 'ورود با موفقیت انجام شد',
                'token' => $token,
                'user' => $this->userPayload($user)
            ]);
        }
        return response()->json([
            'status'=>false,
            'message'=>'اطلاعات ورود نامعتبر است'
        ]);
    }

    /**
     * @OA\Post (
     *      path="/register",
     *      operationId="registerUsers",
     *      tags={"Authorization"},
     *      summary="register users",
     *       @OA\RequestBody(
     *          required=true,
     *          description="Register User",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="first_name",
     *                  type="string",
     *              ),
     *              @OA\Property(
     *                  property="last_name",
     *                  type="string",
     *              ),
     *              @OA\Property(
     *                  property="email",
     *                  type="string",
     *              ),
     *              @OA\Property(
     *                  property="mobile",
     *                  type="string",
     *              ),
     *              @OA\Property(
     *                  property="password",
     *                  type="string",
     *              ),
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent()
     *     ),
     *      @OA\Response(
     *          response=404,
     *          description="Not Found Exception",
     *          @OA\JsonContent()
     *       )
     *     )
     */
    public function register(Request $request)
    {
        $data = $this->validate($request, [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => ['nullable', 'email'],
            'mobile' => ['required',Rule::unique('users')],
            'password' => ['required']
        ]);

        $data['mobile'] = fix_mobile($data['mobile']);

        if ($user = User::create($data)) {
            event(new UserRegisterEvent($user));
            return response()->json([
                'status' => true,
                'message' => 'ثبت نام شما با موفقیت انجام شد'
            ]);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['status' => true, 'message' => 'با موفقیت خارج شدید']);
    }

    public function changeProfile(Request $request)
    {
        $data = $this->validate($request, [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => ['nullable', 'email'],
        ]);
        if ($request->user()->update($data)) {
            return response()->json([
                'status' => true,
                'user'=>$this->userPayload($request->user()->fresh()),
                'message' => 'اطلاعات شما با موفقیت به روز رسانی شد'
            ]);
        }
        return response()->json([
            'status' => false,
            'message' => 'مشکلی در برقراری ارتباط پیش آمده است کمی بعد دوباره امتحان کنید'
        ]);
    }

    public function employ_request(Request $request)
    {
        $shiraz = SpadLocation::city();
        $data=$this->validate($request,[
            'name'=>['required'],
            'mobile'=>['required'],
            'second_mobile'=>['required'],
            'postal_code'=>['required'],
            'address'=>['required'],
            'study_field'=>['required'],
            'study_degree'=>['required','integer','min:0','max:4'],
            'job_demand'=>['required','integer','min:0','max:8'],
            'work_experience'=>['nullable','array'],
            'guarantee'=>['required','boolean'],
            'description'=>['nullable']
        ]);
        $data['province_id'] = $shiraz->province_id;
        $data['city_id'] = $shiraz->id;

        if ($employ=EmployRequest::create($data)){
            return response()->json([
                'status'=>true,
                'message'=>'درخواست شما ثبت شد.'
            ]);
        }
    }

    public function guest_expert_request(Request $request)
    {
        $shiraz = SpadLocation::city();
        $data=$this->validate($request,[
            'first_name'=>['required'],
            'last_name'=>['required'],
            'mobile'=>['required'],
            'category_id'=>['required',Rule::exists('categories','id')],
            'region_id'=>['nullable',Rule::exists('regions','id')->where('city_id', $shiraz->id)],
            'address'=>['required'],
        ]);
        $data['province_id'] = $shiraz->province_id;
        $data['city_id'] = $shiraz->id;

        $data['guest']=true;
        if (ExpertRequest::create($data)){
            return response()->json([
                'status'=>true,
                'message'=>'درخواست شما برای کارشناسان ملک ثبت شد. کارشناسان ما به زودی با شما تماس می گیرند'
            ]);
        }
        return response()->json([
            'status'=>false,
            'message'=>'مشکلی در ثبت درخواست رخ داده است کمی بعد دوباره تلاش کنید'
        ]);

    }

    public function users_expert_request(Request $request)
    {
        $shiraz = SpadLocation::city();
        $data=$this->validate($request,[
            'category_id'=>['required',Rule::exists('categories','id')],
            'region_id'=>['nullable',Rule::exists('regions','id')->where('city_id', $shiraz->id)],
            'address'=>['required'],
        ]);
        $data['province_id'] = $shiraz->province_id;
        $data['city_id'] = $shiraz->id;

        $data['guest']=false;
        $data['user_id']=$request->user()->id;
        if (ExpertRequest::create($data)){
            return response()->json([
                'status'=>true,
                'message'=>'درخواست شما برای کارشناسان ملک ثبت شد. کارشناسان ما به زودی با شما تماس می گیرند'
            ]);
        }
        return response()->json([
            'status'=>false,
            'message'=>'مشکلی در ثبت درخواست رخ داده است کمی بعد دوباره تلاش کنید'
        ]);

    }

    /**
     * @OA\Post(
     *     path="/upload",
     *     tags={"User"},
     *     summary="Upload a file",
     *     description="Upload a file",
     *     operationId="userUploadFile",
     *     @OA\MediaType(mediaType="multipart/form-data"),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid Credentials",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized User",
     *         @OA\JsonContent()
     *     ),
     *     @OA\RequestBody(
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *               type="object",
     *               @OA\Property(
     *                  property="file",
     *                  type="file"
     *               )
     *           ),
     *       )
     *     ),
     *     security={{ "bearer_token": {} }}
     * )
     */
    public function uploadAdImage(Request $request)
    {
        $directory = null;

        if (Storage::disk('public')->exists('userAds')){
            $directory=Directory::whereNull('parent')->firstWhere('name','userAds');
        }else{
            Storage::disk('public')->makeDirectory('/userAds');
            $credentials=[
                'name'=>'userAds',
                'path'=>'/',
                'parent'=>null
            ];
            $directory=Directory::create($credentials);
        }

        $name = $request->file->getClientOriginalName();
        $name = str_replace('.' . $request->file->extension(), '_' . rand(100,10000) . '.' . $request->file->extension(), $name);
        $name=str_replace(' ','_',$name);
        $type = $request->file->getMimeType();
        $size = $request->file->getSize();

        $credentials = [
            'directory_id' => $directory ? $directory->id : null,
            'name' => $name,
            'type' => $type,
            'size' => $size,
        ];


        $file_path = ($directory ? $directory->full_path : '/') . $name;

        if (Storage::disk('public')->exists($file_path)) {
            return response()->json([
                'status' => false,
                'message' => 'فایلی با همین نام وجود دارد'
            ]);
        }

        $file=auth()->user()->files()->create($credentials);

        if ($file &&
            $request->file->storeAs($directory ? $directory->full_path : '/', $name, 'public')) {
            return response()->json([
                'status' => true,
                'message' => 'آپلود انجام شد',
                'file'=>$file->only(['id','url'])
            ]);
        }

/*        $user=$request->user();
        $file = $request->file('file')->store("advertisements/{$user->id}",'public');
        return response()->json([
            'status' => true,
            'file' => "/".$file,
        ]);*/
    }

    /**
     * @OA\Get(
     *      path="/favorite_adds",
     *      operationId="getUserFavoriteAdds",
     *      tags={"User"},
     *      summary="user favorite advertisements",
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
    public function getUserFavorites()
    {
        $favorite=auth()->user()->favorites()
            ->where('favoriteable_type',Advertisement::class)
            ->whereIn('favoriteable_id', Advertisement::query()->publicCatalog()->select('id'))
            ->paginate();
        return FavoriteResource::collection($favorite);
    }

    /**
     * @OA\Get(
     *      path="/favorite_adds/v1",
     *      operationId="getAllUserFavoriteAddsOnlyIds",
     *      tags={"User"},
     *      summary="user all favorite advertisements only id",
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
    public function getUserFavoritesOnlyId()
    {
        $favorite=auth()->user()->favorites()
            ->where('favoriteable_type',Advertisement::class)
            ->whereIn('favoriteable_id', Advertisement::query()->publicCatalog()->select('id'))
            ->pluck('favoriteable_id')->toArray();
        return $favorite;
    }

    /**
     * @OA\Post (
     *      path="/add_to_favorites",
     *      operationId="addAdvertisementToUserFavoriteList",
     *      tags={"User"},
     *      summary="add advertisements to user favorite",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="adds",
     *                  type="array",
     *                  @OA\Items(type="integer")
     *              )
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
     *      security={{ "bearer_token": {} }}
     *     )
     */
    public function addAdvertisementToFavorites(Request $request)
    {
        $this->validate($request,[
            'adds'=>'array',
            'adds.*'=>'numeric'
        ]);

        $user_favorite_adds=auth()->user()->favorites()->where('favoriteable_type',Advertisement::class)->pluck('favoriteable_id')->toArray();
        $requestedIds = array_values(array_unique(array_map('intval', $request->input('adds', []))));
        $publicIds = Advertisement::query()->publicCatalog()->whereIn('id', $requestedIds)->pluck('id')->map(function ($id) {
            return (int)$id;
        })->toArray();
        $ids=array_values(array_filter($publicIds,function ($val)use($user_favorite_adds){
            return !in_array($val,$user_favorite_adds);
        }));

        $data=[];
        foreach ($ids as $add){
            $data[]=new Favorite([
                'user_id' => auth()->id(),
                'favoriteable_id' =>$add,
                'favoriteable_type'=> Advertisement::class
            ]);
        }

        if(auth()->user()->favorites()->saveMany($data)){
            return response()->json([
                'status'=>true,
                'message'=>'لیست علاقه مندهی های شما به روز رسانی شد'
            ]);
        }
    }

    /**
     * @OA\Delete(
     *      path="/empty_favorites",
     *      operationId="emptyUserFavoriteAdds",
     *      tags={"User"},
     *      summary="empty user favorite advertisements",
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
    public function emptyAdvertisementsFavorite(Request $request)
    {
        $favorites=auth()->user()->favorites()->where('favoriteable_type',Advertisement::class);
        if ($favorites && $favorites->delete()){
            return response()->json([
                'status'=>true,
                'message'=>'لیست علاقه مندی های شما خالی شد'
            ]);
        }
    }

    /**
     * @OA\Delete(
     *      path="/delete_ad_from_favorites/{advertisement}",
     *      operationId="deleteAddFromFavorites",
     *      tags={"User"},
     *      summary="delete advertisement from favorites",
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
     *      ),
     *      security={{ "bearer_token": {} }}
     *     )
     */
    public function deleteAdvertisementFromFavorite(Request $request,Advertisement $advertisement)
    {
        $favorites=auth()->user()->favorites()->where('favoriteable_type',Advertisement::class)->where('favoriteable_id',$advertisement->id);
        if ($favorites && $favorites->delete()){
            return response()->json([
                'status'=>true,
                'message'=>'لیست علاقه مندی های شما به روز شد'
            ]);
        }
    }

    /**
     * @OA\Get(
     *      path="/my-ads",
     *      operationId="getUserAdvertisements",
     *      tags={"User"},
     *      summary="user registered advertisements",
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
    public function myAdvertisements()
    {
        $query=auth()->user()->advertisements()->where('city_id', SpadLocation::cityId())->orderBy('created_at','desc');
        return OwnerAdvertisementsList::collection($query->paginate());
    }


    /**
     * Return the owner's advertisement in the exact structure expected by the
     * customer edit form. This endpoint also powers preview for unapproved ads.
     */
    public function editMyAdvertisement(Request $request, Advertisement $advertisement)
    {
        $this->assertAdvertisementOwner($request, $advertisement);

        $category = $advertisement->category;
        $layoutData = app(AdvertisementController::class)
            ->getCategoryLayoutV1($request, $category);

        $data = $advertisement->properties()
            ->with('property')
            ->get()
            ->mapWithKeys(function ($property) {
                return [$property->property_slug => $property->panel_value];
            })
            ->all();

        $shiraz = SpadLocation::city();
        $position = [
            'city' => [
                'id' => (int) $shiraz->id,
                'name' => $shiraz->name,
                'lat' => $shiraz->lat,
                'lng' => $shiraz->lng,
                'regions_count' => $shiraz->regions()->count(),
            ],
            'address' => $advertisement->address,
            'location' => [
                'lat' => $shiraz->lat,
                'lng' => $shiraz->lng,
            ],
        ];

        if ($advertisement->region_id) {
            $region = $advertisement->region()
                ->select(['id', 'city_id', 'name', 'lat', 'lng'])
                ->first();

            if ($region && (int) $region->city_id === (int) $shiraz->id) {
                $position['region'] = $region;
            }
        }

        $data['position'] = $position;
        $data['important_places'] = $advertisement->important_places ?: [
            ['distance' => '', 'place' => ''],
        ];

        $tool = $advertisement->toolConfig()->first();
        $evacuationDate = optional($tool)->rental_evacuation_date;
        if ($evacuationDate instanceof \DateTimeInterface) {
            $evacuationDate = $evacuationDate->format('Y-m-d');
        } elseif ($evacuationDate) {
            $evacuationDate = substr((string) $evacuationDate, 0, 10);
        }

        // Customers can only edit customer-facing tool fields. Admin-only badges,
        // virtual tours, 3D floor plans and financial settings are never exposed here.
        $data['property_tools'] = [
            'document_type' => optional($tool)->document_type ?: '',
            'rental_occupancy_status' => optional($tool)->rental_occupancy_status ?: 'vacant',
            'rental_evacuation_date' => $evacuationDate ?: '',
        ];

        return response()->json([
            'layout' => $layoutData['layout'] ?? [],
            'properties' => $layoutData['properties'] ?? [],
            'important_places' => $layoutData['important_places'] ?? [],
            'category' => $category->only(['id', 'name', 'slug']),
            'data' => $data,
        ]);
    }

    /**
     * Update an advertisement from the customer panel.
     * The city is always forced to Shiraz and the selected street must belong to Shiraz.
     */
    public function updateMyAdvertisement(Request $request, Advertisement $advertisement)
    {
        $this->assertAdvertisementOwner($request, $advertisement);

        $shiraz = SpadLocation::city();

        $this->validate($request, [
            'position.region.id' => [
                'required',
                Rule::exists('regions', 'id')->where('city_id', $shiraz->id),
            ],
            'position.address' => ['required'],
            'important_places' => ['nullable', 'array'],
            'property_tools.document_type' => [
                'nullable',
                Rule::in(['single_leaf', 'endowment', 'pre_sale']),
            ],
            'property_tools.rental_occupancy_status' => [
                'nullable',
                Rule::in(['vacant', 'tenant']),
            ],
            'property_tools.rental_evacuation_date' => [
                'nullable',
                'date',
                'required_if:property_tools.rental_occupancy_status,tenant',
            ],
        ]);

        $category = $advertisement->category;
        $checkAdvertisement = isValidAdvertisement(
            $category,
            $request->except('category', 'position', 'property_tools'),
            true,
            true
        );

        if (count($checkAdvertisement['errors']) > 0) {
            return response()->json([
                'status' => false,
                'message' => 'برخی اطلاعات آگهی معتبر نیستند',
                'errors' => $checkAdvertisement['errors'],
            ], 422);
        }

        // Customer edits must be reviewed again by management.
        $advertisement->update([
            'city_id' => $shiraz->id,
            'region_id' => $request->input('position.region.id'),
            'address' => $request->input('position.address'),
            'status' => Advertisement::WAITING,
            'important_places' => $request->input('important_places', []),
        ]);

        $data = $checkAdvertisement['data'];
        $validSlugs = collect($data)->pluck('property_slug')->filter()->values()->all();

        // Remove stale values when dependencies/layout choices change.
        $staleProperties = AdvertisementProperty::query()
            ->where('advertisement_id', $advertisement->id);
        if (count($validSlugs) > 0) {
            $staleProperties->whereNotIn('property_slug', $validSlugs);
        }
        $staleProperties->delete();

        foreach ($data as $item) {
            $adProp = AdvertisementProperty::updateOrCreate(
                [
                    'advertisement_id' => $advertisement->id,
                    'property_slug' => $item['property_slug'],
                ],
                $item
            );
            $adProp->images = $advertisement;
            $adProp->save();
        }

        return response()->json([
            'status' => true,
            'message' => 'تغییرات آگهی ذخیره شد و برای بررسی مجدد ارسال شد',
            'data' => $data,
        ]);
    }

    private function assertAdvertisementOwner(Request $request, Advertisement $advertisement): void
    {
        $ownsAdvertisement = $request->user()
            ->advertisements()
            ->whereKey($advertisement->id)
            ->exists();

        if (!$ownsAdvertisement) {
            abort(403, 'You are not allowed to edit this advertisement.');
        }
    }

    /**
     * @OA\Get(
     *      path="/my-expert-requests",
     *      operationId="getUserExpertRequests",
     *      tags={"User"},
     *      summary="user expert requests",
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
    public function myExpertsRequests(Request $request)
    {
        $experts=$request->user()->expert_requests()->with('province:id,name','city:id,name','region:id,name','category:id,slug,name,parent','category.parent_category:id,slug,name,parent','category.parent_category.parent_category:id,slug,name,parent');
        return UserExpertRequestResource::collection($experts->paginate());
    }

    /**
     * @OA\Post (
     *      path="/change-password",
     *      operationId="changeUserPassword",
     *      tags={"Authorization"},
     *      summary="change user password",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="password",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="password_confirmation",
     *                  type="string"
     *              )
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
     *      security={{ "bearer_token": {} }}
     *     )
     */
    public function changePassword(Request $request)
    {
        $data=$this->validate($request,[
            'password' => 'required|min:6|max:25|confirmed',
        ]);

        if ($request->user()->update([
            'password'=>$data['password']
        ])){
            return response()->json([
                'status'=>true,
                'message'=>'رمز عبور شما با موفقیت تغییر یافت'
            ]);
        }
    }
}
