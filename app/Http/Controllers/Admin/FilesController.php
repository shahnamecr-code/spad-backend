<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Flysystem\Plugin\ListWith;

class FilesController extends Controller
{
    //
    public function get_directory_files($dir)
    {
        $files=[];
        $directories=[];
        $storage = Storage::disk('public')->addPlugin(new ListWith());
        $storageItems = $storage->listWith(['mimetype'], $dir);
        foreach ($storageItems as $item) {
            if ($item['type'] == 'dir') {
                $directories[] = [
                    'name'          => $item['basename'],
                    'type'          => 'folder',
                    'path'          => Storage::disk('public')->url($item['path']),
                    'relative_path' => $item['path'],
                    'items'         => '',
                    'last_modified' => '',
                ];
            } else {
                if (empty(pathinfo($item['path'], PATHINFO_FILENAME))) {
                    continue;
                }

                $files[] = [
                    'name'          => $item['basename'],
                    'filename'      => $item['filename'],
                    'type'          => $item['mimetype'] ?? 'file',
                    'path'          => Storage::disk('public')->url($item['path']),
                    'relative_path' => $item['path'],
                    'size'          => $item['size'],
                    'last_modified' => $item['timestamp'],
                    'thumbnails'    => [],
                ];
            }
        }

        return array_merge($directories,$files);
    }

    public function get()
    {
        if(request()->has('dir')){
            $shop_id=request()->input('shop_id');
            $prefix_dir=$shop_id?"shops/shop-$shop_id/":'/';
            $dir=$prefix_dir.request()->input('dir');
            return response()->json(['status'=>true,'files'=>$this->get_directory_files($dir)]);
        }
        return response()->json(['status'=>false]);
    }

    public function create()
    {
        if(request()->has('directory') && request()->has('folder')){
            $shop_id=request()->input('shop_id');
            $prefix_dir=$shop_id?"shops/shop-$shop_id/":'/';
            $dir=$prefix_dir.request()->input('directory');
            $folder=request()->input('folder');
            $folder_name=$dir.'/'.$folder;

            if (Storage::disk('public')->exists($folder_name)) {
                return response(['status'=>false,'message'=>'پوشه ای با این نام قبلاً ایجاد شده است']);
            } elseif (Storage::disk('public')->makeDirectory($folder_name)) {
                return response()->json(['status'=>true,'message'=>'پوشه جدید ایجاد شد.','files'=>$this->get_directory_files($dir)]);
            }

            return response()->json(['status'=>true,'message'=>'پوشه جدید ایجاد شد.']);
        }
        return response(['status'=>false,'message'=>'درخواست شما معتبر نمی باشد.']);
    }

    public function upload(Request $request)
    {
        $extension = $request->file->getClientOriginalExtension();
        $name = Str::replaceLast('.'.$extension, '', $request->file->getClientOriginalName());
        $shop_id=request()->input('shop_id');
        $prefix_dir=$shop_id?"shops/shop-$shop_id/":'/';

        $file = $request->file->storeAs($prefix_dir.$request->upload_path, $name.'.'.$extension, 'public');

        return response()->json([
            'status'=>true,
            'url'=>get_upload_url($file),
            'files'=>$this->get_directory_files($prefix_dir.$request->upload_path)
        ]);
    }

}
