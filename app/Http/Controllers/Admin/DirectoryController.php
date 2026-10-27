<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Directory;
use App\Models\File;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DirectoryController extends Controller
{
    /**
     * @OA\Post (
     *      path="/admin/directory",
     *      operationId="storeNewDirectory",
     *      tags={"Media"},
     *      summary="create nwe Directory",
     *       @OA\RequestBody(
     *          required=true,
     *          description="Pass data",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *              ),
     *              @OA\Property(
     *                  property="directory",
     *                  type="integer",
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *     ),
     *      @OA\Response(
     *          response=404,
     *          description="Not Found Exception",
     *          @OA\JsonContent()
     *       )
     *     )
     */
    public function store(Request $request)
    {
        $this->validate($request,[
            'name'=>['required'],
            'directory'=>['nullable',Rule::exists('directories','id')],
        ]);


        $path='/';

        if ($request->has('directory') && $directory=Directory::find($request->input('directory'))){
            if (!$request->user()->can('media.management') || $request->user()->id != $directory->admin_id) {
                abort(403);
            }
            $path=$directory->full_path;
        }

        $folder_name = $path . $request->input('name');


        if (Storage::disk('public')->exists($folder_name)) {
            return response(['status'=>false,'message'=>'پوشه ای با این نام قبلاً ایجاد شده است']);
        } elseif (Storage::disk('public')->makeDirectory($folder_name)) {
            $credentials=[
                'name'=>$request->input('name'),
                'path'=>$path,
                'parent'=>$request->directory
            ];
            auth()->user()->directories()->create($credentials);
            return response()->json(['status'=>true,'message'=>'پوشه جدید ایجاد شد.'/*,'files'=>$this->get_directory_files($dir)*/]);
        }
    }

    /**
     * @OA\Put (
     *      path="/admin/directory/{id}/rename",
     *      operationId="renameDirectory",
     *      tags={"Media"},
     *      summary="rename Directory",
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          description="The ID of Directory",
     *          required=true,
     *          example="1",
     *          @OA\Schema(
     *              type="integer",
     *          ),
     *       ),
     *       @OA\RequestBody(
     *          required=true,
     *          description="Pass data",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *     ),
     *      @OA\Response(
     *          response=404,
     *          description="Not Found Exception",
     *          @OA\JsonContent()
     *       )
     *     )
     */
    public function rename(Request $request,Directory $directory)
    {
        $this->validate($request,[
            'name'=>'required'
        ]);

        if (is_null($directory->admin_id) || !$request->user()->can('media.management')){
            abort(403);
        }

        if ($directory->name==$request->input('name')){
            return response()->json([
                'status'=>false,
                'message'=>'نام پوشه تغییری نیافته است'
            ]);
        }

        $old=$directory->full_path;
        $new=$directory->path . $request->input('name') . "/";
        if (Storage::disk('public')->exists($new)) {
            return response(['status'=>false,'message'=>'پوشه ای با این نام قبلاً ایجاد شده است']);
        }else{
            if(Storage::disk('public')->move($old,$new) &&
                $directory->update([
                    'name'=>$request->input('name')
                ])){
                $directory->load('children');
                updateDirectorySubs($directory,$old,$new);

                return response()->json([
                    'status'=>true,
                    'message'=>'نام پوشه تغییر یافت',
                ]);
            }
        }
    }

    /**
     * @OA\Post (
     *      path="/admin/media/move",
     *      operationId="moveDirectory",
     *      tags={"Media"},
     *      summary="move Directory",
     *       @OA\RequestBody(
     *          required=true,
     *          description="Pass data",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="dist",
     *                  type="integer",
     *              ),
     *              @OA\Property(
     *                  property="directories",
     *                  type="array",
     *                  @OA\Items()
     *              ),
     *              @OA\Property(
     *                  property="files",
     *                  type="array",
     *                  @OA\Items()
     *              ),
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *     ),
     *      @OA\Response(
     *          response=404,
     *          description="Not Found Exception",
     *          @OA\JsonContent()
     *       )
     *     )
     */
    public function move(Request $request)
    {
        $this->validate($request,[
            'directories'=>'nullable|array',
            'directories.*'=>'numeric',
            'files'=>'nullable|array',
            'files.*'=>'numeric',
            'dist'=>['nullable',Rule::exists('directories','id')]
        ]);

        if (!$request->user()->can('media.management')){
            abort(403);
        }

        $dist=null;
        if ($request->filled('dist')){
            $dist=Directory::find($request->input('dist'));
        }

        $directory_count=0;
        $files_count=0;

        if ($request->filled('directories')){
            $directories=Directory::query()->whereNotNull('admin_id')->whereIn('id',$request->input('directories'))->get();
            foreach ($directories as $directory){
                if($directory->move($dist)){
                    $directory_count+=1;
                }
            }
        }
        if ($request->filled('files')){
            $files=File::whereIn('id',$request->input('files'))->get();
            foreach ($files as $file){
                if($file->move($dist)){
                    $files_count+=1;
                }
            }
        }

        if ($directory_count > 0 && $files_count>0){
            $msg=$directory_count . " پوشه و " . $files_count . " فایل انتقال یافت";
            $status=true;
        }elseif($directory_count > 0){
            $msg=$directory_count . " پوشه انتقال یافت";
            $status=true;
        }elseif($files_count > 0){
            $msg=$files_count . " فایل انتقال یافت";
            $status=true;
        }else{
            $msg="هیچ انتقالی انجام نشد";
            $status=false;
        }

        return response()->json([
            'status'=>$status,
            'message'=>$msg
        ]);

    }

    /**
     * @OA\Get (
     *      path="/admin/media/get/{id}",
     *      operationId="getDirectoryStructure",
     *      tags={"Media"},
     *      summary="get Directory Structure",
     *       @OA\Parameter(
     *          name="id",
     *          in="path",
     *          description="The ID of Directory",
     *          required=false,
     *          @OA\Schema(
     *              type="integer",
     *          ),
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *     ),
     *      @OA\Response(
     *          response=404,
     *          description="Not Found Exception",
     *          @OA\JsonContent()
     *       )
     *     )
     */
    public function getStructure(Request $request,$directory_id=null)
    {
        $upload_directory=null;

        if (is_null($directory_id)){
            $directory_id=$upload_directory;
        }

//        $directory_id=null;
        $directories=Directory::where('parent',$directory_id);
        $files=File::where('directory_id',$directory_id);

        if (!$request->user()->can('media.management')){
            $directories->where('admin_id',$request->user()->id);
            $files->where('fileable_type',Admin::class)->where('fileable_id',$request->user()->id);
        }

        return [
            'files'=>$files->get(),
            'directories'=>$directories->get()
        ];
    }

    /**
     * @OA\Delete  (
     *      path="/admin/media/remove",
     *      operationId="removeDirectory",
     *      tags={"Media"},
     *      summary="remove Directory And File",
     *       @OA\RequestBody(
     *          required=true,
     *          description="Pass data",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="directories",
     *                  type="array",
     *                  @OA\Items()
     *              ),
     *              @OA\Property(
     *                  property="files",
     *                  type="array",
     *                  @OA\Items()
     *              ),
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *     ),
     *      @OA\Response(
     *          response=404,
     *          description="Not Found Exception",
     *          @OA\JsonContent()
     *       )
     *     )
     */
    public function remove(Request $request)
    {
        $this->validate($request,[
            'directories'=>'nullable|array',
            'directories.*'=>'numeric',
            'files'=>'nullable|array',
            'files.*'=>'numeric',
        ]);

        if (!$request->user()->can('media.management')){
            abort(403);
        }

        $directories=Directory::query()->whereNotNull('admin_id')->whereIn('id',$request->input('directories',[]))->get();
        $files=File::whereIn('id',$request->input('files',[]))->get();

        if(count($directories)>0){
            foreach ($directories as $directory){
                $directory->remove();
            }
        }

        if(count($files)>0){
            foreach ($files as $file){
                $file->remove();
            }
        }

        return response()->json([
            'status'=>true,
            'message'=>'حذف انجام شد'
        ]);

    }

    public function destroy()
    {

    }
}
