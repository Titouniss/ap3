<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\TaskPeriod;
use App\Models\Hour;
use App\Models\TasksBundle;
use App\Models\Project;
use App\Models\TaskComment;
use App\Models\PreviousTask;
use App\Models\Document;
use App\Models\ModelHasDocuments;
use App\Models\Skill;
use App\Models\TasksSkill;
use App\Models\Workarea;
use App\User;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Storage;
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
        $items = Task::where('user_id', $user->id)->with('project', 'comments', 'skills', 'documents', 'periods')->get();
        return response()->json(['success' => $items], $this->successStatus);
    }

    /**
     * Display a listing of the resource by workarea.
     *
     * @param  \App\Models\Workarea $workarea
     * @return \Illuminate\Http\Response
     */
    public function getByWorkarea(Workarea $workarea)
    {
        $items = Task::where('workarea_id', $workarea->id)->with('workarea', 'skills', 'comments', 'previousTasks', 'project', 'documents', 'periods')->get();
        return response()->json(['success' => $items], $this->successStatus);
    }
    /**
     * Display a listing of the resource by bundle.
     *
     * @param  \App\Models\TasksBundle $task_bundle
     * @return \Illuminate\Http\Response
     */
    public function getByBundle(TasksBundle $task_bundle)
    {
        $items = Task::where('tasks_bundle_id', $task_bundle->id)->with('workarea', 'skills', 'comments', 'previousTasks', 'project', 'documents', 'periods')->get();
        return response()->json(['success' => $items], $this->successStatus);
    }

    /**
     * Display a listing of the resource by skill.
     *
     * @param  \App\Models\Skill $skill
     * @return \Illuminate\Http\Response
     */
    public function getBySkill(Skill $skill)
    {
        $items = TasksSkill::select('task_id')->where('skill_id', $skill->id)->get();
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
     * Display a listing of the resource by user.
     *
     * @param  \App\User $user
     * @return \Illuminate\Http\Response
     */
    public function getByUser(User $user)
    {
        if (!$user) {
            return response()->json(['error' => "Utilisateur inconnu"], $this->successStatus);
        }

        $items = Task::where('user_id', $user->id)->with('project:name,status,color', 'skills:name', 'user', 'workarea', 'comments', 'documents', 'periods')->get();

        return response()->json(['success' => $items], $this->successStatus);
    }

    /**
     * Show the specified resource.
     *
     * @param  \App\Models\Task $task
     * @return \Illuminate\Http\Response
     */
    public function show(Task $task)
    {
        return response()->json(['success' => $task->load('project:name,status', 'skills:name', 'user', 'workarea', 'comments', 'documents', 'periods')], $this->successStatus);
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
            'estimated_time' => 'required',
        ]);
        if ($validator->fails()) {

            return response()->json(['error' => $validator->errors()], 401);
        }
        $taskBundle = $this->checkIfTaskBundleExist($arrayRequest['project_id']);
        if (!$taskBundle) {

            return response()->json(['error' => 'error'], 401);
        }
        $user = Auth::user();
        $arrayRequest['created_by'] = $user->id;
        $arrayRequest['tasks_bundle_id'] = $taskBundle->id;

        $project = Project::find($arrayRequest['project_id']);

        if ($project->status == 'doing') {

            $date = Carbon::parse($arrayRequest['date']);
            $start_at = $date->format('Y-m-d H:i:s');
            $end_at = $date->addHours((int)$arrayRequest['estimated_time'])->format('Y-m-d H:i:s');

            $userAvailable = $this->checkIfUserAvailable($arrayRequest['user_id'], $start_at, $end_at);
            $workareaAvailable = $this->checkIfWorkareaAvailable($arrayRequest['workarea_id'], $start_at, $end_at);

            if (!$userAvailable && !$workareaAvailable) {
                return response()->json(['error' => 'L\'utilisateur et l\'ilôt ne sont pas disponibles durant cette période.'], $this->successStatus);
            } elseif (!$userAvailable) {
                return response()->json(['error' => 'L\'utilisateur n\'est pas disponible durant cette période.'], $this->successStatus);
            } elseif (!$workareaAvailable) {
                return response()->json(['error' => 'L\'ilôt n\'est pas disponible durant cette période.'], $this->successStatus);
            }
        } else if ($project->status == 'done') {
            return response()->json(['error' => 'Vous ne pouvez ajouter une tâche à un projet terminé.'], $this->successStatus);
        }

        $item = Task::create($arrayRequest);
        TaskPeriod::create(['task_id' => $item->id, 'start_time' => $start_at, 'end_time' => $end_at]);
        if (isset($arrayRequest['token'])) {
            $this->storeDocuments($item->id, $arrayRequest['token'], $project->company);
        }
        $this->storeComment($item->id, $arrayRequest['comment']);
        $this->storeSkills($item->id, $arrayRequest['skills']);
        $this->storePreviousTask($item->id, $arrayRequest['previousTasksIds']);

        $item = Task::find($item->id)->load('workarea', 'skills', 'comments', 'previousTasks', 'project', 'documents', 'periods');

        return response()->json(['success' => $item], $this->successStatus);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Task $task
     * @return \Illuminate\Http\Response
     */
    public function addComment(Request $request, Task $task)
    {
        $arrayRequest = $request->all();
        $this->storeComment($task->id, $arrayRequest['comment']);

        return response()->json(['success' => $task->load('comments')], $this->successStatus);
    }

    private function checkIfUserAvailable($user_id, $start_at, $end_at, $task_id = null)
    {
        $available = true;
        $newTaskPeriod = CarbonPeriod::create($start_at, $end_at);

        $userTasks = $task_id ? Task::where('user_id', $user_id)->where('status', '!=', 'done')->where('id', '!=', $task_id)->get()
            : Task::where('user_id', $user_id)->where('status', '!=', 'done')->get();

        if (count($userTasks) > 0) {

            foreach ($userTasks as $task) {

                if ($available) {
                    foreach ($task->periods as $task_period) {

                        $period = CarbonPeriod::create($task_period->start_time, $task_period->end_time);

                        if ($period->contains($start_at) || $period->contains($end_at) || $newTaskPeriod->contains($task_period->start_time) || $newTaskPeriod->contains($task_period->end_time)) {
                            $available = false;
                            break;
                        }
                    }
                }
            }
        }
        return $available;
    }

    private function checkIfWorkareaAvailable($workarea_id, $start_at, $end_at, $task_id = null)
    {
        $available = true;
        $newTaskPeriod = CarbonPeriod::create($start_at, $end_at);

        $workareaTasks = $task_id ? Task::where('workarea_id', $workarea_id)->where('status', '!=', 'done')->where('id', '!=', $task_id)->get()
            : Task::where('workarea_id', $workarea_id)->where('status', '!=', 'done')->get();

        if (count($workareaTasks) > 0) {

            foreach ($workareaTasks as $task) {
                if ($available) {
                    foreach ($task->periods as $task_period) {

                        $period = CarbonPeriod::create($task_period->start_time, $task_period->end_time);

                        if ($period->contains($start_at) || $period->contains($end_at) || $newTaskPeriod->contains($task_period->start_time) || $newTaskPeriod->contains($task_period->end_time)) {
                            $available = false;
                            break;
                        }
                    }
                }
            }
        }
        return $available;
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
     * @param  \App\Models\Task $task
     * @return \Illuminate\Http\Response
     */
    public function updatePartial(Request $request, Task $task)
    {
        $arrayRequest = $request->all();

        $validator = Validator::make($arrayRequest, [
            'time_spent' => 'required',
            'notify' => 'required'
        ]);

        $update = $task->update([
            'time_spent' => $arrayRequest['time_spent'],
        ]);

        if (isset($arrayRequest['comment'])) {
            $this->storeComment((int) $task->id, $arrayRequest['comment'], $arrayRequest['notify']);
        }


        if ($update) {
            return response()->json(['success' => $task->load('project:name,status', 'skills:name', 'user', 'workarea', 'comments', 'documents', 'periods')], $this->successStatus);
        } else {
            return response()->json(['error' => 'error'], $this->errorStatus);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Task $task
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Task $task)
    {
        $arrayRequest = $request->all();

        $validator = Validator::make($arrayRequest, [
            'name' => 'required',
            'estimated_time' => 'required',
        ]);

        // Pour un projet en cours, on regarde si la date et l'ilot sont dispo
        $project = Project::find($arrayRequest['project_id']);

        if ($project->status == 'doing') {

            $date = Carbon::parse($arrayRequest['date']);
            $start_at = $date->format('Y-m-d H:i:s');
            $end_at = $date->addHours((int)$arrayRequest['estimated_time'])->format('Y-m-d H:i:s');

            $userAvailable = $this->checkIfUserAvailable($arrayRequest['user_id'], $start_at, $end_at, $id);
            $workareaAvailable = $this->checkIfWorkareaAvailable($arrayRequest['workarea_id'], $start_at, $end_at, $id);

            if (!$userAvailable && !$workareaAvailable) {
                return response()->json(['error' => 'L\'utilisateur et l\'ilôt ne sont pas disponibles durant cette période.'], $this->successStatus);
            } elseif (!$userAvailable) {
                return response()->json(['error' => 'L\'utilisateur n\'est pas disponible durant cette période.'], $this->successStatus);
            } elseif (!$workareaAvailable) {
                return response()->json(['error' => 'L\'ilôt n\'est pas disponible durant cette période.'], $this->successStatus);
            }
        } else if ($project->status == 'done') {
            return response()->json(['error' => 'Vous ne pouvez ajouter une tâche à un projet terminé.'], $this->successStatus);
        }


        $update = $task->update([
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
            $this->updateSkills($task->id, $arrayRequest['skills']);
            $this->updatePreviousTasks($task->id, $arrayRequest['previousTasksIds']);
        }

        if (isset($arrayRequest['token'])) {
            $this->storeDocuments($task->id, $arrayRequest['token'], $project->company);
        }

        if (isset($arrayRequest['documents'])) {
            $documents = $task->documents()->whereNotIn('id', array_map(function ($doc) {
                return $doc['id'];
            }, $arrayRequest['documents']))->get();

            foreach ($documents as $doc) {
                $doc->deleteFile();
            }
        }

        if ($update) {
            return response()->json(['success' => $task->load('workarea', 'skills', 'comments', 'previousTasks', 'project', 'periods', 'documents')], $this->successStatus);
        } else {
            return response()->json(['error' => 'error'], $this->errorStatus);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Task $task
     * @return \Illuminate\Http\Response
     */
    public function destroy(Task $task)
    {
        return response()->json(['success' => $task->delete()], $this->successStatus);
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

    private function storeDocuments(int $task_id, $token, $company)
    {
        if ($token && $task_id) {
            $documents = Document::where('token', $token)->get();

            foreach ($documents as $doc) {
                ModelHasDocuments::firstOrCreate(['model' => Task::class, 'model_id' => $task_id, 'document_id' => $doc->id]);
                $doc->moveFile($company->name);
                $doc->token = null;
                $doc->save();
            }
        }
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
