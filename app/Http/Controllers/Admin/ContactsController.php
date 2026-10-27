<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminContactsList;
use App\Models\Contact;
use Illuminate\Http\Request;

class ContactsController extends Controller
{
    /**
     * @OA\Get(
     *      path="/admin/contact/list",
     *      operationId="adminContactsList",
     *      tags={"Admin-Pages"},
     *      summary="get contacts list",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       )
     *     )
     */
    public function index()
    {
        $query=Contact::query();
        return AdminContactsList::collection($query->paginate());
    }
}
