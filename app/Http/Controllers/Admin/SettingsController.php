<?php

namespace App\Http\Controllers\Admin;

use App\Models\City;
use App\Models\Setting;
use App\Models\Sidebar;
use App\Support\SpadLocation;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SettingsController extends Controller
{
    public function getTabs()
    {
        return Setting::$settings;
    }

    public function getByGroup($group)
    {
        $settings = Setting::$settings;
        if (isset($settings[$group]) && isset($settings[$group]['subs'])) {
            $res = Setting::where('group', $group)->whereIn('code', array_column($settings[$group]['subs'], 'code'))->get()->pluck('value', 'code');
            return ($res && count($res) > 0) ? $res : false;
        }
        throw new ModelNotFoundException();
    }

    public function saveByGroup($group)
    {
        $settings = Setting::$settings;
        if (isset($settings[$group]) && isset($settings[$group]['subs'])) {
            $valid_codes = array_column($settings[$group]['subs'], 'code');
            if (count($valid_codes) > 0) {
                $fields = request()->all();
                foreach ($fields as $index => $field) {
                    if (in_array($index, $valid_codes)) {
                        $item = Setting::firstOrNew([
                            'group' => $group,
                            'code' => $index,
                        ]);
                        $item->val = $field;
                        $item->save();
                        $item->setFiles($field);
                    }
                }
                return response()->json([
                    'status' => true,
                    'message' => 'تنطیمات با موفقیت به روز رسانی شد',
                ]);
            }
            return response()->json([
                'status' => false,
                'message' => 'موردی جهت به روز سانی یافت نشد',
            ]);
        }
        return response()->json([
            'status' => false,
            'message' => 'اطلاعات ورودی معتبر نمی باشد',
        ]);
    }

    /**
     * @OA\Get  (
     *      path="/admin/settings/sidebar",
     *      operationId="showAllSidebars",
     *      tags={"Admin-Settings"},
     *      summary="show all sidebar",
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
    public function showAllSidebars()
    {
        return Sidebar::all();
    }

    /**
     * @OA\Post (
     *      path="/admin/settings/sidebar/create",
     *      operationId="createSidebar",
     *      tags={"Admin-Settings"},
     *      summary="create sidebar",
     *       @OA\RequestBody(
     *          required=true,
     *          description="Pass user credentials",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  example="سایدبار وبلاگ"
     *              ),
     *              @OA\Property(
     *                  property="content",
     *                  type="array",
     *                  description="type must be one of latestBlogPosts|latestAds|advertising|BlogCategories. for advertising type enter alt|src|link",
     *                  @OA\Items(
     *                      type="object",
     *                      example={
     *                          "title":"جدیدترین نوشته ها",
     *                          "type":"latestBlogPosts"
     *                      }
     *                  )
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
    public function createSidebar(Request $request)
    {
        $data = $this->validate($request, [
            'name' => ['required'],
            'content' => ['array']
        ]);

        $sidebar = Sidebar::create($data);

        if ($sidebar) {
            return response()->json([
                'status' => true,
                'message' => 'سایدبار با موفقیت ایجاد شد'
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'مشکل در ارسال اطلاعات'
        ]);
    }

    public function getDefaultCity()
    {
        $city = SpadLocation::city();

        if (!$city->is_default) {
            City::query()->update(['is_default' => false]);
            $city->update(['is_default' => true]);
        }

        return response()->json([
            'city' => $city,
            'city_id' => $city->id,
        ]);
    }

    public function unsetDefaultCity()
    {
        return response()->json([
            'status' => false,
            'message' => 'شهر SPAD به صورت ثابت شیراز است و قابل حذف نیست.',
        ], 422);
    }

    public function setDefaultCity(City $city)
    {
        $shiraz = SpadLocation::city();

        if ((int) $city->id !== (int) $shiraz->id) {
            return response()->json([
                'status' => false,
                'message' => 'تنها شهر قابل انتخاب در SPAD شیراز است.',
            ], 422);
        }

        City::query()->update(['is_default' => false]);
        $shiraz->update(['is_default' => true]);

        return response()->json([
            'status' => true,
            'message' => 'شهر ثابت سایت شیراز است.',
        ]);
    }

}
