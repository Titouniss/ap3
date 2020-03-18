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
            $listObject = Role::all();
        } else if ($user->company_id != null) {
            $listObject = Role::where('company_id',$user->company_id)
                            ->where(function ($query) use ($a,$b) {
                                $query->where('company_id', '=', 'null')
                                    ->orWhere('isPublic', true);
                            })->get();
        }
        return response()->json(['success' => $listObject], $this-> successStatus); 
    } 
    
    /** 
     * get single item api 
     * 
     * @return \Illuminate\Http\Response 
     */ 
    public function read($id) 
    { 
        $item = Role::where('id',$id)->first();
        return response()->json(['success' => $item], $this-> successStatus); 
    } 

    /** 
     * get create item api 
     * 
     * @return \Illuminate\Http\Response 
     */ 
    public function create($request) 
    { 
        $arrayRequest = $request->all();
        $validator = Validator::make($arrayRequest, [ 
            'name' => 'required'
        ]);
        if ($validator->fails()) { 
            return response()->json(['error'=>$validator->errors()], 401);            
        }
        $item = Role::create($arrayRequest);
        return response()->json(['success' => $item], $this-> successStatus); 
    } 

    /** 
     * get edit item api 
     * 
     * @return \Illuminate\Http\Response 
     */ 
    public function edit($request, $id) 
    { 
        $arrayRequest = $request->all();
        $validator = Validator::make($arrayRequest, [ 
            'name' => 'required'
        ]);
        $item = Role::where('id',$id)->update(['name' => $arrayRequest['name']]);
        return response()->json(['success' => $item], $this-> successStatus); 
    } 

    /** 
     * get delete item api 
     * 
     * @return \Illuminate\Http\Response 
     */ 
    public function delete($id) 
    { 
        $item = Role::where('id',$id)->delete();
        return response()->json(['success' => $item], $this-> successStatus); 
    } 
}