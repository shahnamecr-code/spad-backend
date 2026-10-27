<?php

namespace Spad\Crm\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Property;
use Illuminate\Http\Request;

class AdvertisementCategoryController extends Controller
{
    public function showCategoryCrmLayout(Request $request, Category $category)
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
            'searchItems' => $category->crmFields,
        ]);
    }

    public function saveCategoryCrmLayout(Request $request, Category $category)
    {
        return $category->update([
            'crmFields' => $request->input('items')
        ]);
    }

    public function displayCategoryCrmLayout(Request $request, Category $category)
    {
        return response()->json([
            'searchItems' => userShowCategoryCrmLayout($category),
        ]);
    }


}
