<?php

namespace Spad\Crm\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spad\Crm\Models\CrmRequest;
use Spad\Crm\Models\CrmRequestProperties;
use function getLayoutAllProperties;
use function response;

class PublicCrmController extends Controller
{

    /**
     * @OA\Get(
     *      path="/crm/layout/{category}",
     *      operationId="getSpecificCategoryCrmLayout",
     *      tags={"Crm"},
     *      summary="get crm layout of specific category",
     *      @OA\Parameter(
     *          name="category",
     *          description="category slug",
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
    public function displayCategoryCrmLayout(Request $request, Category $category)
    {
        return response()->json([
            'searchItems' => userShowCategoryCrmLayout($category),
        ]);
    }

    public function addCrmRequest(Request $request,Category $category)
    {
        $this->validate($request, [
            'req' => 'required',
            'req.position' => ['required'],
            'req.position.city' => ['required']
        ]);

        $crm_request = CrmRequest::create([
            'category_id' => $category->id,
            'city_id' => $request->req['position']['city']['id'],
            'regions' => isset($request->req['position']['region']) ? array_column($request->req['position']['region'], 'id') : null,
            'user_id' => auth()->id(),
        ]);
        if ($crm_request) {
            $layouts=userShowCategoryCrmLayout($category);
            $crm_items=[];
            foreach ($layouts as $loop_layout){
                if (isset($loop_layout['items'])){
                    $crm_items=array_merge($crm_items,$loop_layout['items']);
                }
             }
            $crmFields = array_column($crm_items, null, 'code');
            if ($crmFields) {
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
}
