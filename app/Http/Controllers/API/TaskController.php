<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\TaskPeriod;
use App\Models\TasksBundle;
use App\Models\Project;
use App\Models\TaskComment;
use App\Models\PreviousTask;
use App\Models\Skill;
use App\Models\TasksSkill;
use App\Models\Workarea;
use App\Traits\StoresDocuments;
use App\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TaskController extends BaseApiController
{
    use StoresDocuments;

    protected static $index_load = ['workarea', 'skills', 'comments', 'previousTasks', 'project', 'documents', 'periods'];
    protected static $index_append = null;
    protected static $show_load = ['workarea', 'skills', 'comments', 'previousTasks', 'project', 'documents', 'periods'];
    protected static $show_append = null;

    protected static $store_validation_array = [
        'name' => 'required',
        'description' => 'nullable',
        'order' => 'nullable',
        'status' => 'required|in:todo,doing,done',
        'date' => 'nullable',
        'date_end' => 'nullable',
        'estimated_time' => 'required',
        'time_spent' => 'nullable',
        'project_id' => 'required',
        'token' => 'nullable',
        'comment' => 'nullable',
        'skills' => 'required|array',
        'previous_task_ids' => 'nullable|array',
        'user_id' => 'nullable',
        'workarea_id' => 'nullable',
    ];

    protected static $update_validation_array = [
        'name' => 'required',
        'description' => 'nullable',
        'order' => 'nullable',
        'status' => 'required|in:todo,doing,done',
        'date' => 'nullable',
        'date_end' => 'nullable',
        'estimated_time' => 'required',
        'time_spent' => 'nullable',
        'token' => 'nullable',
        'skills' => 'required|array',
        'previous_task_ids' => 'nullable|array',
        'user_id' => 'nullable',
        'workarea_id' => 'nullable',
    ];

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        parent::__construct(Task::class);
    }

    protected function filterIndexQuery($query, Request $request)
    {
        if ($request->has('workarea_id')) {
            if (Workarea::where('id', $request->workarea_id)->doesntExist()) {
                throw new ApiException("Paramètre 'workarea_id' n'est pas valide.");
            }

            $query->where('workarea_id', $request->workarea_id);
        }

        if ($request->has('tasks_bundle_id')) {
            if (TasksBundle::where('id', $request->tasks_bundle_id)->doesntExist()) {
                throw new ApiException("Paramètre 'tasks_bundle_id' n'est pas valide.");
            }

            $query->where('tasks_bundle_id', $request->tasks_bundle_id);
        }

        if ($request->has('skill_id')) {
            if (Skill::where('id', $request->skill_id)->doesntExist()) {
                throw new ApiException("Paramètre 'skill_id' n'est pas valide.");
            }

            $query->where('user_id', $request->skill_id);
        }

        if ($request->has('skill_ids')) {
            if (Skill::whereIn('id', $request->skill_ids)->doesntExist()) {
                throw new ApiException("Paramètre 'skill_ids' n'est pas valide.");
            }

            $query->join('tasks_skills', function ($join) use ($request) {
                $join->on('tasks_skills.task_id', '=', 'task.id')
                    ->whereIn('tasks_skills.skill_id', $request->skill_ids);
            });
        }

        if ($request->has('user_id')) {
            if (User::where('id', $request->user_id)->doesntExist()) {
                throw new ApiException("Paramètre 'user_id' n'est pas valide.");
            }

            $query->where('user_id', $request->user_id);
        }
    }

    protected function storeItem(array $arrayRequest)
    {
        $project = Project::find($arrayRequest['project_id']);
        if ($error = $this->itemErrors($project, 'read')) {
            return $error;
        }

        switch ($project->status) {
            case 'done':
                throw new ApiException('Vous ne pouvez ajouter une tâche à un projet terminé.');
                break;

            case 'doing':
                $date = Carbon::parse($arrayRequest['date']);
                $start_at = $date->format('Y-m-d H:i:s');
                $end_at = $date->addHours((int)$arrayRequest['estimated_time'])->format('Y-m-d H:i:s');

                $userAvailable = $this->checkIfUserAvailable($arrayRequest['user_id'], $start_at, $end_at);
                $workareaAvailable = $this->checkIfWorkareaAvailable($arrayRequest['workarea_id'], $start_at, $end_at);

                if (!$userAvailable && !$workareaAvailable) {
                    throw new ApiException('L\'utilisateur et l\'ilôt ne sont pas disponibles durant cette période.');
                } elseif (!$userAvailable) {
                    throw new ApiException('L\'utilisateur n\'est pas disponible durant cette période.');
                } elseif (!$workareaAvailable) {
                    throw new ApiException('L\'ilôt n\'est pas disponible durant cette période.');
                }
                break;
            default:
                break;
        }

        $item = Task::create([
            'name' => $arrayRequest['name'],
            'description' => $arrayRequest['order'] ?? null,
            'order' => $arrayRequest['order'] ?? null,
            'status' => $arrayRequest['status'],
            'date' => $arrayRequest['date'] ?? null,
            'date_end' => $arrayRequest['date_end'] ?? null,
            'estimated_time' => $arrayRequest['estimated_time'],
            'time_spent' => $arrayRequest['time_spent'] ?? null,
            'project_id' => $arrayRequest['project_id'],
            'user_id' => $arrayRequest['user_id'] ?? null,
            'workarea_id' => $arrayRequest['workarea_id'] ?? null,
            'tasks_bundle_id' => $this->checkIfTaskBundleExist($project->id)->id,
            'created_by' => Auth::id(),
        ]);

        if ($project->status == 'doing') {
            TaskPeriod::create(['task_id' => $item->id, 'start_time' => $start_at, 'end_time' => $end_at]);
        }
        if (isset($arrayRequest['token'])) {
            $this->storeDocumentsByToken($item, $arrayRequest['token'], $project->company);
        }

        $this->storeComment($item->id, $arrayRequest['comment'] ?? null);
        $this->storeSkills($item->id, $arrayRequest['skills'] ?? null);
        $this->storePreviousTask($item->id, $arrayRequest['previous_task_ids'] ?? null);

        return $item;
    }

    protected function updateItem($item, array $arrayRequest)
    {
        $project = Project::find($arrayRequest['project_id']);
        if ($error = $this->itemErrors($project, 'read')) {
            return $error;
        }

        switch ($project->status) {
            case 'done':
                throw new ApiException('Vous ne pouvez ajouter une tâche à un projet terminé.');
                break;

            case 'doing':
                $date = Carbon::parse($arrayRequest['date']);
                $start_at = $date->format('Y-m-d H:i:s');
                $end_at = $date->addHours((int)$arrayRequest['estimated_time'])->format('Y-m-d H:i:s');

                $userAvailable = $this->checkIfUserAvailable($arrayRequest['user_id'], $start_at, $end_at);
                $workareaAvailable = $this->checkIfWorkareaAvailable($arrayRequest['workarea_id'], $start_at, $end_at);

                if (!$userAvailable && !$workareaAvailable) {
                    throw new ApiException('L\'utilisateur et l\'ilôt ne sont pas disponibles durant cette période.');
                } elseif (!$userAvailable) {
                    throw new ApiException('L\'utilisateur n\'est pas disponible durant cette période.');
                } elseif (!$workareaAvailable) {
                    throw new ApiException('L\'ilôt n\'est pas disponible durant cette période.');
                }
                break;
            default:
                break;
        }

        $item->update([
            'name' => $arrayRequest['name'],
            'description' => $arrayRequest['order'] ?? null,
            'order' => $arrayRequest['order'] ?? null,
            'status' => $arrayRequest['status'],
            'date' => $arrayRequest['date'] ?? null,
            'date_end' => $arrayRequest['date_end'] ?? null,
            'estimated_time' => $arrayRequest['estimated_time'],
            'time_spent' => $arrayRequest['time_spent'] ?? null,
            'user_id' => $arrayRequest['user_id'] ?? null,
            'workarea_id' => $arrayRequest['workarea_id'] ?? null,
        ]);

        if ($project->status == 'doing') {
            TaskPeriod::create(['task_id' => $item->id, 'start_time' => $start_at, 'end_time' => $end_at]);
        }
        if (isset($arrayRequest['token'])) {
            $this->storeDocumentsByToken($item, $arrayRequest['token'], $project->company);
        }

        if (isset($arrayRequest['documents'])) {
            $this->deleteUnusedDocuments($item, $arrayRequest['documents']);
        }

        $this->updateSkills($item->id, $arrayRequest['skills'] ?? null);
        $this->updatePreviousTasks($item->id, $arrayRequest['previous_task_ids'] ?? null);

        return $item;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Task $task
     * @return \Illuminate\Http\Response
     */
    public function addComment(Request $request, int $id)
    {
        $item = Task::find($id);
        if ($error = $this->itemErrors($item, 'edit')) {
            return $error;
        }

        $arrayRequest = $request->all();
        $validator = Validator::make($arrayRequest, ['comment' => 'required']);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors());
        }

        $this->storeComment($item->id, $request->comment);

        if (static::$show_load) {
            $item->load(static::$show_load);
        }

        if (static::$show_append) {
            $item->append(static::$show_append);
        }

        return $this->successResponse($item, 'Commentaire ajouté avec succès');
    }

    /**
     * Update part of the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function updatePartial(Request $request, int $id)
    {
        $item = Task::find($id);
        if ($error = $this->itemErrors($item, 'edit')) {
            return $error;
        }

        $arrayRequest = $request->all();
        $validator = Validator::make($arrayRequest, [
            'time_spent' => 'required',
            'notify' => 'required',
            'comment' => 'nullable'
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors());
        }

        $item->update([
            'time_spent' => $arrayRequest['time_spent'],
        ]);
        if (isset($arrayRequest['comment'])) {
            $this->storeComment((int) $item->id, $arrayRequest['comment'], $arrayRequest['notify']);
        }

        if (static::$show_load) {
            $item->load(static::$show_load);
        }

        if (static::$show_append) {
            $item->append(static::$show_append);
        }

        return $this->successResponse($item, 'Mise à jour terminée avec succès');
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
        if ($comment != null && $comment != '' && $task_id) {
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
