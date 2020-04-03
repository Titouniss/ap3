<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\TasksBundle;
use App\Models\Project;
use App\Models\TaskComment;
use App\Models\TasksSkill;
use Illuminate\Support\Facades\Auth;
use Validator;


class TaskController extends Controller
{
    public $successStatus = 200;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($project_id)
    {
        $user = Auth::user();
        $items = Task::all();
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
        $item = Task::where('id',$id)->first();
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
            'status' => 'required',
            'estimated_time' => 'required'            
        ]);
        if ($validator->fails()) { 
            return response()->json(['error'=>$validator->errors()], 401);            
        }
        $taskBundle = $this->checkIfTaskBundleExist($arrayRequest['project_id']);
        if (!$taskBundle) {
            return response()->json(['error'=> 'error'], 401);            
        }
        $arrayRequest['tasks_bundle_id'] = $taskBundle->id;
        $item = Task::create($arrayRequest);
        $this->storeComment($item->id, $arrayRequest['comment']);
        $this->storeSkills($item->id, $arrayRequest['skills']);

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
        
        $update = Task::where('id',$id)
            ->update([
                'name' => $arrayRequest['name'], 
                'date' => $arrayRequest['date'],
                'company_id' => $arrayRequest['company_id']
            ]);
        
        if($update){
            $item = Task::find($id)->load('company');
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
        $item = Task::findOrFail($id);
        $item->delete();
        return '';
    }


    private function checkIfTaskBundleExist(int $project_id){

        $exist = TasksBundle::where('project_id', $project_id)->first();
        if(!$exist){
            $project = Project::find($project_id);
            if ($project) {
                return TasksBundle::create(['company_id' => $project->company_id, 'project_id' => $project_id]);
            }
        } 
        return $exist;
    }

    private function storeComment(int $task_id, $comment){
        if($comment != '' && $task_id){
            TaskComment::create(['description' => $comment, 'confirmed' => 1, 'task_id' => $task_id]);
        }
    }

    private function storeSkills(int $task_id, $skills){
        if(count($skills) > 0 && $task_id){
            foreach ($skills as $skill_id) {
                TasksSkill::create(['task_id' => $task_id, 'skill_id' => $skill_id]);
            } 
        }
    }
}
