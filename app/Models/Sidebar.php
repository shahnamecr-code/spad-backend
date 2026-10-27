<?php

namespace App\Models;

use App\Http\Resources\UserAdvertisementsList;
use App\Http\Resources\UserBlogPostsList;
use App\Models\Blog\BlogCategory;
use App\Models\Blog\BlogPost;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sidebar extends Model
{
    use HasFactory;
    protected $guarded=[];

    protected $casts=[
        'content'=>'array'
    ];

    public function widgets()
    {
        $widgets=$this->content;
        $items=[];
        if ($widgets){
            foreach ($widgets as $widget){
                if (isset($widget['type'])){
                    $item=[
                        'type'=>$widget['type'],
                        'title'=>$widget['title']??null,
                    ];
                    $count=$widget['count']??6;
                    switch ($widget['type']){
                        case "latestBlogPosts":
                            $ads=BlogPost::ready()->take($count)->get();
                            $item['content']=UserBlogPostsList::collection($ads)->resource;
                            break;
                        case "latestAds":
                            $ads=Advertisement::query()->with(['category', 'city', 'region'])->where('status',Advertisement::APPROVED)->new()->take($count)->get();
                            $item['content']=UserAdvertisementsList::collection($ads)->resource;
                            break;
                        case 'advertising':
                            $item['content']=[
                                'alt'=>$widget['alt']??null,
                                'src'=>$widget['src']?get_upload_url($widget['src']):null,
                                'link'=>$widget['link']??null,
                            ];
                            break;
                        case 'BlogCategories':
                            $item['content']=BlogCategory::query()
                                ->where('status',1)
                                ->withCount("posts as posts_count")
                                ->orderBy("posts_count","desc")
//                                ->select(['id','name','slug','status'])
                                ->take(6)
                                ->get()->map(function ($cat){
                                    return collect($cat)->only('name','slug')->toArray();
                                });
                            break;
                    }
                    $items[]=$item;
                }
            }
        }
        return $items;
    }
}
