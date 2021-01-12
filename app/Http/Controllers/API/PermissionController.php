<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

use Illuminate\Support\Facades\Auth;
use Validator;

class PermissionController extends Controller
{
    public $successStatus = 200;

    /**
     * list of items api
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        $listObject = [];
        if ($user->is_admin) {
            $listObject = Permission::orderBy('name', 'desc')->get(); // order important because vuejs have fixed array
        } else {
            $listObject = Permission::where('is_public', true)->orderBy('name', 'desc')->get();
        }
        return response()->json(['success' => $listObject], $this->successStatus);
    }

    /**
     * get single item api
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Permission $permission)
    {
        return response()->json(['success' => $permission], $this->successStatus);
    }

    /**
     * create item api
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $arrayRequest = $request->all();
        $validator = Validator::make($arrayRequest, [
            'name' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $item = Permission::create($arrayRequest);
        return response()->json(['success' => $item], $this->successStatus);
    }

    /**
     * update item api
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Permission $permission)
    {
        $arrayRequest = $request->all();

        $validator = Validator::make($arrayRequest, [
            'name' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $permission->update(['name' => $arrayRequest['name'], 'is_public' => $arrayRequest['is_public']]);
        return response()->json(['success' => $permission], $this->successStatus);
    }

    /**
     * delete item api
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Permission $permission)
    {
        if (!$permission->delete()) {
            return response()->json(['error' => 'Erreur lors de la suppression'], 500);
        }

        return response()->json(['success' => true], $this->successStatus);
    }
}
