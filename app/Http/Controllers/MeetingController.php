<?php

namespace App\Http\Controllers;

use App\Http\Resources\AdminMeetingResource;
use App\Http\Resources\UserMeetingResource;
use App\Models\Advertisement;
use App\Models\Meeting;
use App\Models\MeetingTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MeetingController extends Controller
{
    /**
     * @OA\Post (
     *      path="/advertisement/{id}/meeting",
     *      operationId="setMeetingForAdvertisement",
     *      tags={"Advertisemnts"},
     *      summary="offer time for advertisement meeting",
     *      security={{ "bearer_token": {} }},
     *      @OA\Parameter(
     *          name="id",
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
     *                  property="description",
     *                  example="I want to visit the interior of the building",
     *                  type="string",
     *              ),
     *              @OA\Property(
     *                  property="times",
     *                  type="array",
     *                  @OA\Items(type="integer",example=1659948600115)

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
    public function addMeeting(Request $request,Advertisement $advertisement)
    {
        $data=$this->validate($request,[
            'description'=>'nullable',
            'times'=>['required','array','min:1'],
            'times.*'=>['required','numeric'],
        ]);

        $times=$this->normalizeMeetingTimes($data['times']);

        if (count($times)===0){
            return response()->json([
                'status'=>false,
                'message'=>'لطفاً حداقل یک زمان معتبر در آینده انتخاب کنید.'
            ],422);
        }

        $meeting=auth()->user()->meetings()->create([
            'description'=>$data['description']??null,
            'advertisement_id'=>$advertisement->id,
        ]);

        if ($meeting){
            $meeting_items=[];
            foreach ($times as $time){
                $meeting_item=new MeetingTime;
                $meeting_item->time=$time;
                $meeting_items[]=$meeting_item;
            }
            $meeting->times()->saveMany($meeting_items);
            return response()->json([
                'status'=>true,
                'message'=>'درخواست بازدید شما ثبت شد. کارشناسان برای هماهنگی با شما تماس می‌گیرند.'
            ]);
        }

        return response()->json([
            'status'=>false,
            'message'=>'ثبت درخواست بازدید انجام نشد.'
        ],500);
    }

    /**
     * @OA\Get  (
     *      path="/my-meetings",
     *      operationId="getUserMeetings",
     *      tags={"User"},
     *      summary="get user meetings for advertisements",
     *      security={{ "bearer_token": {} }},
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *          response=401,
     *          description="Unauthorized operation",
     *          @OA\JsonContent()
     *     ),
     *      @OA\Response(
     *          response=404,
     *          description="Not Found Exception",
     *          @OA\JsonContent()
     *       )
     *     )
     */
    public function getMeetings()
    {
        $meeting=auth()->user()->meetings()
            ->with([
                'advertisement.category',
                'advertisement.city',
                'advertisement.region',
                'times',
                'active_time'
            ])
            ->orderByDesc('created_at');

        return UserMeetingResource::collection($meeting->paginate());
    }

    /**
     * User changes the proposed times of one of their own visiting requests.
     */
    public function updateMyMeeting(Request $request,Meeting $meeting)
    {
        $meeting=$this->ownedMeeting($request,$meeting);

        if ($this->meetingIsClosed($meeting)){
            return response()->json([
                'status'=>false,
                'message'=>'این درخواست دیگر قابل تغییر نیست.'
            ],422);
        }

        $data=$this->validate($request,[
            'times'=>['required','array','min:1'],
            'times.*'=>['required','numeric'],
        ]);

        $times=$this->normalizeMeetingTimes($data['times']);

        if (count($times)===0){
            return response()->json([
                'status'=>false,
                'message'=>'لطفاً حداقل یک زمان معتبر در آینده انتخاب کنید.'
            ],422);
        }

        // Replace the old proposed times with the new list.
        $meeting->times()->delete();

        $meeting_items=[];
        foreach ($times as $time){
            $meeting_item=new MeetingTime;
            $meeting_item->time=$time;
            $meeting_item->status=false;
            $meeting_items[]=$meeting_item;
        }
        $meeting->times()->saveMany($meeting_items);

        $meeting->update([
            'status'=>Meeting::USER_CHANGED,
            'admin_id'=>null,
        ]);

        return response()->json([
            'status'=>true,
            'message'=>'زمان‌های پیشنهادی بازدید با موفقیت تغییر کرد.',
            'meeting'=>new UserMeetingResource($meeting->fresh(['advertisement.category','advertisement.city','advertisement.region','times']))
        ]);
    }

    /**
     * User cancels one of their own visiting requests.
     */
    public function cancelMyMeeting(Request $request,Meeting $meeting)
    {
        $meeting=$this->ownedMeeting($request,$meeting);

        if ($this->meetingIsClosed($meeting)){
            return response()->json([
                'status'=>false,
                'message'=>'این درخواست قبلاً بسته یا منقضی شده است.'
            ],422);
        }

        $meeting->times()->update(['status'=>false]);
        $meeting->update([
            'status'=>Meeting::USER_CANCELED,
            'admin_id'=>null,
        ]);

        return response()->json([
            'status'=>true,
            'message'=>'درخواست بازدید با موفقیت لغو شد.'
        ]);
    }

    private function ownedMeeting(Request $request,Meeting $meeting)
    {
        if ((int)$meeting->user_id !== (int)$request->user()->id){
            abort(403,'شما اجازه دسترسی به این درخواست را ندارید.');
        }

        return $meeting;
    }

    private function meetingIsClosed(Meeting $meeting)
    {
        if (in_array($meeting->status,[Meeting::USER_CANCELED,Meeting::ADMIN_CANCELED],true)){
            return true;
        }

        $reference_time=$meeting->active_time()->orderByDesc('time')->first() ?: $meeting->times()->orderByDesc('time')->first();
        return $reference_time && $reference_time->time->isPast();
    }

    private function normalizeMeetingTimes(array $times)
    {
        $normalized=[];

        foreach ($times as $raw_time){
            $timestamp=(int)$raw_time;

            // Front-end sends milliseconds. Also accept seconds for compatibility.
            if ($timestamp>9999999999){
                $timestamp=(int)floor($timestamp/1000);
            }

            if ($timestamp<=0){
                continue;
            }

            $time=Carbon::createFromTimestamp($timestamp);
            if ($time->isFuture()) {
                $normalized[$time->getTimestamp()]=$time;
            }
        }

        ksort($normalized);
        return array_values($normalized);
    }

    /**
     * @OA\Get (
     *      path="/admin/advertisements/{advertisement}/meetings/user/{user}",
     *      operationId="userMeetingForSpecific",
     *      tags={"Admin-Advertisements"},
     *      summary="user previous meeting for specific advertisement",
     *      @OA\Parameter(
     *          name="advertisement",
     *          description="advertisement id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="user",
     *          description="user id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
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
    public function getUserMeeting(Request $request,Advertisement $advertisement,User $user)
    {
        $meeting=$user->meetings()
            ->whereIn('status',[Meeting::USER_CHANGED,Meeting::ADMIN_CHANGED,Meeting::INIT,Meeting::DONE])
            ->whereHas('times',function ($query){
                $query->where('time','>',now());
            })
            ->with(['times'=>function($query){
                $query->where('time','>',now());
            }])
            ->firstWhere('advertisement_id',$advertisement->id);
        return $meeting;
    }

    /**
     * @OA\Post (
     *      path="/admin/advertisements/{advertisement}/meetings/user/{user}",
     *      operationId="adminSetMeetingForAdvertisement",
     *      tags={"Admin-Advertisements"},
     *      summary="offer time for advertisement meeting",
     *      @OA\Parameter(
     *          name="advertisement",
     *          description="advertisement id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="user",
     *          description="user id",
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
     *                  property="description",
     *                  example="meeting description",
     *                  type="string",
     *              ),
     *              @OA\Property(
     *                  property="time",
     *                  type="object",
     *                  example={
     *                      "id":1,
     *                      "timestamp":5454455454
     *                   }
     *              ),
     *              @OA\Property(
     *                  property="meeting_id",
     *                  example="3",
     *                  type="integer",
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
    public function setMeeting(Request $request,Advertisement $advertisement,User $user)
    {
        $data=$this->validate($request,[
            'meeting_id'=>['nullable'],
            'time'=>['required','array'],
            'time.timestamp'=>['required'],
            'description'=>['nullable'],
        ]);

        $prev_meeting=$user->meetings()
            ->whereIn('status',[Meeting::USER_CHANGED,Meeting::ADMIN_CHANGED,Meeting::INIT,Meeting::DONE])
            ->whereHas('times',function ($query){
                $query->where('time','>',now());
            })
            ->with(['times:id,meeting_id,status,time'])
            ->firstWhere('advertisement_id',$advertisement->id);


        if ($prev_meeting){
            $meeting=$prev_meeting;
            if ($request->filled('meeting_id')){
                if ($request->input('meeting_id')!=$prev_meeting->id){
                    $meeting=$user->meetings()
                        ->where('advertisement_id',$advertisement->id)
                        ->findOrFail($data['meeting_id']);
                    $old_meeting_item=null;
                    $prev_meeting->update([
                        'status'=>Meeting::ADMIN_CHANGED
                    ]);
                }
            }
            $active_time=$prev_meeting->active_time()->first();
            if ($active_time){
                $active_time->update([
                    'status'=>false,
                ]);
            }

            if ($request->filled('time.id')){
                $meeting_time=$meeting->times()->findOrFail($data['time']['id']);
                $meeting_time->update([
                    'status'=>true,
                    'time'=>$data['time']['timestamp']
                ]);
            }else{
                $meeting->times()->create([
                    'status'=>true,
                    'time'=>$data['time']['timestamp']
                ]);
            }
            $meeting->update([
                'status'=>Meeting::DONE,
                'admin_id'=>$request->user()->id
            ]);
        }else{
            $meeting=$user->meetings()->create([
                'description'=>$data['description']??null,
                'advertisement_id'=>$advertisement->id,
                'status'=>Meeting::DONE,
                'admin_id'=>$request->user()->id
            ]);
            if ($meeting){
                $meeting->times()->create([
                    'time'=>$data['time']['timestamp'],
                    'status'=>true,
                ]);
            }
        }
    }

    /**
     * @OA\Put (
     *      path="/admin/meeting/{meeting}/cancel",
     *      operationId="cancelMeetingByAdmin",
     *      tags={"Admin-Advertisements"},
     *      summary="cancel meeting by admin",
     *      @OA\Parameter(
     *          name="meeting",
     *          description="meeting id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
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
    public function cancelMeeting(Request $request,Meeting $meeting)
    {
        $active_time=$meeting->active_time()->first();
        if ($active_time){
            $active_time->update([
                'status'=>false,
            ]);
        }
        if ($meeting->update([
            'status'=>Meeting::ADMIN_CANCELED,
            'admin_id'=>$request->user()->id
        ])){
            return response()->json([
                'status'=>true,
                'meeting'=>$meeting,
                'message'=>'بازدید با موفقیت لغو شد'
            ]);
        }
    }

    /**
     * @OA\Get  (
     *      path="/admin/my-meetings/{type}",
     *      operationId="getExpertMeetings",
     *      tags={"Admin-Advertisements"},
     *      summary="get expert meetings by type",
     *      @OA\Parameter(
     *          name="type",
     *          description="type can be expired|done|waiting|canceled",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="category",
     *          description="category id to filter",
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="id",
     *          description="advertisement id to filter",
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *          response=401,
     *          description="Unauthorized operation",
     *          @OA\JsonContent()
     *     ),
     *      @OA\Response(
     *          response=404,
     *          description="Not Found Exception",
     *          @OA\JsonContent()
     *       )
     *     )
     */
    public function myMeetings(Request $request,$type=null)
    {
        $meetings=Meeting::query()
            ->with('advertisement','user:id,first_name,last_name,mobile','advertisement.category','advertisement.city','advertisement.region','times')
            ->whereHas('advertisement',function ($query){
                $user = request()->user();
                $user_role = $user->roles->first()->name;
                switch ($user_role) {
                    case 'manager':
                        $query->whereHas('category', function ($query) use ($user) {
                            $query->whereIn('id', $user->allowed_categories->pluck('id')->toArray());
                        })->whereHas('city', function ($query) use ($user) {
                            $query->whereIn('id', $user->cities->pluck('id')->toArray());
                        });
                        break;
                    case 'supervisor':
                        $allowed_admins = $user->experts()->select('id')->pluck('id')->toArray();
                        $allowed_admins[] = $user->id;
                        $query->whereHas('admins', function ($query) use ($allowed_admins) {
                            $query->whereIn('admin_id', $allowed_admins);
                        });
                        break;
                    case 'expert':
                        $query->whereHas('admins', function ($query) use ($user) {
                            $query->where('admin_id', $user->id);
                        });
                        break;
                    case 'Super Admin':
                        break;
                }
            })
            ->withMin('active_time as min_time','time')
            ->where(function ($query)use($request){
                $query->whereNull("admin_id")->orWhere('admin_id',$request->user()->id);
            })
            ->orderBy('min_time');
        if (!is_null($type)){
            switch ($type){
                case 'expired':
                    $meetings->whereHas('active_time',function ($query){
                        $query->where('time','<',Carbon::now());
                    });
                    break;
                case 'done':
                    $meetings->where('status',Meeting::DONE);
                    break;
                case 'waiting':
                    $meetings->whereIn('status',[Meeting::INIT,Meeting::USER_CHANGED,Meeting::USER_CANCELED]);
                    break;
                case 'canceled':
                    $meetings->whereIn('status',[Meeting::USER_CANCELED,Meeting::ADMIN_CANCELED]);
                    break;
            }
        }
        if ($request->filled('category') || $request->filled('id')){
            $meetings->whereHas('advertisement',function ($query)use($request){
                $query->where('id',(int)$request->input('id'));
                if ($request->filled('category')){
                    $query->whereHas('category',function ($query)use ($request){
                        $query->where('id',(int)$request->input('category'))->orWhereJsonContains('structure',(int)$request->input('category'));
                    });
                }
            });
        }
        return AdminMeetingResource::collection($meetings->paginate());
    }
}
