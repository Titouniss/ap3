<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Validator;


class ProjectController extends Controller
{
    public $successStatus = 200;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        $items = [];
        if ($user->hasRole('superAdmin')) {
            $items = Project::all()->load('company');
        } else if ($user->company_id != null) {
            $items = Project::where('company_id',$user->company_id)->get()->load('company');
        }
        return response()->json(['success' => $items], $this-> successStatus);  
    }

    /**
     * Show the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $item = Project::where('id',$id)->first();
        return response()->json(['success' => $item], $this-> successStatus); 
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $arrayRequest = $request->all();
        $validator = Validator::make($arrayRequest, [ 
            'name' => 'required',
            'date' => 'required',
            'company_id' => 'required',
            
        ]);
        if ($validator->fails()) { 
            return response()->json(['error'=>$validator->errors()], 401);            
        }
        $item = Project::create($arrayRequest)->load('company');
        return response()->json(['success' => $item], $this-> successStatus); 
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $arrayRequest = $request->all();
        
        $validator = Validator::make($arrayRequest, [ 
            'name' => 'required',
            'date' => 'required',
            'company_id' => 'required'
            ]);
        
        $update = Project::where('id',$id)
            ->update([
                'name' => $arrayRequest['name'], 
                'date' => $arrayRequest['date'],
                'company_id' => $arrayRequest['company_id']
            ]);
        
        if($update){
            $item = Project::find($id)->load('company');
            return response()->json(['success' => $item], $this-> successStatus); 
        }
        else{
            return response()->json(['error' => 'error'], $this-> errorStatus); 
        }
        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $item = Project::findOrFail($id);
        $item->delete();
        return '';
    }
}
