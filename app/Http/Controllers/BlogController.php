<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserBlogPostSingle;
use App\Http\Resources\UserBlogPostsList;
use App\Models\Blog\BlogCategory;
use App\Models\Blog\BlogPost;
use App\Models\Category;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    /**
     * @OA\Get(
     *      path="/blog/posts",
     *      operationId="getBlogPosts",
     *      tags={"Blog"},
     *      summary="list of blog posts by paginate",
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
        $posts=BlogPost::query()->ready()->orderBy('created_at','desc');
        return UserBlogPostsList::collection($posts->paginate());
    }

    /**
     * @OA\Get(
     *      path="/blog/category/{slug}/posts",
     *      operationId="getBlogCategoryPosts",
     *      tags={"Blog"},
     *      summary="list of blog posts has specific category by paginate",
     *      @OA\Parameter(
     *          name="slug",
     *          description="category slug",
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
    public function showCategoryPosts(Request $request,BlogCategory $category)
    {
        $posts=$category->posts()->ready();
        return UserBlogPostsList::collection($posts->paginate());
    }

    /**
     * @OA\Get(
     *      path="/blog/post/{slug}",
     *      operationId="getBlogPostPreview",
     *      tags={"Blog"},
     *      summary="blog ingle page detail",
     *      @OA\Parameter(
     *          name="slug",
     *          description="category slug",
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
    public function showSinglePost(Request $request,BlogPost $post)
    {
        return new UserBlogPostSingle($post);
    }
}
