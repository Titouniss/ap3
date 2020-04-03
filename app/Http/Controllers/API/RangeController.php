<?php

namespace App\Http\Controllers\API;
use Illuminate\Http\Request; 
use App\Http\Controllers\Controller; 

use App\Models\Range; 
use Spatie\Permission\Models\Role;

use Illuminate\Support\Facades\Auth; 
use Validator;

class RangeController extends Controller 
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
            $listObject = Range::all()->load('company');
        } else if ($user->company_id != null) {
            $listObject = Range::where('company_id',$user->company_id);
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
        $item = Range::where('id',$id)->first()->with('repetitive_task');
        return response()->json(['success' => $item], $this-> successStatus); 
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
            return response()->json(['error'=>$validator->errors()], 401);            
        }

        // TODO ADD repetitive_tasks
        $repetitive_tasks = $arrayRequest['repetitive_tasks'];
        // unset($arrayRequest['permissions']);
        if ($user != null) {
            $arrayRequest['company_id'] = $user->company_id;
            $item = Range::create($arrayRequest);
            if ($item != null) {
                if (isset($repetitive_tasks)) {
                    $item->repetitive_tasks()->sync($repetitive_tasks);
                }
            }
            return response()->json(['success' => $item], $this-> successStatus); 
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
        $item = Range::where('id',$id)->first();
        if ($item != null) {
            $item->name = $arrayRequest['name'];
            $item->description = $arrayRequest['description'];
        // TODO ADD repetitive_tasks
            $repetitive_tasks = $arrayRequest['repetitive_tasks'];
            if (isset($repetitive_tasks)) {
                $item->repetitive_tasks()->sync($repetitive_tasks);
            }
            $role->save();
        }
        return response()->json(['success' => true, 'item' => $item], $this-> successStatus); 
    } 

    /** 
     * delete item api 
     * 
     * @return \Illuminate\Http\Response 
     */ 
    public function destroy($id) 
    { 
        $item = Range::where('id',$id)->delete();
        return response()->json(['success' => $item], $this-> successStatus); 
    } 
}