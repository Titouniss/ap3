<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\Controller;
use App\User;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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
        if ($user->is_admin) {
            $listObject = Role::all()->load('permissions');
        } else if ($user->company_id != null) {
            $listObject = Role::where('company_id', $user->company_id)
                ->orWhere(function ($query) {
                    $query->where('company_id', '=', null)
                        ->where('is_public', true);
                })->get()->load('permissions');
        }
        return response()->json(['success' => $listObject], $this->successStatus);
    }

    /**
     * get single item api
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Role $role)
    {
        return response()->json(['success' => $role->load('permissions')], $this->successStatus);
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

        // add permissions index for companies and permissions
        $require_permissions = Permission::select('id')->where('name', 'read permissions')->orWhere('name', 'read companies')->get();
        foreach ($require_permissions as $key => $rp) {
            $id_perm = strval($rp->id);
            if (in_array($id_perm, $permissions) == false) {
                array_push($permissions, $id_perm);
            }
        }
        sort($permissions);

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
    public function update(Request $request, Role $role)
    {
        $arrayRequest = $request->all();

        $validator = Validator::make($arrayRequest, [
            'name' => 'required'
        ]);
        if ($role != null) {
            $role->name = $arrayRequest['name'];
            $role->description = $arrayRequest['description'];
            $role->is_public = $arrayRequest['is_public'];
            if (isset($arrayRequest['permissions'])) {
                $role->syncPermissions($arrayRequest['permissions']);
            }
            $role->save();
        }
        return response()->json(['success' => $role], $this->successStatus);
    }

    /**
     * Restore the specified resource in storage.
     *
     * @param  \App\Models\Role  $user
     * @return \Illuminate\Http\Response
     */
    public function restore(Role $role)
    {
        if (!$role->restore()) {
            return response()->json(['error' => 'Erreur lors de la restauration'], 400);
        }

        return response()->json(['success' => $role], $this->successStatus);
    }

    /**
     * delete item api
     *
     * @param  \App\Models\Role  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(Role $role)
    {
        if (!$role->delete()) {
            return response()->json(['error' => 'Erreur lors de l\'archivage'], 400);
        }

        return response()->json(['success' => $role], $this->successStatus);
    }

    /**
     * forceDelete the specified resource from storage.
     *
     * @param  \App\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function forceDelete(Role $role)
    {
        if (!$role->forceDelete()) {
            return response()->json(['error' => 'Erreur lors de la suppression'], 400);
        }

        return response()->json(['success' => true], $this->successStatus);
    }
}
