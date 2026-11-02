<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Services\SpadAdminHealthService;
use Illuminate\Http\Request;
class SpadServerHealthController extends Controller {
 public function index(Request $request) {
  abort_unless($request->user(),401);
  $hours=$request->query('period')==='7d'?168:24;
  return response()->json(['data'=>SpadAdminHealthService::report($hours)])
   ->header('Cache-Control','private, no-store');
 }
}
