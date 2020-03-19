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
        if ($user->hasRole('superAdmin')) {
            $listObject = Permission::all();
        } else {
            $listObject = Permission::where('isPublic', true)->get();
        }
        return response()->json(['success' => $listObject], $this-> successStatus); 
    } 
    
    /** 
     * get single item api 
     * 
     * @return \Illuminate\Http\Response 
     */ 
    public function show($id) 
    { 
        $item = Permission::where('id',$id)->first();
        return response()->json(['success' => $item], $this-> successStatus); 
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
            return response()->json(['error'=>$validator->errors()], 401);            
        }
        $item = Permission::create($arrayRequest);
        return response()->json(['success' => $item], $this-> successStatus); 
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
        
        $item = Permission::where('id',$id)->update(['name' => $arrayRequest['name'], 'isPublic' => $arrayRequest['isPublic'] ]);
        return response()->json(['success' => $item], $this-> successStatus); 
    } 

    /** 
     * delete item api 
     * 
     * @return \Illuminate\Http\Response 
     */ 
    public function destroy($id) 
    { 
        $item = Permission::where('id',$id)->delete();
        return response()->json(['success' => $item], $this-> successStatus); 
    } 
}