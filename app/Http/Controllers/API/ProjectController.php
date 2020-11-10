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
        if ($user->hasRole('superAdmin')) {
            $items = Project::withTrashed()->get()->load('company')->load('customer', 'documents');
        } else if ($user->company_id != null) {
            $items = Project::where('company_id', $user->company_id)->get()->load('company')->load('customer', 'documents');
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
        $item = Project::where('id', $id)->first();
        return response()->json(['success' => $item->load('documents')], $this->successStatus);
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

        $update = Project::where('id', $id)
            ->update([
                'name' => $arrayRequest['name'],
                'date' => $arrayRequest['date'],
                'company_id' => $arrayRequest['company_id'],
                'customer_id' => $arrayRequest['customer_id'],
                'color' => $arrayRequest['color']
            ]);

        if (!$update) {
            return response()->json(['error' => 'error'], $this->errorStatus);
        }

        $item = Project::find($id)->load('company')->load('customer');

        if (isset($arrayRequest['token'])) {
            $this->storeProjectDocuments($item, $arrayRequest['token']);
        }

        if (isset($arrayRequest['documents'])) {
            $documents = $item->documents()->whereNotIn('id', array_map(function ($doc) {
                return $doc['id'];
            }, $arrayRequest['documents']))->get();

            foreach ($documents as $doc) {
                $doc->deleteFile();
            }
        }

        return response()->json(['success' => $item->load('documents')], $this->successStatus);
    }

    /**
     * Restore the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function restore($id)
    {
        try {
            $item = Project::withTrashed()->findOrFail($id);
            $success = $item->restoreCascade();

            if ($success) {
                return response()->json(['success' => $item->load('company')], $this->successStatus);
            } else {
                throw new Exception('Impossible de restaurer le projet');
            }
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'error' => $th->getMessage()], 400);
        }
    }

    /**
     * Archive the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $item = Project::findOrFail($id);
            $success = $item->deleteCascade();

            if (!$success) {
                throw new Exception('Impossible d\'archiver le projet');
            }

            return response()->json(['success' => $item->load('company')], $this->successStatus);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'error' => $th->getMessage()], 400);
        }
    }

    /**
     * forceDelete the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function forceDelete($id)
    {
        try {
            $item = Project::withTrashed()->findOrFail($id);
            $success = $item->forceDeleteCascade();

            if (!$success) {
                throw new Exception('Impossible de supprimer le projet');
            }

            return response()->json(['success' => true], $this->successStatus);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'error' => $th->getMessage()], 400);
        }
    }

    public function start($id)
    {

        // RAF :
        //  - Gestion des heures indispo de + de 3,5h 
        //  - Gestion des tâches de + de 3,5h 
        //  - Corriger la redirection vers les plannings

        $project = Project::find($id);
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
            $TimeData = $this->calculTimeAvailable($users, $project->date, $users);

            if ($TimeData['total_hours'] - $TimeData['total_hours_unavailable'] >= $nbHoursRequired) {

                $response = $this->setDateToTasks($project->tasks, $TimeData, $users, $project);
                Project::where('id', $id)->update(['start_date' => Carbon::now()]);
                return response()->json(['success' => $response], $this->successStatus);
            } else {

                return $TimeData['total_hours'] < $nbHoursRequired ? response()->json(['error_time' => 'time_less'], $this->successStatus) : response()->json(['error' => 'user_time_less'], $this->successStatus);
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

        $tasksTemp = $this->orderTasksByPrevious($tasks);
        $NoPlanTasks = [];

        //Foreach jours jusqu'a la date de livraison


        foreach ($TimeData['details'] as $date) {

            // On regarde si on a des heures disponibles pour plannifier une tache
            if (($date['total_hours'] - $date['total_hours_unavailable']) > 0) {

                //On parcours chaque tache 
                foreach ($tasksTemp as $keytask => $task) {

                    if ($task->date == null) {
                        // On récupère les compétences de la tache
                        $taskSkills = $task->skills;
                        $taskPlan = false;

                        //On parcours la liste des périodes disponible et on regarde si la durée de la tache est inférieur ou égale a la periode disponilbe
                        foreach ($date['periods_available'] as $key => $period) {

                            if ($task->estimated_time <= $period['hours'] && !$taskPlan) {

                                $previousOk = true;
                                //On regarde si la tache est dépendante d'autre(s) tache(s)
                                if ($task->previousTasks && count($task->previousTasks) > 0) {

                                    //Si oui, on regarde si les taches sont déjà programmées et si la période est supérieur à la tâche qui précède  
                                    foreach ($task->previousTasks as $previous) {
                                        $previous_task = Task::find($previous->previous_task_id);
                                        if ($previous_task->date == null || $previous_task->dateEnd > $period['start_time']) {
                                            $previousOk = false;
                                            break;
                                        }
                                    }
                                }

                                //On regarde si un ilôt est disponible pendant la période 
                                $workareaOk = null;
                                $workareas = $this->getWorkareasBySkills($task->skills, $project->company_id);

                                foreach ($workareas as $workarea) {
                                    $tasksWorkarea = Task::where('workarea_id', $workarea->id)->whereNotNull('date')->where('status', '!=', 'done')->get();
                                    if (count($tasksWorkarea) > 0) {
                                        foreach ($tasksWorkarea as $taskWorkarea) {
                                            $workareaTaskperiod = CarbonPeriod::create($taskWorkarea->date, $taskWorkarea->dateEnd);

                                            if (
                                                !$workareaTaskperiod->contains($period['start_time'])
                                                && !$workareaTaskperiod->contains($period['end_time'])
                                                && !($period['start_time'] <= $taskWorkarea->date && $taskWorkarea->dateEnd <= $period['end_time'])
                                            ) {


                                                $workareaOk = $workarea;
                                                break;
                                            }
                                        }
                                    } else {
                                        $workareaOk = $workarea;
                                    }
                                }



                                //On regarde si la tache est conforme pour être programmée
                                if ($previousOk && $workareaOk) {

                                    // On regarde si l'utilisateur de la période possède les compétences nécéssaires
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
                                            $tasksTemp[$keytask] = tap(Task::findOrFail($task->id))->update(['date' => $period['start_time'], 'user_id' => $period['user']->id, 'workarea_id' => $workareaOk->id])->fresh();
                                            $taskPlan = true;

                                            //On enlève la période des heures dispo de l'utilisateur
                                            $date['periods_available'][$key]['hours'] = $period['hours'] - $task->estimated_time;
                                            if ($date['periods_available'][$key]['hours'] > 0) {
                                                $date['periods_available'][$key]['start_time'] = Carbon::parse($period['start_time'])->addHours($task->estimated_time);
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        //On informe si les utilisateurs ne possèdent pas les compétences nécessaires pour une certaine tache
                        //return response()->json(['error' => 'No enought users with skills'], 401); 
                    }
                }
            }
        }

        //On regarde si toute les tâches ont été plannifé
        $allPlanified = true;
        $taskIds = [];

        foreach ($tasksTemp as $taskTemp) {
            array_push($taskIds, $taskTemp->id);

            if (!$taskTemp->date || !$taskTemp->user_id) {
                $allPlanified = false;
            }
        }


        //Si toutes les taches ont été planifié, on passe le projet en `doing` et on return success
        $alerts = null;
        if ($allPlanified) {
            Project::findOrFail($project->id)->update(['status' => 'doing']);

            $alerts = ['success' => 'All good'];
        } else { // Si non, on reboot les taches planifiés et on retourne les alertes a l'utilisateur

            Task::whereIn('id', $taskIds)->update(['date' => null, 'user_id' => null, 'workarea_id' => null]);
        }

        return $alerts;
    }

    private function calculTimeAvailable($users, $date_end)
    {
        $hoursAvailable = [];

        // Get days today date -> end date
        $start_date = Carbon::now()->addDays('1')->startOfDay();
        $end_date = Carbon::createFromFormat('Y-m-d H:i:s', $date_end)->subDays('1')->endOfDay();

        $period = CarbonPeriod::create($start_date, $end_date);

        foreach ($period as $t) {
            $hoursAvailable[] = [
                'date' => $t,
                'day_label' => $t->format('l')
            ];
        }

        $totalHours = 0;
        $totalHoursUnavailable = 0;

        // foreach days foreach users get day get hours
        foreach ($hoursAvailable as $key => $data) {
            $test[] = $data['day_label'];
            $totalDateHours = 0;
            switch (true) {
                case ($data['day_label'] == 'Monday' || $data['day_label'] == 'Lundi'):

                    $dayHours = $this->getHoursAvailableByDay('lundi', $data['date'], $users);
                    //Raf already task in this date

                    if ($dayHours) {
                        $totalHours += $dayHours['total'];
                        $totalHoursUnavailable += $dayHours['total_unavailable'];
                        $data['users'] = $dayHours['users'];
                        $data['periods_available'] = $dayHours['periods_available'];
                        $data['total_hours'] = $dayHours['total'];
                        $data['total_hours_unavailable'] = $dayHours['total_unavailable'];

                        $hoursAvailable[$key] = $data;
                    }
                    break;

                case ($data['day_label'] == 'Tuesday' || $data['day_label'] == 'Mardi'):

                    $dayHours = $this->getHoursAvailableByDay('mardi', $data['date'], $users);
                    //Raf already task in this date

                    if ($dayHours) {
                        $totalHours += $dayHours['total'];
                        $totalHoursUnavailable += $dayHours['total_unavailable'];
                        $data['users'] = $dayHours['users'];
                        $data['periods_available'] = $dayHours['periods_available'];
                        $data['total_hours'] = $dayHours['total'];
                        $data['total_hours_unavailable'] = $dayHours['total_unavailable'];

                        $hoursAvailable[$key] = $data;
                    }
                    break;

                case ($data['day_label'] == 'Wednesday' || $data['day_label'] == 'Mercredi'):

                    $dayHours = $this->getHoursAvailableByDay('mercredi', $data['date'], $users);
                    //Raf already task in this date

                    if ($dayHours) {
                        $totalHours += $dayHours['total'];
                        $totalHoursUnavailable += $dayHours['total_unavailable'];
                        $data['users'] = $dayHours['users'];
                        $data['periods_available'] = $dayHours['periods_available'];
                        $data['total_hours'] = $dayHours['total'];
                        $data['total_hours_unavailable'] = $dayHours['total_unavailable'];

                        $hoursAvailable[$key] = $data;
                    }
                    break;

                case ($data['day_label'] == 'Thursday' || $data['day_label'] == 'Jeudi'):

                    $dayHours = $this->getHoursAvailableByDay('jeudi', $data['date'], $users);
                    //Raf already task in this date

                    if ($dayHours) {
                        $totalHours += $dayHours['total'];
                        $totalHoursUnavailable += $dayHours['total_unavailable'];
                        $data['users'] = $dayHours['users'];
                        $data['periods_available'] = $dayHours['periods_available'];
                        $data['total_hours'] = $dayHours['total'];
                        $data['total_hours_unavailable'] = $dayHours['total_unavailable'];

                        $hoursAvailable[$key] = $data;
                    }
                    break;

                case ($data['day_label'] == 'Friday' || $data['day_label'] == 'Vendredi'):

                    $dayHours = $this->getHoursAvailableByDay('vendredi', $data['date'], $users);
                    //Raf already task in this date

                    if ($dayHours) {
                        $totalHours += $dayHours['total'];
                        $totalHoursUnavailable += $dayHours['total_unavailable'];
                        $data['users'] = $dayHours['users'];
                        $data['periods_available'] = $dayHours['periods_available'];
                        $data['total_hours'] = $dayHours['total'];
                        $data['total_hours_unavailable'] = $dayHours['total_unavailable'];

                        $hoursAvailable[$key] = $data;
                    }
                    break;

                case ($data['day_label'] == 'Saturday' || $data['day_label'] == 'Samedi'):

                    $dayHours = $this->getHoursAvailableByDay('samedi', $data['date'], $users);
                    //Raf already task in this date

                    if ($dayHours) {
                        $totalHours += $dayHours['total'];
                        $totalHoursUnavailable += $dayHours['total_unavailable'];
                        $data['users'] = $dayHours['users'];
                        $data['periods_available'] = $dayHours['periods_available'];
                        $data['total_hours'] = $dayHours['total'];
                        $data['total_hours_unavailable'] = $dayHours['total_unavailable'];

                        $hoursAvailable[$key] = $data;
                    }
                    break;

                case ($data['day_label'] == 'Sunday' || $data['day_label'] == 'Dimanche'):

                    $dayHours = $this->getHoursAvailableByDay('dimanche', $data['date'], $users);
                    //Raf already task in this date

                    if ($dayHours) {
                        $totalHours += $dayHours['total'];
                        $totalHoursUnavailable += $dayHours['total_unavailable'];
                        $data['users'] = $dayHours['users'];
                        $data['periods_available'] = $dayHours['periods_available'];
                        $data['total_hours'] = $dayHours['total'];
                        $data['total_hours_unavailable'] = $dayHours['total_unavailable'];

                        $hoursAvailable[$key] = $data;
                    }
                    break;
            }

            $totalHours += $totalDateHours;
        }

        return $response = [
            'details' => $hoursAvailable,
            'total_hours' => $totalHours,
            'total_hours_unavailable' => $totalHoursUnavailable
        ];
    }

    private function getHoursAvailableByDay($day, $date, $users)
    {
        $hours = [
            'total' => 0,
            'total_unavailable' => 0,
            'users' => [],
            'periods_available' => []
        ];
        $conflictUnavailableDate = false;

        foreach ($users as $user) {
            $userHours = [
                'user_id' => $user->id,
                'hours' => 0,
                'hours_unavailable' => 0,
                'hours_tasks_other_project' => 0,
                'periods_available' => []
            ];

            $unAvailablePeriods = count($user->unavailabilities) > 0 ? $this->transformDatesToPeriod($user->unavailabilities) : null;

            foreach ($user->workHours as $dayHours) {

                if ($dayHours->day == $day && (string) $dayHours->is_active) {

                    //Hours available
                    if ($dayHours->morning_ends_at && $dayHours->morning_starts_at) {
                        $morningEnd = Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' ' . $dayHours->morning_ends_at);
                        $morningStart = Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' ' . $dayHours->morning_starts_at);

                        $userHours['hours'] += Carbon::parse($morningEnd)->floatDiffInHours(Carbon::parse($morningStart));
                    }
                    if ($dayHours->afternoon_ends_at && $dayHours->afternoon_starts_at) {
                        $AfternoonEnd = Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' ' . $dayHours->afternoon_ends_at);
                        $AfternoonStart = Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' ' . $dayHours->afternoon_starts_at);

                        $userHours['hours'] += Carbon::parse($AfternoonEnd)->floatDiffInHours(Carbon::parse($AfternoonStart));
                    }

                    //Hours unavailable
                    if ($unAvailablePeriods) {

                        $workDate = Carbon::createFromFormat('Y-m-d H:i:s', $date);
                        $hoursUnavailableByPeriod = [];

                        foreach ($unAvailablePeriods as $period) {

                            //On regarde si la période ne comprend que une seule journée
                            if (count($period['period']) == 1) {

                                $unavailableDate = $period['period']->getStartDate();

                                if ($unavailableDate == $workDate) {

                                    //On calcul le nombre d'heures d'indisponibilité
                                    $hoursUnavailableByPeriod[] = $this->calculNbHoursUnavailable($dayHours, $period);
                                }
                            } else { //plusieurs journées
                                $startDate = $period['period']->getStartDate();
                                $endDate = $period['period']->getEndDate();

                                foreach ($period['period'] as $periodDay) {

                                    if ($periodDay == $workDate) {
                                        if ($workDate == $startDate) {
                                            $hoursUnavailableByPeriod[] = $this->calculNbHoursUnavailablePeriod($dayHours, $periodDay, $period['start_date']);
                                        } else if ($workDate == $endDate) {
                                            $hoursUnavailableByPeriod[] = $this->calculNbHoursUnavailablePeriod($dayHours, $periodDay, null, $period['end_date']);
                                        } else {
                                            $hoursUnavailableByPeriod[] = $this->calculNbHoursUnavailablePeriod($dayHours, $periodDay);
                                        }
                                    }
                                }
                            }
                        }
                        $hours_unavailable_details = $this->mergeHoursUnavailable($hoursUnavailableByPeriod);

                        $userHours['hours_unavailable_details'] = $hours_unavailable_details;
                        $userHours['hours_unavailable'] = $hours_unavailable_details['afternoon']['hours'] + $hours_unavailable_details['morning']['hours'];
                    }

                    //Hours Tasks other Project
                    $tasks_user_day = Task::where('user_id', $user->id)->where('date', 'like', '%' . $date->format('Y-m-d') . '%')->get();
                    if (count($tasks_user_day) > 0) {

                        $nbHours = 0;
                        $other_tasks_project_periods = [
                            'morning' => [],
                            'afternoon' => []
                        ];

                        foreach ($tasks_user_day as $task) {
                            $nbHours += $task->estimated_time;
                            $start_time = $task->date;
                            $end_time = Carbon::parse($task->date)->addHours($task->estimated_time);

                            if ($morningEnd && $period['start_date'] < $morningEnd) {
                                $other_tasks_project_periods['morning'][] = array('start_time' => $start_time, 'end_time' => $end_time);
                            }

                            if ($AfternoonStart && $period['end_date'] > $AfternoonStart) {
                                $other_tasks_project_periods['afternoon'][] = array('start_time' => $start_time, 'end_time' => $end_time);
                            }
                        }

                        $userHours['hours_tasks_other_project'] = $nbHours;
                    }

                    //On construit un planning de l'utilisateur avec les heures disponibles de la journée 
                    $periods = [];
                    if ($userHours['hours_unavailable'] > 0 || $userHours['hours_tasks_other_project'] > 0) {
                        $periods = [];
                        $periods_unavailable = [
                            'morning' => [],
                            'afternoon' => []
                        ];

                        if (isset($userHours['hours_unavailable_details'])) {
                            $periods_unavailable['afternoon'] = array_merge($periods_unavailable['afternoon'], $userHours['hours_unavailable_details']['afternoon']['periods']);
                            $periods_unavailable['morning'] = array_merge($periods_unavailable['morning'], $userHours['hours_unavailable_details']['morning']['periods']);
                        }

                        if (isset($userHours['hours_tasks_other_project'])) {
                            $periods_unavailable['afternoon'] = array_merge($periods_unavailable['afternoon'], $other_tasks_project_periods['afternoon']);
                            $periods_unavailable['morning'] = array_merge($periods_unavailable['morning'], $other_tasks_project_periods['morning']);
                        }

                        $periods = $this->reversePeriodsUnavailableToPeriods(
                            $periods_unavailable,
                            array('morningStart' => $morningStart, 'morningEnd' => $morningEnd, 'afternoonStart' => $AfternoonStart, 'afternoonEnd' => $AfternoonEnd),
                            $user
                        );
                    } else {
                        $periods[] = array(
                            'start_time' => $morningStart,
                            'end_time' => $morningEnd,
                            'hours' => Carbon::parse($morningEnd)->floatDiffInHours(Carbon::parse($morningStart)),
                            'user' => $user
                        );

                        $periods[] = array(
                            'start_time' => $AfternoonStart,
                            'end_time' => $AfternoonEnd,
                            'hours' => Carbon::parse($AfternoonEnd)->floatDiffInHours(Carbon::parse($AfternoonStart)),
                            'user' => $user
                        );
                    }

                    $userHours['periods_available'] = $periods;
                }
            }



            if ($userHours['hours'] != 0) {
                $hours['total'] += $userHours['hours'];
                $hours['total_unavailable'] += $userHours['hours_unavailable'];
                $hours['users'][] = $userHours;

                $hours['periods_available'] = array_merge($hours['periods_available'], $userHours['periods_available']);
                usort($hours['periods_available'], array($this, 'date_sort'));
            }
        }
        return empty($hours) ? null : $hours;
    }

    private function calculNbHoursUnavailable($dayHours, $period, $hours_unavailable = null)
    {

        $hours_unavailable_morning = [
            'hours' => 0,
            'periods' => [
                'start_time' => null,
                'end_time' => null
            ]
        ];
        $hours_unavailable_afternoon = [
            'hours' => 0,
            'periods' => [
                'start_time' => null,
                'end_time' => null
            ]
        ];

        //On regarde si l'indiponiblité est répartie sur le matin et/ou l'après-midi
        if ($dayHours->morning_ends_at && $dayHours->morning_starts_at) {
            $morningEnd = Carbon::createFromFormat('Y-m-d H:i:s', $period['period']->getStartDate()->format('Y-m-d') . ' ' . $dayHours->morning_ends_at);
            $morningStart = Carbon::createFromFormat('Y-m-d H:i:s', $period['period']->getStartDate()->format('Y-m-d') . ' ' . $dayHours->morning_starts_at);

            //Matin
            if ($period['start_date'] < $morningEnd) {
                $startMorningUnavailable = null;
                $endMorningUnavailable = null;

                //On regarde si la l'heure du début de l'indisponibilité ne commence pas avant l'horaire d'embauche du matin
                if ($period['start_date'] > $morningStart) {
                    $startMorningUnavailable = $period['start_date'];
                } else { //Sinon, on prend l'heure d'embauche
                    $startMorningUnavailable = $morningStart;
                }

                //On regarde si la l'heure de fin de l'indisponibilité ne commence pas après l'horaire de débauche du midi
                if ($period['end_date'] <= $morningEnd) {
                    $endMorningUnavailable = $period['end_date'];
                } else { //Sinon, on prend de débauche du midi.
                    $endMorningUnavailable = $morningEnd;
                }

                $hours_unavailable_morning = [
                    'hours' => Carbon::parse($endMorningUnavailable)->floatDiffInHours(Carbon::parse($startMorningUnavailable)),
                    'periods' => [
                        'start_time' => $startMorningUnavailable,
                        'end_time' => $endMorningUnavailable
                    ]
                ];
            }
        }

        if ($dayHours->afternoon_ends_at && $dayHours->afternoon_starts_at) {
            $AfternoonEnd = Carbon::createFromFormat('Y-m-d H:i:s', $period['period']->getStartDate()->format('Y-m-d') . ' ' . $dayHours->afternoon_ends_at);
            $AfternoonStart = Carbon::createFromFormat('Y-m-d H:i:s', $period['period']->getStartDate()->format('Y-m-d') . ' ' . $dayHours->afternoon_starts_at);

            //Après midi
            if ($period['end_date'] > $AfternoonStart) {
                $startAfternoonUnavailable = null;
                $endAfternoonUnavailable = null;

                //On regarde si la l'heure du début de l'indisponibilité ne commence pas avant l'horaire d'embauche d'après-midi
                if ($period['start_date'] > $AfternoonStart) {
                    $startAfternoonUnavailable = $period['start_date'];
                } else { //Sinon, on prend l'heure d'embauche d'après-midi
                    $startAfternoonUnavailable = $AfternoonStart;
                }

                //On regarde si la l'heure de fin de l'indisponibilité ne commence pas après l'horaire de débauche du soir
                if ($period['end_date'] <= $AfternoonEnd) {
                    $endAfternoonUnavailable = $period['end_date'];
                } else { //Sinon, on prend de débauche du midi.
                    $endAfternoonUnavailable = $AfternoonEnd;
                }

                $hours_unavailable_afternoon = [
                    'hours' => Carbon::parse($endAfternoonUnavailable)->floatDiffInHours(Carbon::parse($startAfternoonUnavailable)),
                    'periods' => [
                        'start_time' => $startAfternoonUnavailable,
                        'end_time' => $endAfternoonUnavailable
                    ]
                ];;
            }
        }

        return array('morning' => $hours_unavailable_morning, 'afternoon' => $hours_unavailable_afternoon);
    }


    private function calculNbHoursUnavailablePeriod($dayHours, $day, $fisrt_date = null, $last_date = null)
    {

        $hours_unavailable_morning = [
            'hours' => 0,
            'periods' => [
                'start_time' => null,
                'end_time' => null
            ]
        ];
        $hours_unavailable_afternoon = [
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
            $morningEnd = Carbon::createFromFormat('Y-m-d H:i:s', $day->format('Y-m-d') . ' ' . $dayHours->morning_ends_at);
            $morningStart = Carbon::createFromFormat('Y-m-d H:i:s', $day->format('Y-m-d') . ' ' . $dayHours->morning_starts_at);

            //Matin
            if ($startDateTime < $morningEnd) {
                $startMorningUnavailable = null;
                $endMorningUnavailable = null;

                //On regarde si la l'heure du début de l'indisponibilité ne commence pas avant l'horaire d'embauche du matin
                if ($startDateTime > $morningStart) {
                    $startMorningUnavailable = $startDateTime;
                } else { //Sinon, on prend l'heure d'embauche
                    $startMorningUnavailable = $morningStart;
                }

                //On regarde si la l'heure de fin de l'indisponibilité ne commence pas après l'horaire de débauche du midi
                if ($endDateTime <= $morningEnd) {
                    $endMorningUnavailable = $endDateTime;
                } else { //Sinon, on prend de débauche du midi.
                    $endMorningUnavailable = $morningEnd;
                }

                $hours_unavailable_morning = [
                    'hours' => Carbon::parse($endMorningUnavailable)->floatDiffInHours(Carbon::parse($startMorningUnavailable)),
                    'periods' => [
                        'start_time' => $startMorningUnavailable,
                        'end_time' => $endMorningUnavailable
                    ]
                ];
            }
        }

        if ($dayHours->afternoon_ends_at && $dayHours->afternoon_starts_at) {
            $AfternoonEnd = Carbon::createFromFormat('Y-m-d H:i:s', $day->format('Y-m-d') . ' ' . $dayHours->afternoon_ends_at);
            $AfternoonStart = Carbon::createFromFormat('Y-m-d H:i:s', $day->format('Y-m-d') . ' ' . $dayHours->afternoon_starts_at);

            //Après midi
            if ($endDateTime > $AfternoonStart) {
                $startAfternoonUnavailable = null;
                $endAfternoonUnavailable = null;

                //On regarde si la l'heure du début de l'indisponibilité ne commence pas avant l'horaire d'embauche d'après-midi
                if ($startDateTime > $AfternoonStart) {
                    $startAfternoonUnavailable = $startDateTime;
                } else { //Sinon, on prend l'heure d'embauche d'après-midi
                    $startAfternoonUnavailable = $AfternoonStart;
                }

                //On regarde si la l'heure de fin de l'indisponibilité ne commence pas après l'horaire de débauche du soir
                if ($endDateTime <= $AfternoonEnd) {
                    $endAfternoonUnavailable = $endDateTime;
                } else { //Sinon, on prend de débauche du soir.
                    $endAfternoonUnavailable = $AfternoonEnd;
                }

                $hours_unavailable_afternoon = [
                    'hours' => Carbon::parse($endAfternoonUnavailable)->floatDiffInHours(Carbon::parse($startAfternoonUnavailable)),
                    'periods' => [
                        'start_time' => $startAfternoonUnavailable,
                        'end_time' => $endAfternoonUnavailable
                    ]
                ];;
            }
        }

        return array('morning' => $hours_unavailable_morning, 'afternoon' => $hours_unavailable_afternoon);
    }

    private function mergeHoursUnavailable($hoursUnavailableByPeriod)
    {

        //On regarde si la journée contient une ou plusieurs indisponnibilités
        if (count($hoursUnavailableByPeriod) == 1) {
            $response = $hoursUnavailableByPeriod[0];
        } else {
            $hoursUnavailable = [
                'morning' => [
                    'hours' => 0,
                    'periods' => []
                ],
                'afternoon' => [
                    'hours' => 0,
                    'periods' => []
                ]
            ];

            foreach ($hoursUnavailableByPeriod as $hours) {

                if ($hours['morning']['hours'] != 0) {
                    $hoursUnavailable['morning']['hours'] += $hours['morning']['hours'];
                    $hoursUnavailable['morning']['periods'][] = $hours['morning']['periods'];
                }

                if ($hours['afternoon']['hours'] != 0) {
                    $hoursUnavailable['afternoon']['hours'] += $hours['afternoon']['hours'];
                    $hoursUnavailable['afternoon']['periods'][] = $hours['afternoon']['periods'];
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

    //Permet de transformer le planning des periodes indisponibles en périodes disponibles
    private function reversePeriodsUnavailableToPeriods($periods_unavailable, $hours_work, $user)
    {

        $periods = [];

        count($periods_unavailable['morning']) > 1 ? usort($periods_unavailable['morning'], array($this, 'date_sort')) : null;
        count($periods_unavailable['afternoon']) > 1 ? usort($periods_unavailable['afternoon'], array($this, 'date_sort')) : null;

        //Matin
        if ($hours_work['morningStart'] && $hours_work['morningEnd']) {
            //Si pas d'indiponibilité ni de tache d'autre projet
            if (count($periods_unavailable['morning']) == 0) {
                $period_available = [
                    'start_time' => $hours_work['morningStart'],
                    'end_time' => $hours_work['morningEnd'],
                    'hours' => Carbon::parse($hours_work['morningEnd'])->floatDiffInHours(Carbon::parse($hours_work['morningStart'])),
                    'user' => $user
                ];
                $periods[] = $period_available;
            } elseif (count($periods_unavailable['morning']) == 1) {

                // 1er cas : On regarde si la periode d'indispo est égale ou déborde de la période de travail
                if ($periods_unavailable['morning'][0]['start_time'] <= $hours_work['morningStart'] && $hours_work['morningEnd'] <= $periods_unavailable['morning'][0]['end_time']) {
                    $period_available = null;
                }

                // 2ème cas : On regarde si la période est strictement dans la periode de travail
                elseif ($periods_unavailable['morning'][0]['start_time'] > $hours_work['morningStart'] && $hours_work['morningEnd'] > $periods_unavailable['morning'][0]['end_time']) {

                    $period_available1 = [
                        'start_time' => $hours_work['morningStart'],
                        'end_time' => $periods_unavailable['morning'][0]['start_time'],
                        'hours' => Carbon::parse($periods_unavailable['morning'][0]['start_time'])->floatDiffInHours(Carbon::parse($hours_work['morningStart'])),
                        'user' => $user
                    ];

                    $period_available2 = [
                        'start_time' => $periods_unavailable['morning'][0]['end_time'],
                        'end_time' => $hours_work['morningEnd'],
                        'hours' => Carbon::parse($hours_work['morningEnd'])->floatDiffInHours(Carbon::parse($periods_unavailable['morning'][0]['end_time'])),
                        'user' => $user
                    ];

                    $periods[] = $period_available1;
                    $periods[] = $period_available2;
                } else {
                    $start_time = $periods_unavailable['morning'][0]['start_time'] > $hours_work['morningStart'] ? $periods_unavailable['morning'][0]['start_time'] : $hours_work['morningStart'];
                    $end_time = $hours_work['morningEnd'] > $periods_unavailable['morning'][0]['end_time'] ? $periods_unavailable['morning'][0]['end_time'] : $end_time = $hours_work['morningEnd'];
                    $period_available = [
                        'start_time' => $start_time,
                        'end_time' => $end_time,
                        'hours' => Carbon::parse($end_time)->floatDiffInHours(Carbon::parse($start_time)),
                        'user' => $user
                    ];

                    $periods[] = $period_available;
                }
            }

            // Si + de 1 indisponibilité
            else {
                //On regarde si le premier élément du tableau est supérieur a la l'heure d'embauche du matin
                if ($periods_unavailable['morning'][0]['start_time'] > $hours_work['morningStart']) {
                    $period_available = [
                        'start_time' => $hours_work['morningStart'],
                        'end_time' => $periods_unavailable['morning'][0]['start_time'],
                        'hours' => Carbon::parse($periods_unavailable['morning'][0]['start_time'])->floatDiffInHours(Carbon::parse($hours_work['morningStart'])),
                        'user' => $user
                    ];
                    $periods[] = $period_available;
                }

                foreach ($periods_unavailable['morning'] as $key => $morningPeriod) {
                    $start_time = $morningPeriod['end_time'];
                    $end_time = $periods_unavailable[$key + 1]  && $periods_unavailable[$key + 1]['start_time'] < $hours_work['morningEnd'] ? $periods_unavailable[$key + 1]['start_time'] : $hours_work['morningEnd'];
                    $period_available = [
                        'start_time' => $start_time,
                        'end_time' => $end_time,
                        'hours' => Carbon::parse($end_time)->floatDiffInHours(Carbon::parse($start_time)),
                        'user' => $user
                    ];

                    $periods[] = $period_available;
                }
            }
        }

        //Après-midi
        if ($hours_work['afternoonStart'] && $hours_work['afternoonEnd']) {
            //Si pas d'indiponibilité ni de tache d'autre proje
            if (count($periods_unavailable['afternoon']) == 0) {
                $period_available = [
                    'start_time' => $hours_work['afternoonStart'],
                    'end_time' => $hours_work['afternoonEnd'],
                    'hours' => Carbon::parse($hours_work['afternoonEnd'])->floatDiffInHours(Carbon::parse($hours_work['afternoonStart'])),
                    'user' => $user
                ];
                $periods[] = $period_available;
            } elseif (count($periods_unavailable['afternoon']) == 1) {

                // 1er cas : On regarde si la periode d'indispo est égale ou déborde de la période de travail
                if ($periods_unavailable['afternoon'][0]['start_time'] <= $hours_work['afternoonStart'] && $hours_work['afternoonEnd'] <= $periods_unavailable['afternoon'][0]['end_time']) {
                    $period_available = null;
                }

                // 2ème cas : On regarde si la période est strictement dans la periode de travail
                elseif ($periods_unavailable['afternoon'][0]['start_time'] > $hours_work['afternoonStart'] && $hours_work['afternoonEnd'] > $periods_unavailable['afternoon'][0]['end_time']) {

                    $period_available1 = [
                        'start_time' => $hours_work['afternoonStart'],
                        'end_time' => $periods_unavailable['afternoon'][0]['start_time'],
                        'hours' => Carbon::parse($periods_unavailable['afternoon'][0]['start_time'])->floatDiffInHours(Carbon::parse($hours_work['afternoonStart'])),
                        'user' => $user
                    ];

                    $period_available2 = [
                        'start_time' => $periods_unavailable['afternoon'][0]['end_time'],
                        'end_time' => $hours_work['afternoonEnd'],
                        'hours' => Carbon::parse($hours_work['afternoonEnd'])->floatDiffInHours(Carbon::parse($periods_unavailable['afternoon'][0]['end_time'])),
                        'user' => $user
                    ];

                    $periods[] = $period_available1;
                    $periods[] = $period_available2;
                } else {
                    $start_time = $periods_unavailable['afternoon'][0]['start_time'] > $hours_work['afternoonStart'] ? $periods_unavailable['afternoon'][0]['start_time'] : $hours_work['afternoonStart'];
                    $end_time = $hours_work['afternoonEnd'] > $periods_unavailable['afternoon'][0]['end_time'] ? $periods_unavailable['afternoon'][0]['end_time'] : $end_time = $hours_work['afternoonEnd'];
                    $period_available = [
                        'start_time' => $start_time,
                        'end_time' => $end_time,
                        'hours' => Carbon::parse($end_time)->floatDiffInHours(Carbon::parse($start_time)),
                        'user' => $user
                    ];

                    $periods[] = $period_available;
                }
            }
            // Si + de 1 indisponibilité
            else {
                //On regarde si le premier élément du tableau est supérieur a la l'heure d'embauche du matin
                if ($periods_unavailable['afternoon'][0]['start_time'] > $hours_work['afternoonStart']) {
                    $period_available = [
                        'start_time' => $hours_work['afternoonStart'],
                        'end_time' => $periods_unavailable['afternoon'][0]['start_time'],
                        'hours' => Carbon::parse($periods_unavailable['afternoon'][0]['start_time'])->floatDiffInHours(Carbon::parse($hours_work['afternoonStart'])),
                        'user' => $user
                    ];
                    $periods[] = $period_available;
                }

                foreach ($periods_unavailable['afternoon'] as $key => $afternoonPeriod) {
                    $start_time = $afternoonPeriod['end_time'];
                    $end_time = $periods_unavailable[$key + 1]  && $periods_unavailable[$key + 1]['start_time'] < $hours_work['afternoonEnd'] ? $periods_unavailable[$key + 1]['start_time'] : $hours_work['afternoonEnd'];
                    $period_available = [
                        'start_time' => $start_time,
                        'end_time' => $end_time,
                        'hours' => Carbon::parse($end_time)->floatDiffInHours(Carbon::parse($start_time)),
                        'user' => $user
                    ];

                    $periods[] = $period_available;
                }
            }
        }


        return $periods;
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
}
