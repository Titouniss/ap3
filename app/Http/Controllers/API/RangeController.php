<?php

namespace App\Http\Controllers\API;
use Illuminate\Http\Request; 
use App\Http\Controllers\Controller; 

use App\Models\Range; 
use App\Models\RepetitiveTask; 
use App\Models\RepetitiveTasksSkill; 
use Spatie\Permission\Models\Role;

use Illuminate\Support\Facades\Auth; 
use Validator;
use Illuminate\Database\Eloquent\SoftDeletes;

class RangeController extends Controller 
{
    use SoftDeletes;

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
        $item = Range::where('id',$id)->first();
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
        $repetitive_tasks = $arrayRequest['repetitive_tasks'];

        if ($user != null) {
            $item = Range::create($arrayRequest);
            if ($item != null) {
                if (isset($repetitive_tasks)) {
                   foreach ($repetitive_tasks as $repetitive_task) {
                       $this->storeRepetitiveTask($item->id, $repetitive_task);
                   }
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
            $item->save();

            $repetitive_tasks = $arrayRequest['repetitive_tasks'];
            if (isset($repetitive_tasks)) {
                $this->updateRepetitiveTask($item->id, $repetitive_tasks);
            }   
            else{
                RepetitiveTask::where('range_id', $item->id)->delete();
            }
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

    /**
     * forceDelete the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function forceDelete($id)
    {
        $item = Range::findOrFail($id);
        $item->delete();

        $item = Range::withTrashed()->findOrFail($id);
        $item->forceDelete();
        return '';
    }

    public function getRepetitiveTasks($range_id){
        if($range_id){
            $items = RepetitiveTask::where('range_id',$range_id)->with('skills', 'workarea')->get();
            return response()->json(['success' => $items], $this-> successStatus); 
        }
        return response()->json(['error'=> 'error'], 401);        
    }

    private function storeRepetitiveTask($range_id, $repetitive_task){
        $repetitive_task['range_id'] = $range_id;
        $item = RepetitiveTask::create($repetitive_task);
        foreach ($repetitive_task['skills'] as $skill_id) {
            $this->storeRepetitiveTasksSkill($item->id, $skill_id);
        }
        return $item;
    }

    private function storeRepetitiveTasksSkill($repetitive_task_id, $skill_id){
        RepetitiveTasksSkill::create(['repetitive_task_id' => $repetitive_task_id, 'skill_id' => $skill_id]);
    }

    private function updateRepetitiveTask($range_id, $repetitive_tasks){
        $ids = [];
        foreach ($repetitive_tasks as $repetitive_task) {
            $item = RepetitiveTask::where('id', $repetitive_task['id'])->where('range_id', $range_id)->first();
            if($item){
                $item->name = $repetitive_task['name'];
                $item->order = $repetitive_task['order'];
                $item->description = $repetitive_task['description'];
                $item->estimated_time = $repetitive_task['estimated_time'];
                $item->workarea_id = $repetitive_task['workarea_id'];
                $item->save();

                RepetitiveTasksSkill::where('repetitive_task_id', $item->id)->delete();
                foreach ($repetitive_task['skills'] as $skill_id) {
                    $this->storeRepetitiveTasksSkill($item->id, $skill_id);
                }
            }
            else{
               $item = $this->storeRepetitiveTask($range_id, $repetitive_task);
            }
            array_push($ids, $item->id);
        }
        $itemtest = RepetitiveTask::whereNotIn('id', $ids)->where('range_id', $range_id)->delete();
    }
}