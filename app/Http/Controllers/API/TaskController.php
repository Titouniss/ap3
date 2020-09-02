<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\Hour;
use App\Models\TasksBundle;
use App\Models\Project;
use App\Models\TaskComment;
use App\Models\PreviousTask;
use App\Models\TasksSkill;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Auth;
use Validator;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class TaskController extends Controller
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
        $items = Task::all()->load('project', 'comments');
        return response()->json(['success' => $items], $this->successStatus);
    }

    /**
     * Display a listing of the resource by bundle.
     *
     * @return \Illuminate\Http\Response
     */
    public function getByBundle(int $bundle_id)
    {
        $items = Task::where('tasks_bundle_id', $bundle_id)->with('workarea', 'skills', 'comments', 'previousTasks', 'project')->get();
        return response()->json(['success' => $items], $this->successStatus);
    }

    /**
     * Display a listing of the resource by skill.
     *
     * @return \Illuminate\Http\Response
     */
    public function getBySkill(int $skill_id)
    {
        $items = TasksSkill::select('task_id')->where('skill_id', $skill_id)->get();
        return response()->json(['success' => $items], $this->successStatus);
    }

    /**
     * Display a listing of the resource by skills.
     *
     * @return \Illuminate\Http\Response
     */
    public function getBySkills(Request $request)
    {
        $arrayRequest = $request->all();

        $items = [];
        if (!empty($arrayRequest)) {
            foreach ($arrayRequest as $skill_id) {
                $tasks_id = TasksSkill::select('task_id')->where('skill_id', $skill_id)->get();

                //check if task_id is not already in $items
                foreach ($tasks_id as $t_id) {
                    if (!in_array($t_id, $items)) {
                        array_push($items, $t_id);
                    }
                }
            }
        }

        return response()->json(['success' => $items], $this->successStatus);
    }

    /**
     * Show the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $item = Task::where('id', $id)->first()->load('project:name,status', 'skills:name', 'user', 'workarea', 'comments', 'documents');
        return response()->json(['success' => $item], $this->successStatus);
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

        $controllerLog = new Logger('task');
        $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
        $controllerLog->info('task arrayRequest', [$arrayRequest]);

        $validator = Validator::make($arrayRequest, [
            'name' => 'required',
            'estimated_time' => 'required',
        ]);
        if ($validator->fails()) {

            $controllerLog = new Logger('task');
            $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
            $controllerLog->info('task', ['ici 1']);

            return response()->json(['error' => $validator->errors()], 401);
        }
        $taskBundle = $this->checkIfTaskBundleExist($arrayRequest['project_id']);
        if (!$taskBundle) {

            $controllerLog = new Logger('task');
            $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
            $controllerLog->info('task', ['ici 2']);

            return response()->json(['error' => 'error'], 401);
        }
        $user = Auth::user();
        $arrayRequest['created_by'] = $user->id;
        $arrayRequest['tasks_bundle_id'] = $taskBundle->id;

        $project = Project::find($arrayRequest['project_id']);

        if($project->status == 'doing'){

            $date = Carbon::parse($arrayRequest['date']);
            $start_at = $date->format('Y-m-d H:i:s');
            $end_at = $date->addHours((int)$arrayRequest['estimated_time'])->format('Y-m-d H:i:s');

            $userAvailable = $this->checkIfUserAvailable($arrayRequest['user_id'], $start_at, $end_at);
            $workareaAvailable = $this->checkIfWorkareaAvailable($arrayRequest['workarea_id'], $start_at, $end_at);

            if(!$userAvailable && !$workareaAvailable){
                return response()->json(['error' => 'L\'utilisateur et l\'ilôt ne sont pas disponibles durant cette période.'], $this->successStatus);
            }
            elseif(!$userAvailable){
                return response()->json(['error' => 'L\'utilisateur n\'est pas disponible durant cette période.'], $this->successStatus);
            }
            elseif(!$workareaAvailable){
                return response()->json(['error' => 'L\'ilôt n\'est pas disponible durant cette période.'], $this->successStatus);
            }
        }
        else if($project->status == 'done'){
            return response()->json(['error' => 'Vous ne pouvez ajouter une tâche à un projet terminé.'], $this->successStatus);
        }

        $item = Task::create($arrayRequest);
        $this->storeComment($item->id, $arrayRequest['comment']);
        $this->storeSkills($item->id, $arrayRequest['skills']);
        $this->storePreviousTask($item->id, $arrayRequest['previousTasksIds']);

        return response()->json(['success' => $item], $this->successStatus);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addComment(Request $request, $id)
    {
        $item = Task::find($id);
        $arrayRequest = $request->all();
        $this->storeComment($id, $arrayRequest['comment']);
        $item = Task::find($id)->load('comments');

        return response()->json(['success' => $item], $this->successStatus);
    }

    private function checkIfUserAvailable($user_id, $start_at, $end_at, $task_id = null){

        $newTaskPeriod = CarbonPeriod::create($start_at, $end_at);

        $userTasks = $task_id ? Task::where('user_id', $user_id)->where('status', '!=', 'done')->where('id', '!=', $task_id)->get() 
                                : Task::where('user_id', $user_id)->where('status', '!=', 'done')->get() ;

        if(count($userTasks) > 0){

            foreach($userTasks as $task){
                $date = Carbon::parse($task->date);
                $task->start_at = $date->format('Y-m-d H:i:s');
                $task->end_at = $date->addHours((int)$task->estimated_time)->format('Y-m-d H:i:s');

                $taskPeriod = CarbonPeriod::create($task->start_at, $task->end_at);

                if($taskPeriod->contains($start_at) || $taskPeriod->contains($end_at) || $newTaskPeriod->contains($task->start_at) || $newTaskPeriod->contains($task->end_at)){
                    return false;
                }
                else {return true;}
            }
        }
        else{return true;}
    }

    private function checkIfWorkareaAvailable($workarea_id, $start_at, $end_at, $task_id = null){

        $newTaskPeriod = CarbonPeriod::create($start_at, $end_at);

        $workareaTasks = $task_id ? Task::where('workarea_id', $workarea_id)->where('status', '!=', 'done')->where('id', '!=',$task_id)->get() 
                                  : Task::where('workarea_id', $workarea_id)->where('status', '!=', 'done')->get();

        if(count($workareaTasks) > 0){

            foreach($workareaTasks as $task){
                $date = Carbon::parse($task->date);
                $task->start_at = $date->format('Y-m-d H:i:s');
                $task->end_at = $date->addHours((int)$task->estimated_time)->format('Y-m-d H:i:s');
                
                $taskPeriod = CarbonPeriod::create($task->start_at, $task->end_at);

                if($taskPeriod->contains($start_at) || $taskPeriod->contains($end_at) || $newTaskPeriod->contains($task->start_at) || $newTaskPeriod->contains($task->end_at)){
                    return false;
                }
                else {return true;}
            }
        }
        else{return true;}
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
     * Update part of the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updatePartial(Request $request, $id)
    {
        $arrayRequest = $request->all();

        $validator = Validator::make($arrayRequest, [
            'time_spent' => 'required',
            'notify' => 'required'
        ]);

        $update = Task::where('id', $id)
            ->update([
                'time_spent' => $arrayRequest['time_spent'],
            ]);

        if (isset($arrayRequest['comment'])) {
            $this->storeComment((int) $id, $arrayRequest['comment'], $arrayRequest['notify']);
        }


        if ($update) {
            $item = Task::find($id)->load('project:name,status', 'skills:name', 'user', 'workarea', 'comments', 'documents');
            return response()->json(['success' => $item], $this->successStatus);
        } else {
            return response()->json(['error' => 'error'], $this->errorStatus);
        }
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
            'estimated_time' => 'required',
        ]);

        // Pour un projet en cours, on regarde si la date et l'ilot sont dispo 
        $project = Project::find($arrayRequest['project_id']);

        if($project->status == 'doing'){

            $date = Carbon::parse($arrayRequest['date']);
            $start_at = $date->format('Y-m-d H:i:s');
            $end_at = $date->addHours((int)$arrayRequest['estimated_time'])->format('Y-m-d H:i:s');

            $userAvailable = $this->checkIfUserAvailable($arrayRequest['user_id'], $start_at, $end_at, $id);
            $workareaAvailable = $this->checkIfWorkareaAvailable($arrayRequest['workarea_id'], $start_at, $end_at, $id);

            if(!$userAvailable && !$workareaAvailable){
                return response()->json(['error' => 'L\'utilisateur et l\'ilôt ne sont pas disponibles durant cette période.'], $this->successStatus);
            }
            elseif(!$userAvailable){
                return response()->json(['error' => 'L\'utilisateur n\'est pas disponible durant cette période.'], $this->successStatus);
            }
            elseif(!$workareaAvailable){
                return response()->json(['error' => 'L\'ilôt n\'est pas disponible durant cette période.'], $this->successStatus);
            }
        }
        else if($project->status == 'done'){
            return response()->json(['error' => 'Vous ne pouvez ajouter une tâche à un projet terminé.'], $this->successStatus);
        }


        $update = Task::where('id', $id)
            ->update([
                'name' => $arrayRequest['name'],
                'date' => $arrayRequest['date'],
                'estimated_time' => $arrayRequest['estimated_time'],
                'order' => $arrayRequest['order'],
                'description' => $arrayRequest['description'],
                'time_spent' => $arrayRequest['time_spent'],
                'workarea_id' => $arrayRequest['workarea_id'],
                'status' => $arrayRequest['status'],
                'user_id' => $arrayRequest['user_id']
            ]);

        if (isset($arrayRequest['skills']) && isset($arrayRequest['previousTasksIds'])) {
            $this->updateSkills($id, $arrayRequest['skills']);
            $this->updatePreviousTasks($id, $arrayRequest['previousTasksIds']);
        }


        if ($update) {
            $item = Task::find($id)->load('workarea', 'skills', 'comments', 'previousTasks');
            return response()->json(['success' => $item], $this->successStatus);
        } else {
            $item = Task::find($id)->load('workarea', 'skills', 'comments', 'previousTasks');
            return response()->json(['error' => 'error'], $this->errorStatus);
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
        return response()->json(['success' => true], $this->successStatus);
    }


    private function checkIfTaskBundleExist(int $project_id)
    {

        $exist = TasksBundle::where('project_id', $project_id)->first();
        if (!$exist) {
            $project = Project::find($project_id);
            if ($project) {
                return TasksBundle::create(['company_id' => $project->company_id, 'project_id' => $project_id]);
            }
        }
        return $exist;
    }

    private function storeComment(int $task_id, $comment, $confirmed = true)
    {
        if ($comment != '' && $task_id) {
            $user = Auth::user();
            TaskComment::create(['description' => $comment, 'confirmed' => !!$confirmed, 'task_id' => $task_id, 'created_by' => $user->id]);
        }
    }

    private function storeSkills(int $task_id, $skills)
    {
        if (count($skills) > 0 && $task_id) {
            foreach ($skills as $skill_id) {
                TasksSkill::create(['task_id' => $task_id, 'skill_id' => $skill_id]);
            }
        }
    }

    private function storePreviousTask(int $task_id, $previousTaskIds)
    {
        if (count($previousTaskIds) > 0 && $task_id) {
            foreach ($previousTaskIds as $id) {
                PreviousTask::create(['task_id' => $task_id, 'previous_task_id' => $id]);
            }
        }
    }

    private function updateSkills(int $task_id, $skills)
    {
        TasksSkill::where('task_id', $task_id)->delete();
        if (count($skills) > 0 && $task_id) {
            foreach ($skills as $skill_id) {
                TasksSkill::create(['task_id' => $task_id, 'skill_id' => $skill_id]);
            }
        }
    }

    private function updatePreviousTasks(int $task_id, $previousTasksIds)
    {
        PreviousTask::where('task_id', $task_id)->delete();
        if (count($previousTasksIds) > 0 && $task_id) {
            foreach ($previousTasksIds as $id) {
                PreviousTask::create(['task_id' => $task_id, 'previous_task_id' => $id]);
            }
        }
    }
}
