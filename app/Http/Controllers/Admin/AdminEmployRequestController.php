<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminEmployRequestList;
use App\Models\EmployRequest;
use Illuminate\Http\Request;

class AdminEmployRequestController extends Controller
{
    public function list()
    {
        $query=EmployRequest::query();
        return AdminEmployRequestList::collection($query->paginate(request('per_page',10)));
    }
}
