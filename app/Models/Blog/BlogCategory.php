<?php

namespace App\Models\Blog;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlogCategory extends Model
{
    use HasFactory;
    protected $guarded=[];
    protected $casts=[
        'status'=>'boolean'
    ];

    public function subs()
    {
        return $this->hasMany(self::class, 'parent', 'id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent');
    }

    public function children()
    {
        return $this->subs()->with('children');
    }

    public function parent_rec()
    {
        return $this->parent()->with('parent_rec');
    }

    public function posts()
    {
        return $this->belongsToMany(BlogPost::class, 'blog_post_categories','blog_category_id','blog_post_id','id','id');
    }
}
