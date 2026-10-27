<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class File extends Model
{
    use HasFactory;
    protected $guarded=[];

    protected $appends = [
        'url'
    ];

    public function fileable()
    {
        return $this->morphTo();
    }

    public function directory()
    {
        return $this->belongsTo(Directory::class);
    }

    public function move($dist)
    {
        $directory_path = $this->directory?$this->directory->full_path:'/';
        $old=$directory_path . $this->name;
        $new=($dist?$dist->full_path:'/') . $this->name;

        if (!Storage::disk('public')->exists($new) && Storage::disk('public')->move($old, $new) && $this->update([
                'directory_id' => $dist?$dist->id:null,
            ])) {
            return true;
        }
        return false;
    }

    public function getUrlAttribute()
    {
        $directory=$this->directory;
        $directory_path='/';
        if ($directory){
            $directory_path=$directory->full_path;
        }
        return url('/storage'.$directory_path . $this->name);
    }

    public function getFilePathAttribute()
    {
        $directory=$this->directory;
        $directory_path='/';
        if ($directory){
            $directory_path=$directory->full_path;
        }
        return $directory_path . $this->name;
    }

    public function remove()
    {
        if ($this->delete() && Storage::disk('public')->exists($this->file_path)){
            try{
                Storage::disk('public')->delete($this->file_path);
                return true;
            }catch (\Exception $e){

            }
        }
        return false;
    }
}
