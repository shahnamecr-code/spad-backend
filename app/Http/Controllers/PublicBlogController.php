<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserBlogPostSingle;
use App\Http\Resources\UserBlogPostsList;
use App\Models\Blog\BlogCategory;
use App\Models\Blog\BlogPost;
use Illuminate\Http\Request;

class PublicBlogController extends Controller
{
    private function listRelations(): array
    {
        return [
            'categories:name,slug',
            'admin:id,first_name,last_name',
            'mainImg.directory',
        ];
    }

    public function index()
    {
        $posts = BlogPost::query()
            ->ready()
            ->with($this->listRelations())
            ->orderBy('created_at', 'desc');

        return UserBlogPostsList::collection($posts->paginate());
    }

    public function showCategoryPosts(Request $request, BlogCategory $category)
    {
        $posts = $category->posts()
            ->ready()
            ->with($this->listRelations())
            ->orderBy('created_at', 'desc');

        return UserBlogPostsList::collection($posts->paginate());
    }

    public function showSinglePost(Request $request, BlogPost $post)
    {
        $post->loadMissing([
            'categories:name,slug',
            'admin:id,first_name,last_name',
            'mainImg.directory',
            'sidebar',
        ]);

        return new UserBlogPostSingle($post);
    }
}
