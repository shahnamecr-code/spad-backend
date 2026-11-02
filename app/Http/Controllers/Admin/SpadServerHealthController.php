<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Services\SpadAdminHealthService;
use Illuminate\Http\Request;
class SpadServerHealthController extends Controller {
 public function index(Request $request) {
  abort_unless($request->user(),401);
  return response()->json(['data'=>SpadAdminHealthService::report()])
   ->header('Cache-Control','private, no-store');
 }
}
