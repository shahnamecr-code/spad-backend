<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Services\SpadAdminHealthService;
use Illuminate\Http\Request;
class SpadServerHealthController extends Controller {
 public function resources(Request $request) {
  $user=$request->user();
  abort_unless($user && $user->roles()->where('name','Super Admin')->exists(),403);
  return response()->json(['data'=>\App\Services\SpadAdminInfrastructureService::snapshot()])
    ->header('Cache-Control','private, no-store');
 }
 public function index(Request $request) {
  abort_unless($request->user(),401);
  $hours=$request->query('period')==='7d'?168:24;
  return response()->json(['data'=>SpadAdminHealthService::report($hours)])
   ->header('Cache-Control','private, no-store');
 }
}
