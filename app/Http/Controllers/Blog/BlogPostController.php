<?php

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Blog\BlogPostRequest;
use App\Http\Resources\AdminBlogPostSingleResource;
use App\Models\Blog\BlogPost;
use Illuminate\Http\Request;

class BlogPostController extends Controller
{
    public function index(Request $request)
    {
        $res = BlogPost::query();
        $request->whenFilled('search', function ($data) use ($res) {
            $res->where('title','like',"%$data%");
        });
        $items=$res->paginate(request()->whenHas('per_page',function ($data){
            return (int)$data;
        }));
        return $items;
    }

    public function show(BlogPost $blogPost)
    {
//        $blogPost->load('categories');
        return new AdminBlogPostSingleResource($blogPost);
    }
    public function store(BlogPostRequest $request)
    {
        $data=$request->validated();
        if (isset($data['image']) && count($data['image'])>0  && count($data['image'])>0){
            $data['image']=$data['image'][0]['id'];
        }else{
            $data['image']=null;
        }
        if(isset($data['categories'])){
            $category=$data['categories'];
            unset($data['categories']);
        }
        if (isset($data['start_time'])){
            $data['start_time']/=1000;
        }
        if (isset($data['end_time'])){
            $data['end_time']/=1000;
        }
        if ($item=auth()->user()->posts()->create($data)){
            if (isset($category)){
                $item->categories()->sync($category);
            }
//            $item->load('categories');
            return response()->json(['status'=>true,'message'=>'نوشته با موفقیت ایجاد شد.','item'=>$item]);
        }
        return response()->json(['status'=>false,'message'=>'اطلاعات وارد شده معتبر نمی باشد.']);
    }

    public function update(BlogPostRequest $request,BlogPost $blogPost)
    {
        $data=$request->validated();
        if (isset($data['image']) && count($data['image'])>0 && count($data['image'])>0){
            $data['image']=$data['image'][0]['id'];
        }else{
            $data['image']=null;
        }
        if(isset($data['categories'])){
            $category=$data['categories'];
            unset($data['categories']);
        }
        if (isset($data['start_time'])){
            $data['start_time']/=1000;
        }
        if (isset($data['end_time'])){
            $data['end_time']/=1000;
        }
        if ($blogPost->update($data)){
/*            if (isset($category)){
                $blogPost->categories()->sync(array_column($category,'id'));
            }
            $blogPost->load('categories');*/
            return response()->json(['status'=>true,'message'=>'نوشته با موفقیت ویرایش شد.','item'=>$blogPost]);
        }
        return response()->json(['status'=>false,'message'=>'اطلاعات وارد شده معتبر نمی باشد.']);
    }
}
