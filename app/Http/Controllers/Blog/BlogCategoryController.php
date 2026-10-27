<?php

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Blog\BlogCategoryRequest;
use App\Models\Blog\BlogCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlogCategoryController extends Controller
{
    public function allBlogCategories(Request $request)
    {
        $res = BlogCategory::with('parent');
        $request->whenFilled('search', function ($data) use ($res) {
            $res->where('name','like',"%$data%");
        });
        $data=$res->paginate(request()->whenHas('per_page',function ($data){
            return (int)$data;
        }));
        return $data;
    }

    public function index(Request $request)
    {
        $res = BlogCategory::where('status',1);
        $request->whenFilled('search', function ($data) use ($res) {
            $res->where('name','like',"%$data%");
        });
        $data=$res->paginate(request()->whenHas('per_page',function ($data){
            return (int)$data;
        }));
        return $data;
    }

    public function show(Request $request,BlogCategory $blogCategory)
    {
        $blogCategory->load('parent');
        return $blogCategory;
    }

    public function store(BlogCategoryRequest $request)
    {
        $data=$request->validated();
        $item=BlogCategory::create($data);
        if ($item){
            $item->load('parent');
            return response()->json(['status'=>true,'message'=>'دسته بندی جدید با موفقیت ثبت شد.','item'=>$item]);
        }
        return response()->json(['status'=>false,'message'=>'اطلاعات وارد شده معتبر نمی باشد.']);
    }

    public function update(BlogCategoryRequest $request,BlogCategory $blogCategory)
    {
        if($blogCategory->update($request->validated())){
            $blogCategory->load('parent');
            return new JsonResponse(['status'=>true,'message'=>'دسته بندی با موفقیت به روز رسانی شد','item'=>$blogCategory]);
        }
    }
}
