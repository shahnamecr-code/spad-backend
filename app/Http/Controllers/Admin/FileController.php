<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Directory;
use App\Models\File;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class FileController extends Controller
{
    /**
     * @OA\Post(
     *     path="/admin/file/upload",
     *     tags={"Media"},
     *     summary="Upload a file",
     *     description="Upload a file",
     *     operationId="uploadFile",
     *     @OA\MediaType(mediaType="multipart/form-data"),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid Credentials",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized User",
     *         @OA\JsonContent()
     *     ),
     *     @OA\RequestBody(
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *               type="object",
     *               @OA\Property(
     *                  property="file",
     *                  type="file"
     *               )
     *           ),
     *       )
     *     ),
     * )
     */
    public function upload(Request $request)
    {
        $directory = null;
        if ($request->has('directory') && $directory = Directory::find($request->input('directory'))) {
            if (!$request->user()->can('media.management') && $request->user()->id != $directory->admin_id) {
                abort(403);
            }
        }

        $name = $request->file->getClientOriginalName();
        $type = $request->file->getMimeType();
        $size = $request->file->getSize();

        $credentials = [
            'directory_id' => $directory ? $directory->id : null,
            'name' => $name,
            'type' => $type,
            'size' => $size,
        ];


        $file_path = ($directory ? $directory->full_path : '/') . $name;

        if (Storage::disk('public')->exists($file_path)) {
            return response()->json([
                'status' => false,
                'message' => 'فایلی با همین نام وجود دارد'
            ]);
        }

        $file=auth()->user()->files()->create($credentials);

        if ($file &&
            $request->file->storeAs($directory ? $directory->full_path : '/', $name, 'public')) {
            return response()->json([
                'status' => true,
                'message' => 'آپلود انجام شد',
                'url'=>$file->url
            ]);
        }
    }

    /**
     * @OA\Put (
     *      path="/admin/file/{id}/rename",
     *      operationId="renameFile",
     *      tags={"Media"},
     *      summary="rename File",
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          description="The ID of File",
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
    public function rename(Request $request, File $file)
    {
        $this->validate($request, [
            'name' => 'required'
        ]);

        if (!$request->user()->can('media.management')){
            abort(403);
        }

        if ($file->name == $request->input('name')) {
            return response()->json([
                'status' => false,
                'message' => 'نام فایل تغییری نیافته است'
            ]);
        }

        $file->load('directory');

        $directory = null;
        $directory_path = '/';
        if ($file->directory) {
            $directory = $file->directory;
            $directory_path = $directory->full_path;
        }

        $old_name = $directory_path . $file->name;
        $new_name = $directory_path . $request->input('name');

        if (Storage::disk('public')->exists($new_name)) {
            return response()->json([
                'status' => false,
                'message' => 'فایلی با همین نام وجود دارد'
            ]);
        }

        if (Storage::disk('public')->move($old_name, $new_name) &&
            $file->update([
                'name' => $request->input('name')
            ])) {
            return response()->json([
                'status' => true,
                'message' => 'نام فایل تغییر یافت'
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'مشکل در ثبت اطلاعات'
        ]);

    }

}
