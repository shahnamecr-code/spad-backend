<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Directory extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $hidden =[
        'created_at' , 'updated_at' ,'deleted_at'
    ];


    public function subs()
    {
        return $this->hasMany(self::class, 'parent', 'id');
    }

    public function children()
    {
        return $this->subs()->with('children');
    }

    public function getFullPathAttribute()
    {
        return $this->attributes['path'] . $this->attributes['name'] . "/";
    }

    public function move($dist)
    {
        $old = $this->full_path;
        $new = '/';
        $parent = null;
        if ($dist) {
            $new = $dist->full_path;
            $parent = $dist->id;
        }
        $new_path = $new . $this->name;
        if (!Storage::disk('public')->exists($new_path) && Storage::disk('public')->move($old, $new_path) && $this->update([
                'path' => $new,
                'parent' => $parent
            ])) {
            updateDirectorySubs($this,$old,$new_path);
            return true;
        }

    }

    public function files()
    {
        return $this->hasMany(File::class);
    }

    public function remove()
    {
        if ($this->delete() && Storage::disk('public')->exists($this->full_path)){
            try {
                Storage::disk('public')->deleteDirectory($this->full_path);
                return true;
            }catch (\Exception $e){

            }
        }
    }
}
