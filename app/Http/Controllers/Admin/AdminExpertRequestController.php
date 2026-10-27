<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminExpertRequestList;
use App\Models\ExpertRequest;
use Illuminate\Http\Request;

class AdminExpertRequestController extends Controller
{
    public function list()
    {
        $query=ExpertRequest::with(['province','city','region','user','category'])->orderBy('created_at','desc');
        return AdminExpertRequestList::collection($query->cursorPaginate(request('per_page',10)));
    }
}
