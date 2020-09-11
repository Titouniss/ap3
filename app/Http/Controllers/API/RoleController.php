<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\User;
use Spatie\Permission\Models\Role;

use Illuminate\Support\Facades\Auth;
use Validator;

class RoleController extends Controller
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
        if ($user->hasRole('superAdmin')) {
            $listObject = Role::all()->load('permissions');
        } else if ($user->company_id != null) {
            $listObject = Role::where('company_id', $user->company_id)
                ->orWhere(function ($query) {
                    $query->where('company_id', '=', null)
                        ->where('isPublic', true);
                })->get()->load('permissions');
        }
        return response()->json(['success' => $listObject], $this->successStatus);
    }

    /** 
     * get single item api 
     * 
     * @return \Illuminate\Http\Response 
     */
    public function show($id)
    {
        $item = Role::where('id', $id)->first()->load('permissions');
        return response()->json(['success' => $item], $this->successStatus);
    }

    /** 
     * create item api 
     * 
     * @return \Illuminate\Http\Response 
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $arrayRequest = $request->all();
        $validator = Validator::make($arrayRequest, [
            'name' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }
        $permissions = $arrayRequest['permissions'];
        unset($arrayRequest['permissions']);
        if ($user != null) {
            $arrayRequest['company_id'] = $user->company_id;
            $item = Role::create($arrayRequest);
            if ($item != null) {
                if (isset($permissions)) {
                    $item->syncPermissions($permissions);
                }
            }
            return response()->json(['success' => $item], $this->successStatus);
        }
        return response()->json(['success' => 'notAuthentified'], 500);
    }

    /** 
     * update item api 
     * 
     * @return \Illuminate\Http\Response 
     */
    public function update(Request $request, $id)
    {
        $arrayRequest = $request->all();

        $validator = Validator::make($arrayRequest, [
            'name' => 'required'
        ]);
        $role = Role::where('id', $id)->first();
        if ($role != null) {
            $role->name = $arrayRequest['name'];
            $role->description = $arrayRequest['description'];
            $role->isPublic = $arrayRequest['isPublic'];
            if (isset($arrayRequest['permissions'])) {
                $role->syncPermissions($arrayRequest['permissions']);
            }
            $role->save();
        }
        return response()->json(['success' => $role], $this->successStatus);
    }

    /** 
     * delete item api 
     * 
     * @return \Illuminate\Http\Response 
     */
    public function destroy($id)
    {
        $item = Role::where('id', $id)->delete();
        return response()->json(['success' => $item], $this->successStatus);
    }
}
