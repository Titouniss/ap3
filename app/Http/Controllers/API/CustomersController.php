<?php

namespace App\Http\Controllers\API;
use Illuminate\Http\Request; 
use App\Http\Controllers\Controller; 

use App\User; 
use App\Models\Customers;

use Illuminate\Support\Facades\Auth; 
use Validator;

class CustomersController extends Controller 
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
        $customers = [];
        if ($user->hasRole('superAdmin')) {
            $customers = Customers::all();
        } else {
            $customers = Customers::all();
            // Add link to specific company ?
        }
        return response()->json(['success' => $customers], $this->successStatus); 
    } 
    
    /** 
     * get single item api 
     * 
     * @return \Illuminate\Http\Response 
     */ 
    public function show($id) 
    { 
        $item = Customers::where('id',$id)->first();
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
                return response()->json(['error'=>$validator->errors()], 401);            
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
            'name' => 'required',
            'lastname'=> 'required',
            'siret'=> 'required',
            'professional'=> 'required'
            ]);
            $customer = Customers::where('id',$id)->first();
            if ($customer != null) {
                $customer->name = $arrayRequest['name'];
                $customer->lastname = $arrayRequest['lastname'];
                $customer->siret = $arrayRequest['siret'];
                $customer->professional = $arrayRequest['professional'];
                $customer->save();
            }
        return response()->json(['success' => true, 'item' => $customer], $this->successStatus); 
    } 

    /** 
     * delete item api 
     * 
     * @return \Illuminate\Http\Response 
     */ 
    public function destroy($id) 
    { 
        $item = Role::where('id',$id)->delete();
        return response()->json(['success' => $item], $this-> successStatus); 
    } 
}