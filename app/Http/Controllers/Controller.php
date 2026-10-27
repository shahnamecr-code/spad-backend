<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="SPAD API",
 *      description="SPAD API documentation",
 *      @OA\Contact(
 *          email="info@spad.estate"
 *      ),
 *      @OA\License(
 *          name="SPAD",
 *          url="https://spad.estate"
 *      )
 * )
 *
 * @OA\Server(
 *      url=L5_SWAGGER_CONST_HOST,
 *      description="SPAD API Server"
 * )

 *
 * @OA\Tag(
 *     name="Real-Estate",
 *     description="api documentation"
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
