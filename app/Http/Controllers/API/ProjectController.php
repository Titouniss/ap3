<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\ModelHasDocuments;
use Illuminate\Http\Request;
use App\Models\Project;
use App\User;
use App\Models\Range;
use App\Models\Task;
use App\Models\TasksSkill;
use App\Models\TaskPeriod;
use App\Models\Workarea;
use App\Models\PreviousTask;
use App\Models\TasksBundle;
use Illuminate\Support\Facades\Auth;
use Validator;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Carbon\CarbonPeriod;
use Exception;

class ProjectController extends Controller
{
    use SoftDeletes;

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
        if ($user->is_admin) {
            $items = Project::withTrashed()->get()->load('company')->load('customer', 'documents');
        } else if ($user->company_id != null) {
            $items = Project::where('company_id', $user->company_id)->get()->load('company')->load('customer', 'documents');
        }
        return response()->json(['success' => $items], $this->successStatus);
    }

    /**
     * Show the specified resource.
     *
     * @param  \App\Models\Project $project
     * @return \Illuminate\Http\Response
     */
    public function show(Project $project)
    {
        return response()->json(['success' => $project->load('documents')], $this->successStatus);
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
        $arrayRequest['company_id'] = $arrayRequest['company']['id'];
        $validator = Validator::make($arrayRequest, [
            'name' => 'required',
            'date' => 'required',
            'company_id' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }
        $item = Project::create($arrayRequest)->load('company');
        ProjectController::checkIfTaskBundleExist($item->id);
        if (isset($arrayRequest['token'])) {
            $this->storeProjectDocuments($item, $arrayRequest['token']);
        }
        return response()->json(['success' => $item->load('documents')], $this->successStatus);
    }

    private function storeProjectDocuments($project, $token)
    {
        if ($token && $project) {
            $documents = Document::where('token', $token)->get();

            foreach ($documents as $doc) {
                ModelHasDocuments::firstOrCreate(['model' => Project::class, 'model_id' => $project->id, 'document_id' => $doc->id]);
                $doc->moveFile($project->company->name);
                $doc->token = null;
                $doc->save();
            }
        }
    }

    public function addRange(Request $request, $id)
    {
        $arrayRequest = $request->all();
        $prefix = $arrayRequest['prefix'];
        $project_id = $arrayRequest['project_id'];
        $item = Range::find($id)->load('repetitive_tasks');
        $user = Auth::user();
        $taskBundle = $this->checkIfTaskBundleExist($project_id);
        $tasksArrayByOrder = [];

        $test = [];

        foreach ($item->repetitive_tasks as $repetitive_task) {
            $task = Task::create([
                'name' => $prefix . ' - ' . $repetitive_task->name,
                'order' => $repetitive_task->order,
                'description' => $repetitive_task->description,
                'estimated_time' => $repetitive_task->estimated_time,
                'tasks_bundle_id' => $taskBundle->id,
                'created_by' => $user->id,
                'workarea_id' => $repetitive_task->workarea_id,
                'status' => 'todo',
            ]);
            isset($tasksArrayByOrder[$task->order]) ?
                array_push($tasksArrayByOrder[$task->order], $task->id) : $tasksArrayByOrder[$task->order] = [$task->id];

            $key = array_search($task->order, array_keys($tasksArrayByOrder));
            array_push($test, ['name_key' => $key]);
            $key > 0 ? $this->attributePreviousTask($tasksArrayByOrder, $key, $task->id) : '';

            $this->storeSkills($task->id, $repetitive_task->skills);
            $this->storeDocuments($task->id, $repetitive_task->documents);
        }

        $items = Task::where('tasks_bundle_id', $taskBundle->id)->with('workarea', 'skills', 'comments', 'previousTasks', 'documents')->get();
        return response()->json(['success' => $items], $this->successStatus);
    }

    private function storeSkills(int $task_id, $skills)
    {
        if (count($skills) > 0 && $task_id) {
            foreach ($skills as $skill) {
                TasksSkill::create(['task_id' => $task_id, 'skill_id' => $skill->id]);
            }
        }
    }

    private function storeDocuments(int $task_id, $documents)
    {
        if ($documents && $task_id) {
            foreach ($documents as $doc) {
                ModelHasDocuments::firstOrCreate(['model' => Task::class, 'model_id' => $task_id, 'document_id' => $doc->id]);
            }
        }
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

    private function attributePreviousTask($tasksArrayByOrder, $key, $taskId)
    {
        $keys = array_keys($tasksArrayByOrder);
        foreach ($tasksArrayByOrder[$keys[$key - 1]] as $previousTaskId) {
            PreviousTask::create(['task_id' => $taskId, 'previous_task_id' => $previousTaskId]);
        }
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
     * @param  \App\Models\Project $project
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Project $project)
    {
        $arrayRequest = $request->all();

        $validator = Validator::make($arrayRequest, [
            'name' => 'required',
            'date' => 'required',
            'company_id' => 'required'
        ]);

        $update = $project->update([
            'name' => $arrayRequest['name'],
            'date' => $arrayRequest['date'],
            'company_id' => $arrayRequest['company_id'],
            'customer_id' => $arrayRequest['customer_id'],
            'color' => $arrayRequest['color']
        ]);

        if (!$update) {
            return response()->json(['error' => 'error'], $this->errorStatus);
        }

        if (isset($arrayRequest['token'])) {
            $this->storeProjectDocuments($project, $arrayRequest['token']);
        }

        if (isset($arrayRequest['documents'])) {
            $documents = $project->documents()->whereNotIn('id', array_map(function ($doc) {
                return $doc['id'];
            }, $arrayRequest['documents']))->get();

            foreach ($documents as $doc) {
                $doc->deleteFile();
            }
        }

        return response()->json(['success' => $project->load('documents', 'company', 'customer')], $this->successStatus);
    }

    /**
     * Restore the specified resource in storage.
     *
     * @param  \App\Models\Project $project
     * @return \Illuminate\Http\Response
     */
    public function restore(Project $project)
    {
        if (!$project->restoreCascade()) {
            return response()->json(['success' => false, 'error' => 'Impossible de restaurer le projet'], 400);
        }

        return response()->json(['success' => $project->load('company')], $this->successStatus);
    }

    /**
     * Archive the specified resource from storage.
     *
     * @param  \App\Models\Project $project
     * @return \Illuminate\Http\Response
     */
    public function destroy(Project $project)
    {
        if (!$project->deleteCascade()) {
            return response()->json(['success' => false, 'error' => 'Impossible d\'archiver le projet'], 400);
        }

        return response()->json(['success' => $project->load('company')], $this->successStatus);
    }

    /**
     * forceDelete the specified resource from storage.
     *
     * @param  \App\Models\Project $project
     * @return \Illuminate\Http\Response
     */
    public function forceDelete(Project $project)
    {
        $project->forceDeleteCascade();

        return response()->json(['success' => true], $this->successStatus);
    }

    public function start(Request $request)
    {
        $arrayRequest = $request->all();
        $project = Project::find($arrayRequest['id']);
        $project->start_date = $arrayRequest['start_date'];
        $users = User::where('company_id', $project->company_id)->with('workHours')->with('unavailabilities', 'skills')->get();
        $workareas = Workarea::where('company_id', $project->company_id)->with('skills')->get();

        // Alertes pour l'utilisateur
        $alerts = $this->checkIfStartIsPossible($project, $users, $workareas);

        if (!$alerts) {

            $nbHoursRequired = 0;
            $nbHoursAvailable = 0;
            $nbHoursUnvailable = 0;

            foreach ($project->tasks as $task) {
                // Hours required
                $nbHoursRequired += $task->estimated_time;
            }

            // Hours Available & Hours Unavailable
            $TimeData = $this->calculTimeAvailable($users, $project, $users);

            if ($TimeData['total_hours_available'] >= $nbHoursRequired) {

                $response = $this->setDateToTasks($project->tasks, $TimeData, $users, $project);
                return response()->json($response);
            } else {

                return $TimeData['total_hours_available'] < $nbHoursRequired ? response()->json(['error_time' => 'time_less'], $this->successStatus) : response()->json(['error' => 'user_time_less'], $this->successStatus);
            }
        } else {
            return response()->json(['error_alerts' => $alerts], $this->successStatus);
        }
    }

    private function checkIfStartIsPossible($project, $users, $workareas)
    {

        //check if workers have hours
        $haveHours = false;
        foreach ($users as $user) {
            foreach ($user->workHours as $workHour) {
                if ($workHour->is_active == 1) {
                    $haveHours = true;
                }
            }
            if ($haveHours) {
                break;
            }
        }

        //check if workers/workareas have tasks skills
        $nb_tasks = count($project->tasks);
        $nb_tasks_skills_worker = 0;
        $nb_tasks_skills_workarea = 0;

        foreach ($project->tasks as $task) {

            $project_skills_ids = [];
            foreach ($task->skills as $skill) {
                $project_skills_ids[] = $skill->id;
            }

            //workers
            foreach ($users as $user) {

                $user_skills_ids = [];
                foreach ($user->skills as $skill) {
                    $user_skills_ids[] = $skill->id;
                }

                if (count(array_intersect($project_skills_ids, $user_skills_ids)) == count($project_skills_ids)) {

                    $nb_tasks_skills_worker += 1;
                    break;
                }
            }

            //workareas
            foreach ($workareas as $workarea) {

                $workarea_skills_ids = [];
                foreach ($workarea->skills as $skill) {
                    $workarea_skills_ids[] = $skill->id;
                }

                if (count(array_intersect($project_skills_ids, $workarea_skills_ids)) == count($project_skills_ids)) {

                    $nb_tasks_skills_workarea += 1;
                    break;
                }
            }
        }
        $workersHaveSkills = $nb_tasks == $nb_tasks_skills_worker ? true : false;
        $workareasHaveSkills = $nb_tasks == $nb_tasks_skills_workarea ? true : false;

        $alerts = [];
        $haveHours ? null : $alerts[] = "Aucun utilisateur ne possède d'heures de travail";
        $workersHaveSkills ? null : $alerts[] = "Au moins une compétence utilisée dans une tâche n'est pas associée à un utilisateur";
        $workareasHaveSkills ? null : $alerts[] = "Au moins une compétence utilisée dans une tâche n'est pas associée à un pôle de produciton";

        return count($alerts) > 0 ? $alerts : null;
    }

    private function setDateToTasks($tasks, $TimeData, $users, $project)
    {
        $available_periods_temp = $TimeData['details'];
        $tasksTemp = $this->orderTasksByPrevious($tasks);
        $start_date_project = null;
        $NoPlanTasks = [];

        try {
            //On parcours chaque tache
            foreach ($tasksTemp as $keytask => $task) {

                if ($task->date == null) {
                    // On récupère les compétences de la tache
                    $taskSkills = $task->skills;
                    $taskPlan = false;

                    $available_periods = $available_periods_temp;
                    //On parcours la liste des périodes disponible et on regarde si la durée de la tache est inférieur ou égale a la periode disponilbe
                    foreach ($available_periods as $key => $available_period) {

                        $period = $available_period;
                        if ($task->estimated_time <= $period['hours'] && !$taskPlan) {

                            // On regarde si l'utilisateur de la période possède les compétences nécéssaires
                            $userHaveSkills = false;
                            if (count($period['user']->skills) > 0) {
                                $haveSkillsNb = 0;
                                foreach ($taskSkills as $skill) {

                                    foreach ($period['user']->skills as $userskill) {
                                        if ($skill->id == $userskill->id) {
                                            $haveSkillsNb++;
                                            break;
                                        }
                                    }
                                }
                                if ($haveSkillsNb == count($taskSkills)) {

                                    //On regarde si la tache est dépendante d'autre(s) tache(s)
                                    $previousOk = true;
                                    if ($task->previousTasks && count($task->previousTasks) > 0) {

                                        //Si oui, on regarde si les taches sont déjà programmées et si la période est supérieur à la tâche qui précède
                                        $last_previous_task_end_time = null;

                                        foreach ($task->previousTasks as $previous) {
                                            $previous_task = Task::find($previous->previous_task_id);
                                            if ($previous_task->date != null || $previous_task->date_end > $period['start_time']) {

                                                $last_previous_task_end_time = ($last_previous_task_end_time == null || $previous_task->date_end > $last_previous_task_end_time) ? $previous_task->date_end : $last_previous_task_end_time;
                                                $previousOk = false;
                                            }
                                        }
                                        if (!$previousOk && $last_previous_task_end_time) {

                                            $total_hours = 0;
                                            foreach ($period['periods'] as $key_period => $period_temp) {

                                                $period_period_temp = CarbonPeriod::create($period_temp['start_time'], $period_temp['end_time']);

                                                if ($period_temp['end_time'] <= $last_previous_task_end_time) { //On supprime la period

                                                    unset($period['periods'][$key_period]);
                                                } elseif ($period_temp['start_time'] >= $last_previous_task_end_time) { //On ne fait rien

                                                    $total_hours += Carbon::parse($period_temp['end_time'])->floatDiffInHours(Carbon::parse($period_temp['start_time']));
                                                } elseif ($period_period_temp->contains($last_previous_task_end_time)) { //On transforme la periode

                                                    $period_temp['start_time'] = Carbon::parse($last_previous_task_end_time);
                                                    $period['periods'][$key_period] = $period_temp;
                                                    $total_hours += Carbon::parse($period_temp['end_time'])->floatDiffInHours(Carbon::parse($period_temp['start_time']));
                                                }
                                            }
                                            $period['start_time'] = Carbon::parse($last_previous_task_end_time);
                                            $period['hours'] = $total_hours;

                                            //On regarde si en supprimant la durée de la tache précedente à la période on a assez d'heures
                                            if ($period['hours'] >= $task->estimated_time) {

                                                $period['periods'] = array_values($period['periods']);
                                                $previousOk = true;
                                            }
                                        }
                                    }

                                    if ($previousOk) {

                                        //On regarde si un ilôt est disponible pendant la période
                                        $workareaOk = null;
                                        $workareas = $this->getWorkareasBySkills($task->skills, $project->company_id);

                                        foreach ($workareas as $workarea) {

                                            if (!$taskPlan) {

                                                $tasksWorkarea = Task::where('workarea_id', $workarea->id)->whereNotNull('date')->where('status', '!=', 'done')->get();
                                                if (count($tasksWorkarea) > 0) {

                                                    //return array de periods || null
                                                    $newPeriods = $this->getNewPeriodsWorkareaTasksLess($tasksWorkarea, $period);
                                                    if ($newPeriods && count($newPeriods) > 0) {

                                                        foreach ($newPeriods as $newPeriod) {

                                                            //On vérifie si la period contient toujours assez d'heure
                                                            if ($task->estimated_time <= $newPeriod['hours']) {

                                                                //On planifie
                                                                $taskPlan = true;
                                                                $planifiedTask = $this->planTask($task, $newPeriod, $workarea->id);
                                                                $tasksTemp[$keytask] = Task::findOrFail($task->id);
                                                                $start_date_project = $start_date_project == null || $start_date_project > $planifiedTask->date ? $planifiedTask->date : $start_date_project;

                                                                //On supprime les periodes utilisées
                                                                $available_periods_temp = $this->delUsedPeriods($available_periods, $key, $planifiedTask);

                                                                break;
                                                            }
                                                        }
                                                    }
                                                } else {
                                                    //On planifie
                                                    $taskPlan = true;
                                                    $planifiedTask = $this->planTask($task, $period, $workarea->id);
                                                    $tasksTemp[$keytask] = Task::findOrFail($task->id);
                                                    $start_date_project = $start_date_project == null || $start_date_project > $planifiedTask->date ? $planifiedTask->date : $start_date_project;

                                                    //On supprime les periodes utilisées
                                                    $available_periods_temp = $this->delUsedPeriods($available_periods, $key, $planifiedTask);

                                                    break;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            //On regarde si toute les tâches ont été plannifé
            $allPlanified = true;
            $taskIds = [];

            foreach ($tasksTemp as $taskTemp) {
                array_push($taskIds, $taskTemp->id);

                if (!$taskTemp->date || !$taskTemp->date_end || !$taskTemp->user_id) {
                    $allPlanified = false;
                }
            }


            //Si toutes les taches ont été planifié, on passe le projet en `doing` et on return success
            if ($allPlanified) {
                Project::findOrFail($project->id)->update(['status' => 'doing', 'start_date' => $start_date_project]);

                return $response = ['success' => 'All good'];
            } else { // Si non, on reboot les taches planifiés et on retourne les alertes a l'utilisateur

                Task::whereIn('id', $taskIds)->update(['date' => null, 'date_end' => null, 'user_id' => null, 'workarea_id' => null]);
                return $response = ['error_time' => 'time_less'];
            }
        } catch (\Throwable $th) {
            //On déprogramme toutes les taches du projet
            $ids = [];
            foreach ($tasks as $task) {
                array_push($ids, $task->id);
            }

            Task::whereIn('id', $ids)->update(['date' => null, 'date_end' => null, 'user_id' => null, 'workarea_id' => null]);
            TaskPeriod::whereIn('task_id', $ids)->delete();

            return $response = ['error_algo' => $th->getMessage()];
        }
    }

    private function planTask($task, $globalPeriod, $workarea_id)
    {

        $counter = $task->estimated_time;
        $task_start = $globalPeriod['periods'][0]['start_time'];
        $period_key = 0;

        while ($counter != 0) {

            $period = $globalPeriod['periods'][$period_key];
            $nb_hours_period = Carbon::parse($period['end_time'])->floatDiffInHours(Carbon::parse($period['start_time']));

            if ($counter >= $nb_hours_period) {

                TaskPeriod::create(['task_id' => $task->id, 'start_time' => $period['start_time'], 'end_time' => $period['end_time']]);
                $counter -= $nb_hours_period;
            } else {
                $hours = floor($counter);
                $mins = round(($counter - $hours) * 60);

                $task_end = Carbon::parse($period['start_time'])->addHours($hours)->addMinutes($mins);
                TaskPeriod::create(['task_id' => $task->id, 'start_time' => $period['start_time'], 'end_time' => $task_end]);
                $counter = 0;
            }

            $period_key++;

            if ($counter == 0) {

                $task_end = isset($task_end) ? $task_end : Carbon::parse($period['end_time']);

                return tap(Task::findOrFail($task->id))->update(
                    [
                        'date' => $task_start,
                        'date_end' => $task_end,
                        'user_id' => $globalPeriod['user']->id,
                        'workarea_id' => $workarea_id
                    ]
                )->fresh();
            }
        }
    }

    private function delUsedPeriods($available_periods, $period_key, $planifiedTask)
    {

        $globalPeriods = $available_periods[$period_key]['periods'];
        $new_total_hours = 0;

        $start_task = Carbon::parse($planifiedTask->date);
        $end_task = Carbon::parse($planifiedTask->date_end);
        $task_period = CarbonPeriod::create($start_task, $end_task);

        $periods = [];
        $globalPeriods_temp = $globalPeriods;
        foreach ($globalPeriods_temp as $key => $period) {
            $period_period = CarbonPeriod::create($period['start_time'], $period['end_time']);

            unset($globalPeriods[$key]);
            if ($period_period->contains($start_task) || $period_period->contains($end_task)) {

                $old_period = $period;

                if ($period['start_time'] < $start_task) {

                    $period1 = $old_period;
                    $period1['end_time'] = $start_task;
                    $period1['hours'] = Carbon::parse($period1['end_time'])->floatDiffInHours(Carbon::parse($period1['start_time']));
                    $period1['additional_period'] = false;

                    array_push($periods, $period1);
                }

                if ($period['end_time'] > $end_task) {

                    $period2 = $old_period;
                    $period2['start_time'] = $end_task;
                    $period2['hours'] = Carbon::parse($period2['end_time'])->floatDiffInHours(Carbon::parse($period2['start_time']));
                    $period2['additional_period'] =  true;

                    array_push($periods, $period2);
                }
            }
            //On verifie si la tache n'englobe pas la periode
            elseif ($task_period->contains($period['start_time']) && $task_period->contains($period['end_time'])) {
                //do nothing
            } else {

                $newPeriod = $period;
                $newPeriod['hours'] = Carbon::parse($newPeriod['end_time'])->floatDiffInHours(Carbon::parse($newPeriod['start_time']));
                $newPeriod['additional_period'] = true;

                array_push($periods, $newPeriod);
            }
        }

        usort($periods, array($this, 'date_sort'));
        $newPeriods = $this->compileHours($periods, $available_periods[$period_key]['user']);

        //On retire l'ancienne periode
        unset($available_periods[$period_key]);

        //On ajoute la/les nouvelles periodes
        $available_periods = array_merge($available_periods, $newPeriods['periods']);
        usort($available_periods, array($this, 'date_sort'));

        return $available_periods;
    }

    private function calculTimeAvailable($users, $project)
    {
        $hoursAvailable = [];
        $usersPeriods = [];

        // Get days today date -> end date
        $start_date = Carbon::createFromFormat('Y-m-d', $project->start_date)->startOfDay();
        $end_date = Carbon::createFromFormat('Y-m-d H:i:s', $project->date)->subDays('1')->endOfDay();

        $period = CarbonPeriod::create($start_date, $end_date);

        foreach ($period as $t) {
            $daysPeriod[] = [
                'date' => $t,
                'day_label' => $t->format('l')
            ];
        }

        $totalAvailableHours = 0;
        // foreach days foreach users get day get hours
        foreach ($users as $user) {

            $userHours = [];
            foreach ($daysPeriod as $key => $day) {

                switch (true) {
                    case ($day['day_label'] == 'Monday' || $day['day_label'] == 'Lundi'):

                        $dayHours = $this->getHoursAvailableByDay('lundi', $day['date'], $user);
                        $dayHours ? $userHours = array_merge($userHours, $dayHours) : null;

                        break;

                    case ($day['day_label'] == 'Tuesday' || $day['day_label'] == 'Mardi'):

                        $dayHours = $this->getHoursAvailableByDay('mardi', $day['date'], $user);
                        $dayHours ? $userHours = array_merge($userHours, $dayHours) : null;

                        break;

                    case ($day['day_label'] == 'Wednesday' || $day['day_label'] == 'Mercredi'):

                        $dayHours = $this->getHoursAvailableByDay('mercredi', $day['date'], $user);
                        $dayHours ? $userHours = array_merge($userHours, $dayHours) : null;

                        break;

                    case ($day['day_label'] == 'Thursday' || $day['day_label'] == 'Jeudi'):

                        $dayHours = $this->getHoursAvailableByDay('jeudi', $day['date'], $user);
                        $dayHours ? $userHours = array_merge($userHours, $dayHours) : null;

                        break;

                    case ($day['day_label'] == 'Friday' || $day['day_label'] == 'Vendredi'):

                        $dayHours = $this->getHoursAvailableByDay('vendredi', $day['date'], $user);
                        $dayHours ? $userHours = array_merge($userHours, $dayHours) : null;

                        break;

                    case ($day['day_label'] == 'Saturday' || $day['day_label'] == 'Samedi'):

                        $dayHours = $this->getHoursAvailableByDay('samedi', $day['date'], $user);
                        $dayHours ? $userHours = array_push($userHours, $dayHours) : null;

                        break;

                    case ($day['day_label'] == 'Sunday' || $day['day_label'] == 'Dimanche'):

                        $dayHours = $this->getHoursAvailableByDay('dimanche', $day['date'], $user);
                        $dayHours ? $userHours = array_merge($userHours, $dayHours) : null;

                        break;
                }
            }

            $userHours = $this->compileHours($userHours, $user);

            usort($userHours['periods'], array($this, 'date_sort'));
            $usersPeriods = array_merge($usersPeriods, $userHours['periods']);

            $totalAvailableHours += $userHours['total_hours'];
        }

        usort($usersPeriods, array($this, 'date_sort'));
        return $response = [
            'details' => $usersPeriods,
            'total_hours_available' => $totalAvailableHours
        ];
    }

    private function getHoursAvailableByDay($day, $date, $user)
    {

        $userPeriods = [];
        $unAvailablePeriods = count($user->unavailabilities) > 0 ? $this->transformDatesToPeriod($user->unavailabilities) : null;

        foreach ($user->workHours as $dayHours) {

            if ($dayHours->day == $day && (string) $dayHours->is_active) {

                $first_time_slot_start = null;
                $first_time_slot_end = null;
                $second_time_slot_start = null;
                $second_time_slot_end = null;

                //Hours available
                if ($dayHours->morning_ends_at && $dayHours->morning_starts_at) {

                    $first_time_slot_end = Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' ' . $dayHours->morning_ends_at);
                    $first_time_slot_start = Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' ' . $dayHours->morning_starts_at);
                }
                if ($dayHours->afternoon_ends_at && $dayHours->afternoon_starts_at) {

                    $second_time_slot_end = Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' ' . $dayHours->afternoon_ends_at);
                    $second_time_slot_start = Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' ' . $dayHours->afternoon_starts_at);
                }

                if (isset($first_time_slot_start) && isset($first_time_slot_end)) {
                    #### Indispo + Other projects task
                    //Hours unavailable
                    if ($unAvailablePeriods) {

                        $workDate = Carbon::createFromFormat('Y-m-d H:i:s', $date);
                        $filteredPeriods = $this->filterUnavailablePeriodsByDay($unAvailablePeriods, $workDate);
                    }

                    // //Hours Tasks other Project
                    $other_project_tasks_day = $this->getOtherTasksProjectsPeriods($user, $date);

                    //On compile les indispo et les taches des autres projets
                    $compiledUnavailablePeriods = $this->compileUnavailablePeriods(isset($filteredPeriods) ? array_merge($filteredPeriods, $other_project_tasks_day) : $other_project_tasks_day);


                    #### Construction du planning
                    $planning = [];
                    if (count($compiledUnavailablePeriods) > 0) {
                        $planning = [];
                        $periods_unavailable = [
                            'first_time_slot' => [],
                            'second_time_slot' => []
                        ];


                        usort($compiledUnavailablePeriods, array($this, 'date_sort'));

                        $planning = $this->buildPlannings($first_time_slot_start, $first_time_slot_end, $second_time_slot_start, $second_time_slot_end, $user, $compiledUnavailablePeriods);
                    } else {

                        $first_time_slot_start && $first_time_slot_end ? $planning[] = array(
                            'start_time' => $first_time_slot_start,
                            'end_time' => $first_time_slot_end,
                            'hours' => Carbon::parse($first_time_slot_end)->floatDiffInHours(Carbon::parse($first_time_slot_start)),
                            'user' => $user,
                            'additional_period' => true,
                        ) : null;

                        isset($second_time_slot_start) && isset($second_time_slot_end) ? $planning[] = array(
                            'start_time' => $second_time_slot_start,
                            'end_time' => $second_time_slot_end,
                            'hours' => Carbon::parse($second_time_slot_end)->floatDiffInHours(Carbon::parse($second_time_slot_start)),
                            'user' => $user,
                            'additional_period' => true,
                        ) : null;
                    }

                    $userPeriods = $planning;
                }
            }
        }

        return !empty($userPeriods) ? $userPeriods : null;
    }

    private function getOtherTasksProjectsPeriods($user, $date)
    {

        $tasks_periods = [];
        $tasks = Task::where('user_id', $user->id)
            ->where('date', 'like', '%' . $date->format('Y-m-d') . '%')
            ->orWhere('date_end', 'like', '%' . $date->format('Y-m-d') . '%')
            ->orWhere(function ($query) use ($date) {
                $query->where('date', '<', $date);
                $query->where('date_end', '>', $date);
            })
            ->get();

        if (count($tasks) > 0) {

            foreach ($tasks as $task) {

                $period = [
                    'start_time' => Carbon::createFromFormat('Y-m-d H:i:s', $task->date),
                    'end_time' => Carbon::createFromFormat('Y-m-d H:i:s', $task->date_end),
                    'period' => CarbonPeriod::create($task->date, $task->date_end)
                ];

                $tasks_periods[] = $period;
            }
        }

        return $tasks_periods;
    }

    private function calculNbHoursUnavailable($dayHours, $period, $hours_unavailable = null)
    {

        $hours_unavailable_first_time_slot = [
            'hours' => 0,
            'periods' => [
                'start_time' => null,
                'end_time' => null
            ]
        ];
        $hours_unavailable_second_time_slot = [
            'hours' => 0,
            'periods' => [
                'start_time' => null,
                'end_time' => null
            ]
        ];

        //On regarde si l'indiponiblité est répartie sur le matin et/ou l'après-midi
        if ($dayHours->morning_ends_at && $dayHours->morning_starts_at) {
            $first_time_slot_end = Carbon::createFromFormat('Y-m-d H:i:s', $period['period']->getStartDate()->format('Y-m-d') . ' ' . $dayHours->morning_ends_at);
            $first_time_slot_start = Carbon::createFromFormat('Y-m-d H:i:s', $period['period']->getStartDate()->format('Y-m-d') . ' ' . $dayHours->morning_starts_at);

            //Matin
            if ($period['start_date'] < $first_time_slot_end) {
                $first_time_slot_start_unavailable = null;
                $first_time_slot_end_unavailable = null;

                //On regarde si la l'heure du début de l'indisponibilité ne commence pas avant l'horaire d'embauche du matin
                if ($period['start_date'] > $first_time_slot_start) {
                    $first_time_slot_start_unavailable = $period['start_date'];
                } else { //Sinon, on prend l'heure d'embauche
                    $first_time_slot_start_unavailable = $first_time_slot_start;
                }

                //On regarde si la l'heure de fin de l'indisponibilité ne commence pas après l'horaire de débauche du midi
                if ($period['end_date'] <= $first_time_slot_end) {
                    $first_time_slot_end_unavailable = $period['end_date'];
                } else { //Sinon, on prend de débauche du midi.
                    $first_time_slot_end_unavailable = $first_time_slot_end;
                }

                $hours_unavailable_first_time_slot = [
                    'hours' => Carbon::parse($first_time_slot_end_unavailable)->floatDiffInHours(Carbon::parse($first_time_slot_start_unavailable)),
                    'periods' => [
                        'start_time' => $first_time_slot_start_unavailable,
                        'end_time' => $first_time_slot_end_unavailable
                    ]
                ];
            }
        }

        if ($dayHours->afternoon_ends_at && $dayHours->afternoon_starts_at) {
            $second_time_slot_end = Carbon::createFromFormat('Y-m-d H:i:s', $period['period']->getStartDate()->format('Y-m-d') . ' ' . $dayHours->afternoon_ends_at);
            $second_time_slot_start = Carbon::createFromFormat('Y-m-d H:i:s', $period['period']->getStartDate()->format('Y-m-d') . ' ' . $dayHours->afternoon_starts_at);

            //Après midi
            if ($period['end_date'] > $second_time_slot_start) {
                $second_time_slot_start_unavailable = null;
                $second_time_slot_end_unavailable = null;

                //On regarde si la l'heure du début de l'indisponibilité ne commence pas avant l'horaire d'embauche d'après-midi
                if ($period['start_date'] > $second_time_slot_start) {
                    $second_time_slot_start_unavailable = $period['start_date'];
                } else { //Sinon, on prend l'heure d'embauche d'après-midi
                    $second_time_slot_start_unavailable = $second_time_slot_start;
                }

                //On regarde si la l'heure de fin de l'indisponibilité ne commence pas après l'horaire de débauche du soir
                if ($period['end_date'] <= $second_time_slot_end) {
                    $second_time_slot_end_unavailable = $period['end_date'];
                } else { //Sinon, on prend de débauche du midi.
                    $second_time_slot_end_unavailable = $second_time_slot_end;
                }

                $hours_unavailable_second_time_slot = [
                    'hours' => Carbon::parse($second_time_slot_end_unavailable)->floatDiffInHours(Carbon::parse($second_time_slot_start_unavailable)),
                    'periods' => [
                        'start_time' => $second_time_slot_start_unavailable,
                        'end_time' => $second_time_slot_end_unavailable
                    ]
                ];;
            }
        }

        return array('first_time_slot' => $hours_unavailable_first_time_slot, 'second_time_slot' => $hours_unavailable_second_time_slot);
    }

    private function calculNbHoursUnavailablePeriod($dayHours, $day, $fisrt_date = null, $last_date = null)
    {

        $hours_unavailable_first_time_slot = [
            'hours' => 0,
            'periods' => [
                'start_time' => null,
                'end_time' => null
            ]
        ];
        $hours_unavailable_second_time_slot = [
            'hours' => 0,
            'periods' => [
                'start_time' => null,
                'end_time' => null
            ]
        ];

        $startDateTime = null;
        $endDateTime = null;

        //Si on est sur la première date de la période alors on attribue l'heure de fin de la journée à 23:59
        //Si on est sur la dernière date de la période alors on attribue l'heure de début de la journée à 00:00
        //Si on est entre le début et la fin de la période alors on attribue l'heure de début de la journée à 00:00 et l'heure de fin de la journée à 23:59

        $startDateTime = $fisrt_date ? $fisrt_date : Carbon::parse($day->startOfDay());
        $endDateTime = $last_date ? $last_date : Carbon::parse($day->endOfDay());

        //return array($startDateTime, $endDateTime);

        //On regarde si l'indiponiblité est répartie sur le matin et/ou l'après-midi
        if ($dayHours->morning_ends_at && $dayHours->morning_starts_at) {
            $first_time_slot_end = Carbon::createFromFormat('Y-m-d H:i:s', $day->format('Y-m-d') . ' ' . $dayHours->morning_ends_at);
            $first_time_slot_start = Carbon::createFromFormat('Y-m-d H:i:s', $day->format('Y-m-d') . ' ' . $dayHours->morning_starts_at);

            //Matin
            if ($startDateTime < $first_time_slot_end) {
                $first_time_slot_start_unavailable = null;
                $first_time_slot_end_unavailable = null;

                //On regarde si la l'heure du début de l'indisponibilité ne commence pas avant l'horaire d'embauche du matin
                if ($startDateTime > $first_time_slot_start) {
                    $first_time_slot_start_unavailable = $startDateTime;
                } else { //Sinon, on prend l'heure d'embauche
                    $first_time_slot_start_unavailable = $first_time_slot_start;
                }

                //On regarde si la l'heure de fin de l'indisponibilité ne commence pas après l'horaire de débauche du midi
                if ($endDateTime <= $first_time_slot_end) {
                    $first_time_slot_end_unavailable = $endDateTime;
                } else { //Sinon, on prend de débauche du midi.
                    $first_time_slot_end_unavailable = $first_time_slot_end;
                }

                $hours_unavailable_first_time_slot = [
                    'hours' => Carbon::parse($first_time_slot_end_unavailable)->floatDiffInHours(Carbon::parse($first_time_slot_start_unavailable)),
                    'periods' => [
                        'start_time' => $first_time_slot_start_unavailable,
                        'end_time' => $first_time_slot_end_unavailable
                    ]
                ];
            }
        }

        if ($dayHours->afternoon_ends_at && $dayHours->afternoon_starts_at) {
            $second_time_slot_end = Carbon::createFromFormat('Y-m-d H:i:s', $day->format('Y-m-d') . ' ' . $dayHours->afternoon_ends_at);
            $second_time_slot_start = Carbon::createFromFormat('Y-m-d H:i:s', $day->format('Y-m-d') . ' ' . $dayHours->afternoon_starts_at);

            //Après midi
            if ($endDateTime > $second_time_slot_start) {
                $second_time_slot_start_unavailable = null;
                $second_time_slot_end_unavailable = null;

                //On regarde si la l'heure du début de l'indisponibilité ne commence pas avant l'horaire d'embauche d'après-midi
                if ($startDateTime > $second_time_slot_start) {
                    $second_time_slot_start_unavailable = $startDateTime;
                } else { //Sinon, on prend l'heure d'embauche d'après-midi
                    $second_time_slot_start_unavailable = $second_time_slot_start;
                }

                //On regarde si la l'heure de fin de l'indisponibilité ne commence pas après l'horaire de débauche du soir
                if ($endDateTime <= $second_time_slot_end) {
                    $second_time_slot_end_unavailable = $endDateTime;
                } else { //Sinon, on prend de débauche du soir.
                    $second_time_slot_end_unavailable = $second_time_slot_end;
                }

                $hours_unavailable_second_time_slot = [
                    'hours' => Carbon::parse($second_time_slot_end_unavailable)->floatDiffInHours(Carbon::parse($second_time_slot_start_unavailable)),
                    'periods' => [
                        'start_time' => $second_time_slot_start_unavailable,
                        'end_time' => $second_time_slot_end_unavailable
                    ]
                ];;
            }
        }

        return array('first_time_slot' => $hours_unavailable_first_time_slot, 'second_time_slot' => $hours_unavailable_second_time_slot);
    }

    private function mergeHoursUnavailable($hoursUnavailableByPeriod)
    {

        //On regarde si la journée contient une ou plusieurs indisponnibilités
        if (count($hoursUnavailableByPeriod) == 1) {
            $response = $hoursUnavailableByPeriod[0];
        } else {
            $hoursUnavailable = [
                'first_time_slot' => [
                    'hours' => 0,
                    'periods' => []
                ],
                'second_time_slot' => [
                    'hours' => 0,
                    'periods' => []
                ]
            ];

            foreach ($hoursUnavailableByPeriod as $hours) {

                if ($hours['first_time_slot']['hours'] != 0) {
                    $hoursUnavailable['first_time_slot']['hours'] += $hours['first_time_slot']['hours'];
                    $hoursUnavailable['first_time_slot']['periods'][] = $hours['first_time_slot']['periods'];
                }

                if ($hours['second_time_slot']['hours'] != 0) {
                    $hoursUnavailable['second_time_slot']['hours'] += $hours['second_time_slot']['hours'];
                    $hoursUnavailable['second_time_slot']['periods'][] = $hours['second_time_slot']['periods'];
                }
            }
            $response = $hoursUnavailable;
        }
        return $response;
    }

    private function transformDatesToPeriod($unavailabilities)
    {

        if (isset($unavailabilities[0])) {
            $periods = [];
            foreach ($unavailabilities as $unavailability) {
                $start_date = Carbon::createFromFormat('Y-m-d H:i:s', $unavailability->starts_at);
                $end_date = Carbon::createFromFormat('Y-m-d H:i:s', $unavailability->ends_at);

                $period = CarbonPeriod::create(Carbon::createFromFormat('Y-m-d H:i:s', $unavailability->starts_at)->startOfDay(), Carbon::createFromFormat('Y-m-d H:i:s', $unavailability->ends_at)->startOfDay());

                $periods[] = [
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'period' => $period,
                ];
            }
            return $periods;
        } else {
            return false;
        }
    }

    private function buildPlannings($first_time_slot_start, $first_time_slot_end, $second_time_slot_start = null, $second_time_slot_end = null, $user, $compiledUnavailablePeriods)
    {
        $planning = [];

        //On construit le planning des heures dispo de l'utilisateur
        $first_time_slot_start && $first_time_slot_end ? $planning[] = array(
            'start_time' => $first_time_slot_start,
            'end_time' => $first_time_slot_end,
            'hours' => Carbon::parse($first_time_slot_end)->floatDiffInHours(Carbon::parse($first_time_slot_start)),
            'user' => $user,
            'additional_period' => true,
        ) : null;

        isset($second_time_slot_start) && isset($second_time_slot_end) ? $planning[] = array(
            'start_time' => $second_time_slot_start,
            'end_time' => $second_time_slot_end,
            'hours' => Carbon::parse($second_time_slot_end)->floatDiffInHours(Carbon::parse($second_time_slot_start)),
            'user' => $user,
            'additional_period' => true,
        ) : null;


        foreach ($compiledUnavailablePeriods as $unavailableHours) {

            usort($planning, array($this, 'date_sort'));
            $planning_temp = [];

            //Pour chaque indispo, on parcours les periodes de travail dispo afin de voir si elles contiennent l'indispo
            foreach ($planning as $key => $workHours) {

                $work_period = CarbonPeriod::create($workHours['start_time'], $workHours['end_time']);
                $unavailable_period = CarbonPeriod::create($unavailableHours['start_time'], $unavailableHours['end_time']);

                // l'indispo est comprise dans le créneau
                if ($work_period->contains($unavailableHours['start_time']) || $work_period->contains($unavailableHours['end_time'])) {

                    $old_period = $workHours;

                    if ($workHours['start_time'] != $unavailableHours['start_time'] || $workHours['end_time'] != $unavailableHours['end_time']) {

                        if ($workHours['start_time'] < $unavailableHours['start_time']) {

                            $period1 = $old_period;
                            $period1['end_time'] = $unavailableHours['start_time'];
                            $period1['hours'] = Carbon::parse($period1['end_time'])->floatDiffInHours(Carbon::parse($period1['start_time']));
                            $period1['additional_period'] = false;

                            array_push($planning_temp, $period1);
                        }

                        if ($workHours['end_time'] > $unavailableHours['end_time']) {

                            $period2 = $old_period;
                            $period2['start_time'] = $unavailableHours['end_time'];
                            $period2['hours'] = Carbon::parse($period2['end_time'])->floatDiffInHours(Carbon::parse($period2['start_time']));
                            $period2['additional_period'] = $period2['additional_period'] ? true : false;

                            array_push($planning_temp, $period2);
                        }
                    }
                }
                // le créneau est compris dans l'indispo : on retire le créneau
                elseif ($unavailable_period->contains($workHours['start_time']) && $unavailable_period->contains($workHours['end_time'])) {

                    // ne pas ajouter de créneau
                } else {
                    array_push($planning_temp, $workHours);
                }
            }

            $planning = $planning_temp;
        }

        usort($planning, array($this, 'date_sort'));
        return $planning;
    }

    private function date_sort($a, $b)
    {
        return strtotime($a['start_time']) - strtotime($b['start_time']);
    }

    //On tri les taches pour éviter les programmations des tâches enfantes avant les tâches parentes
    private function orderTasksByPrevious($tasks)
    {

        $tasksOrdered = [];
        foreach ($tasks as $task) {
            if ($task->previousTasks && count($task->previousTasks) > 0) {

                foreach ($task->previousTasks as $previous) {
                    $prior_task = $previous->previousTask;
                    $tasksOrdered = $this->checkIfPreviousTasks($prior_task, $tasksOrdered);
                }
            }
            $this->containsObject($task, $tasksOrdered) ? null : array_push($tasksOrdered, $task);
        }

        return $tasksOrdered;
    }

    private function checkIfPreviousTasks($task, $tasksOrdered)
    {

        if ($task->previousTasks && count($task->previousTasks) > 0) {

            foreach ($task->previousTasks as $previous) {
                $prior_task = $previous->previousTask;
                $tasksOrdered = $this->checkIfPreviousTasks($prior_task, $tasksOrdered);
            }
        }
        $this->containsObject($task, $tasksOrdered) ? null : array_push($tasksOrdered, $task);

        return $tasksOrdered;
    }

    private function containsObject($obj, $list)
    {

        for ($i = 0; $i < count($list); $i++) {
            if ($list[$i]->id == $obj->id) {
                return true;
            }
        }

        return false;
    }

    private function getWorkareasBySkills($skills, $company_id)
    {

        $workareas = Workarea::where('company_id', $company_id)->with('skills')->get();
        $response = [];

        $workareaIds = [];
        foreach ($workareas as $workarea) {
            $nbTime = count($skills);
            foreach ($skills as $skill) {
                $nbTime = $this->containsObject($skill, $workarea->skills) ? $nbTime - 1 : $nbTime;
            }

            if ($nbTime == 0) {
                array_push($response, $workarea);
            }
        }

        return $response;
    }

    private function compileHours($userHours, $user)
    {

        $compiledPeriods = [];
        $totalHours = 0;

        while (!empty($userHours)) {

            $period = [
                'user' => $user,
                'start_time' => null,
                'end_time' => null,
                'hours' => 0,
                'periods' => []
            ];
            $lastKey = key(array_slice($userHours, -1, 1, true));
            foreach ($userHours as $key => $hour) {

                $period['hours'] += $hour['hours'];
                $period['start_time'] ? null : $period['start_time'] = $hour['start_time'];
                array_push($period['periods'], array('start_time' => $hour['start_time'], 'end_time' => $hour['end_time']));

                unset($userHours[$key]);

                if (!$hour['additional_period'] || $key == $lastKey) {

                    $period['end_time'] = $hour['end_time'];
                    $totalHours += $period['hours'];
                    array_push($compiledPeriods, $period);

                    break;
                }
            }
        }
        return array('periods' => $compiledPeriods, 'total_hours' => $totalHours);
    }

    private function filterUnavailablePeriodsByDay($unAvailablePeriods, $date)
    {

        $periodsDate = [];

        $start_date = $date->copy()->startOfDay();
        $end_date = $date->copy()->endOfDay();

        //On regarde si la periode est hors/comprise/deborde de la date envoyée en paramètre
        foreach ($unAvailablePeriods as $unavailablePeriod) {

            $period = [
                'start_time' => null,
                'end_time' => null,
            ];

            $test = $unavailablePeriod['end_date'] < $start_date;
            $test2 = $unavailablePeriod['start_date'] > $end_date;

            //Hors de notre date
            if ($unavailablePeriod['end_date'] < $start_date || $unavailablePeriod['start_date'] > $end_date) {
            } else {

                if ($start_date >= $unavailablePeriod['start_date'] && $unavailablePeriod['end_date'] <= $end_date) { //la periode est comprise dans notre date

                    $new_start_time = $unavailablePeriod['start_date'];
                    $new_end_time = $unavailablePeriod['end_date'];
                } else { // la periode deborde de notre date

                    $new_start_time = $unavailablePeriod['start_date'] < $start_date ? $start_date : $unavailablePeriod['start_date'];
                    $new_end_time = $unavailablePeriod['end_date'] > $end_date ? $end_date : $unavailablePeriod['end_date'];
                }

                $period = [
                    'start_time' => Carbon::createFromFormat('Y-m-d H:i:s', $new_start_time),
                    'end_time' => Carbon::createFromFormat('Y-m-d H:i:s', $new_end_time),
                    'period' => CarbonPeriod::create(Carbon::createFromFormat('Y-m-d H:i:s', $new_start_time), Carbon::createFromFormat('Y-m-d H:i:s', $new_end_time))
                ];

                $periodsDate[] = $period;
            }
        }

        return count($periodsDate) > 0 ? $periodsDate : null;
    }

    private function compileUnavailablePeriods($unAvailablePeriods)
    {

        $compiledUnavailablePeriods = [];

        while (!empty($unAvailablePeriods)) {

            $first_element = array_shift($unAvailablePeriods);
            $period = [
                'start_time' => $first_element['start_time'],
                'end_time' => $first_element['end_time'],
                'period' => $first_element['period'],
            ];
            $lastKey = key(array_slice($unAvailablePeriods, -1, 1, true));

            foreach ($unAvailablePeriods as $key => $hour) {

                //On regarde si la période peut être mixer
                if (
                    $period['period']->contains($hour['start_time']) || $period['period']->contains($hour['end_time'])
                    || $hour['period']->contains($period['start_time']) || $hour['period']->contains($period['end_time'])
                ) {

                    //On prend le min start_time et le max end_time
                    $period['start_time'] >= $hour['start_time'] ? $period['start_time'] = $hour['start_time'] : null;
                    $period['end_time'] <= $hour['end_time'] ? $period['end_time'] = $hour['end_time'] : null;

                    unset($unAvailablePeriods[$key]);
                }
            }

            $period['period'] = CarbonPeriod::create($period['start_time'], $period['end_time']);
            $compiledUnavailablePeriods[] = $period;
        }
        return $compiledUnavailablePeriods;
    }

    private function getNewPeriodsWorkareaTasksLess($tasksWorkarea, $availablePeriod)
    {

        $unchanged = true;
        $periods = $availablePeriod['periods'];
        $globalPeriod = CarbonPeriod::create($availablePeriod['start_time'], $availablePeriod['end_time']);

        foreach ($tasksWorkarea as $taskWorkarea) {

            $start_task = Carbon::parse($taskWorkarea->date);
            $end_task = Carbon::parse($taskWorkarea->date_end);
            $workareaTaskperiod = CarbonPeriod::create($start_task, $end_task);

            //On regarde si la tache est dans la periode
            if ($globalPeriod->contains($start_task) || $globalPeriod->contains($end_task)) {

                $unchanged = false;

                //On transforme la periode pour prendre en compte les taches de l'ilôt afin d'avoir les dispo
                $temp_periods = $periods;
                foreach ($temp_periods as $key => $period) {

                    unset($periods[$key]);
                    $period_period = CarbonPeriod::create($period['start_time'], $period['end_time']);
                    if ($period_period->contains($start_task) || $period_period->contains($end_task)) {


                        $old_period = $period;

                        if ($period['start_time'] < $start_task) {

                            $period1 = $old_period;
                            $period1['end_time'] = $start_task;
                            $period1['hours'] = Carbon::parse($period1['end_time'])->floatDiffInHours(Carbon::parse($period1['start_time']));
                            $period1['additional_period'] = false;

                            array_push($periods, $period1);
                        }

                        if ($period['end_time'] > $end_task) {

                            $period2 = $old_period;
                            $period2['start_time'] = $end_task;
                            $period2['hours'] = Carbon::parse($period2['end_time'])->floatDiffInHours(Carbon::parse($period2['start_time']));
                            $period2['additional_period'] = true;

                            array_push($periods, $period2);
                        }
                    }
                    //On verifie si la tache n'englobe pas la periode
                    elseif ($workareaTaskperiod->contains($period['start_time']) && $workareaTaskperiod->contains($period['end_time'])) {
                        //do nothing
                    } else {

                        $newPeriod = $period;
                        $newPeriod['hours'] = Carbon::parse($newPeriod['end_time'])->floatDiffInHours(Carbon::parse($newPeriod['start_time']));
                        $newPeriod['additional_period'] = true;

                        array_push($periods, $newPeriod);
                    }
                }
            }
            //On verifie si la tache n'englobe pas la periode
            elseif ($workareaTaskperiod->contains($availablePeriod['start_time']) && $workareaTaskperiod->contains($availablePeriod['end_time'])) {

                return null;
            }
        }


        if (!$unchanged) {
            usort($periods, array($this, 'date_sort'));
            $newPeriods = $this->compileHours($periods, $availablePeriod['user']);
            $response = $newPeriods['periods'];
        } else {
            $response = array($availablePeriod);
        }

        return $response;
    }
}
