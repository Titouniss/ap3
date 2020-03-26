<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Workarea;
use App\Models\WorkareasSkill;
use Illuminate\Support\Facades\Auth;
use Validator;


class WorkareaController extends Controller
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
            $items = Workarea::all()->load('company')->load('skills');
        } else if ($user->company_id != null) {
            $items = Workarea::where('company_id',$user->company_id)->get()->load('company')->load('skills');
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
        $item = Workarea::where('id',$id)->first()->load('skills')->load('company');
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
            'company_id' => 'required'
        ]);
        if ($validator->fails()) { 
            return response()->json(['error'=>$validator->errors()], 401);            
        }
        $item = Workarea::create($arrayRequest)->load('company');
        if(!empty($arrayRequest['skills'])){
            foreach($arrayRequest['skills'] as $skill_id){
                WorkareasSkill::create(['workarea_id' => $item->id, 'skill_id' => $skill_id]);
            }
        }
        
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
            'company_id' => 'required'
        ]);
        $item = Workarea::find($id);
        if($item != null){
            $item->name = $arrayRequest['name'];
            $item->company_id = $arrayRequest['company_id'];

            WorkareasSkill::where('workarea_id', $item->id)->delete();
            if(!empty($arrayRequest['skills'])){
                foreach($arrayRequest['skills'] as $skill_id){
                    WorkareasSkill::create(['workarea_id' => $item->id, 'skill_id' => $skill_id]);
                }
            }
            $item->save();
        }
        
        return response()->json(['success' => $item], $this-> successStatus); 
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $item = Workarea::findOrFail($id);
        $item->delete();
        return '';
    }
}
