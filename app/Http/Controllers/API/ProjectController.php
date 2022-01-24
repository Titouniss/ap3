<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Models\Project;
use App\User;
use App\Models\Range;
use App\Models\Task;
use App\Models\TasksSkill;
use App\Models\TaskPeriod;
use App\Models\Workarea;
use App\Models\WorkareasSkill;
use App\Models\PreviousTask;
use App\Models\TasksBundle;
use App\Models\WorkHours;
use App\Models\UsersSkill;
use App\Traits\StoresDocuments;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Carbon\CarbonPeriod;
use Exception;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;


class ProjectController extends BaseApiController
{
    use StoresDocuments;

    protected static $index_load = ['company:companies.id,name', 'customer:customers.id,name', 'documents', 'tasks'];
    protected static $index_append = null;
    protected static $show_load = ['company:companies.id,name', 'customer:customers.id,name', 'documents', 'tasksBundles', 'tasks'];
    protected static $show_append = null;

    protected static $store_validation_array = [
        'name' => 'required',
        'date' => 'required',
        'company_id' => 'required',
        'customer_id' => 'nullable',
        'color' => 'nullable',
        'token' => 'nullable'
    ];

    protected static $update_validation_array = [
        'name' => 'required',
        'date' => 'required',
        'company_id' => 'required',
        'customer_id' => 'nullable',
        'color' => 'nullable',
        'documents' => 'nullable|array',
        'token' => 'nullable'
    ];

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        parent::__construct(Project::class);
    }

    protected function filterIndexQuery(Request $request, $query)
    {
        $user = Auth::user();
        if ($user->is_admin && $request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('deleted_at')) {
            $query->onlyTrashed();
        }
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->has('month')) { 
            $query->whereMonth('created_at', '=', $request->month);
        }
        if ($request->has('year')) { 
            $query->whereYear('created_at', '=', $request->year);    
        }
        if ($request->has('order_by') && $request->order_by == 'status') {
            try {
                $query->getQuery()->orders = null;

                $direction = 'asc';
                if ($request->has('order_by_desc')) {
                    $direction = filter_var($request->order_by_desc, FILTER_VALIDATE_BOOLEAN) ? 'desc' : 'asc';
                }

                $query->orderByRaw("FIELD(status, \"doing\", \"todo\", \"waiting\", \"done\") {$direction}");
                $query->orderBy('name', $direction);
            } catch (\Throwable $th) {
                throw new ApiException("Paramètre 'order_by' n'est pas valide.");
            }
        }
    }

    protected function storeItem(array $arrayRequest)
    {
        $item = Project::create([
            'name' => $arrayRequest['name'],
            'date' => $arrayRequest['date'],
            'company_id' => $arrayRequest['company_id'],
            'created_by' => Auth::id()
        ]);

        if (isset($arrayRequest['customer_id'])) {
            $item->customer_id = $arrayRequest['customer_id'];
        }
        if (isset($arrayRequest['color'])) {
            $item->color = $arrayRequest['color'];
        }
        if (isset($arrayRequest['token'])) {
            $this->storeDocumentsByToken($item, $arrayRequest['token'], $item->company);
        }
        $item->save();

        $this->checkIfTaskBundleExist($item->id);

        return $item;
    }

    protected function updateItem($item, array $arrayRequest)
    {
        $item->update([
            'name' => $arrayRequest['name'],
            'date' => $arrayRequest['date'],
            'company_id' => $arrayRequest['company_id'],
            'status' => $arrayRequest['status'],
        ]);

        if (isset($arrayRequest['customer_id'])) {
            $item->customer_id = $arrayRequest['customer_id'];
        }
        if (isset($arrayRequest['color'])) {
            $item->color = $arrayRequest['color'];
        }
        $item->save();

        if (isset($arrayRequest['token'])) {
            $this->storeDocumentsByToken($item, $arrayRequest['token'], $item->company);
        }

        if (isset($arrayRequest['documents'])) {
            $this->deleteUnusedDocuments($item, $arrayRequest['documents']);
        }

        return $item;
    }

    protected function updateTaskPeriod(Request $request)
    {
        try {
            $taskPeriod = TaskPeriod::where('id', $request->id)->get();
            $task = Task::where('id', $taskPeriod[0]['task_id'])->get();
            $bundle_id = $task[0]['tasks_bundle_id'];
            $taskBundle = TasksBundle::where('id', $bundle_id)->get();
            $projectId = $taskBundle[0]['project_id'];
            //si l'utilisateur a coché la case pour déplacer les tâches dépendantes
            //si l'utilisateur n'a pas coché la case pour déplacer la date de livraison automatiquement
            //si la task_period doit être déplacée après la date originale
            if ($request->moveChecked == "true" && $request->moveDateEndChecked == "false" && ($request->start > $taskPeriod[0]['start_time'])) {
                //vérifier si l'ilot et l'utilisateur sont dispos sur la nouvelle période
                $arrayPeriodIndispo = $this->listDebutPeriodIndispo($taskPeriod, $task, "next");
                $listDebutTaskPeriodIndispo = $arrayPeriodIndispo["listDebutTaskPeriodIndispo"];
                $list = $arrayPeriodIndispo["list"];
                $listIdTaskDependant = $arrayPeriodIndispo["listIdTaskDependant"];
                $dateLivraison = $arrayPeriodIndispo["date"];

                $erreur = false;
                //on parcours toutes les task periods occupées et on regarde si les nouvelles dates de la tak period se superpose avec une task period occupée
                foreach ($list as $period) {
                    //si l'utilisateur veut déplacer la task period sur une autre task period qui utilise le même ilot ou le même utilisateur -> pas maj
                    if (($request->start <= $period['start_time'] && $request->end <= $period['end_time'] && $request->end > $period['start_time']) ||
                        ($request->start <= $period['start_time'] && $request->end >= $period['end_time']) ||
                        ($request->start >= $period['start_time'] && $request->end <= $period['end_time']) ||
                        ($request->start >= $period['start_time'] && $request->end >= $period['end_time'] && $request->start < $period['end_time'])
                    ) {

                        $erreur = true;
                        $task = Task::where('id', $taskPeriod[0]['task_id'])->with('workarea', 'skills', 'comments', 'previousTasks', 'documents', 'project', 'periods')->get();
                        // try {
                        //     return $this->successResponse($task[0], '');
                        // } catch (\Throwable $th) {
                        throw new ApiException("Vous ne pouvez pas déplacer la période ici car l'utilisateur ou le pôle de production n'est pas disponible.");
                        //}
                    }
                }

                //si on peut déplacer la task period à la nouvelle date car l'ilot et l'utilisateur sont dispos -> maj taskPeriod
                if (!$erreur) {
                    //algo pour déplacer et créer les tasks_period
                    $listTaskPeriodToMoveAndCreate = $this->moveAndCreateTaskPeriodAfter($request, $listIdTaskDependant, $listDebutTaskPeriodIndispo, $list);

                    $controllerLog = new Logger('hours');
                    $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
                    $controllerLog->info('listTaskPeriodToMoveAndCreate', [$listTaskPeriodToMoveAndCreate]);


                    if (count($listTaskPeriodToMoveAndCreate) == 0) {
                        $task = Task::where('id', $taskPeriod[0]['task_id'])->with('workarea', 'skills', 'comments', 'previousTasks', 'documents', 'project', 'periods')->get();
                        throw new ApiException("Il n'y a aucune période dépendante à déplacer.");
                    }

                    //si le tableau retourné contient "erreur horaires" on renvoie une erreur pour dire à l'utilisateur qu'il faut déplacer dans les heures de travail des utilisateurs
                    else if (end($listTaskPeriodToMoveAndCreate) == "erreur horaires") {
                        $task = Task::where('id', $taskPeriod[0]['task_id'])->with('workarea', 'skills', 'comments', 'previousTasks', 'documents', 'project', 'periods')->get();
                        throw new ApiException("La nouvelle date de fin n'est pas dans les heures de travail des utilisateurs.");
                    }

                    //on calcule la date de fin de la dernière task_period déplacée pour savoir si elle se trouve après la date de livraison

                    //si la dernière date est après la date de livraison, erreur
                    if (end($listTaskPeriodToMoveAndCreate["move"]) > $dateLivraison || ($listTaskPeriodToMoveAndCreate["create"] != null && end($listTaskPeriodToMoveAndCreate["create"]) > $dateLivraison)) {
                        throw new ApiException("La date de livraison est dépassée, il faut déplacer la date de livraison plus tard ou cocher la case pour changer automatiquement la date de livraison.");
                    }
                    //sinon on met à jour et on crées les tasks_period
                    else {

                        $this->mergeTaskPeriod($listTaskPeriodToMoveAndCreate);
                        //on met à jour la date de début et de fin de la task modifiée avec la date de début de la première task period et la date de fin de la dernière task_period de la task
                        $listTaskPeriodModified = TaskPeriod::where('task_id', $task[0]['id'])->get();
                        $firstTaskPeriod = $listTaskPeriodModified->first();
                        $dateDebut = $firstTaskPeriod['start_time'];
                        $dateFin = $firstTaskPeriod['end_time'];
                        foreach ($listTaskPeriodModified as $taskPeriodModified) {
                            if ($taskPeriodModified['start_time'] < $dateDebut) {
                                $dateDebut = $taskPeriodModified['start_time'];
                            }
                            if ($taskPeriodModified['end_time'] > $dateFin) {
                                $dateFin = $taskPeriodModified['end_time'];
                            }
                        }

                        Task::where('id', $task[0]['id'])->update([
                            'date' => $dateDebut,
                            'date_end' => $dateFin,
                        ]);

                        //on met à jour la date de début et de fin des tasks dépendantes s'il y en a
                        if ($listIdTaskDependant != null) {
                            for ($id = 0; $id < count($listIdTaskDependant); $id++) {
                                $firstTaskPeriodTaskDependant = TaskPeriod::where('task_id', $listIdTaskDependant[$id])->get();
                                $firstTaskPeriodTaskDependant = $firstTaskPeriodTaskDependant->first();

                                $dateDebut = $firstTaskPeriodTaskDependant['start_time'];
                                $dateFin = $firstTaskPeriodTaskDependant['end_time'];

                                $taskPeriodModified = TaskPeriod::where('task_id', $listIdTaskDependant[$id])->get();

                                for ($i = 0; $i < count($taskPeriodModified); $i++) {
                                    if ($taskPeriodModified[$i]['start_time'] < $dateDebut) {
                                        $dateDebut = $taskPeriodModified[$i]['start_time'];
                                    }
                                    if ($taskPeriodModified[$i]['end_time'] > $dateFin) {
                                        $dateFin = $taskPeriodModified[$i]['end_time'];
                                    }
                                }

                                Task::where('id', $listIdTaskDependant[$id])->update([
                                    'date' => $dateDebut,
                                    'date_end' => $dateFin,
                                ]);
                            }
                        }
                    }
                }
                $task = Task::where('id', $taskPeriod[0]['task_id'])->with('workarea', 'skills', 'comments', 'previousTasks', 'documents', 'project', 'periods')->get();
            }

            //si l'utilisateur a coché la case pour déplacer les tâches dépendantes
            //si l'utilisateur a coché la case pour déplacer la date de livraison automatiquement
            //si la task_period doit être déplacée après la date originale
            else if ($request->moveChecked == "true" && $request->moveDateEndChecked == "true" && ($request->start > $taskPeriod[0]['start_time'])) {
                //vérifier si l'ilot et l'utilisateur sont dispos sur la nouvelle période
                $arrayPeriodIndispo = $this->listDebutPeriodIndispo($taskPeriod, $task, "next");
                $listDebutTaskPeriodIndispo = $arrayPeriodIndispo["listDebutTaskPeriodIndispo"];
                $list = $arrayPeriodIndispo["list"];
                $listIdTaskDependant = $arrayPeriodIndispo["listIdTaskDependant"];
                $dateLivraison = $arrayPeriodIndispo["date"];

                $erreur = false;
                //on parcours toutes les task periods occupées et on regarde si les nouvelles dates de la tak period se superpose avec un task period occupée
                foreach ($list as $period) {
                    //si l'utilisateur veut déplacer la task period sur une autre task period qui utilise le même ilot ou le même utilisateur -> pas maj
                    if (($request->start <= $period['start_time'] && $request->end <= $period['end_time'] && $request->end > $period['start_time']) ||
                        ($request->start <= $period['start_time'] && $request->end >= $period['end_time']) ||
                        ($request->start >= $period['start_time'] && $request->end <= $period['end_time']) ||
                        ($request->start >= $period['start_time'] && $request->end >= $period['end_time'] && $request->start < $period['end_time'])
                    ) {

                        $erreur = true;
                        $task = Task::where('id', $taskPeriod[0]['task_id'])->with('workarea', 'skills', 'comments', 'previousTasks', 'documents', 'project', 'periods')->get();
                        // try {
                        //     return $this->successResponse($task[0], '');
                        // } catch (\Throwable $th) {
                        throw new ApiException("Vous ne pouvez pas déplacer la période ici car l'utilisateur ou le pôle de production n'est pas disponible.");
                        //}
                    }
                }

                //si on peut déplacer la task period à la nouvelle date car l'ilot et l'utilisateur sont dispos -> maj taskPeriod
                if (!$erreur) {
                    //algo pour déplacer et créer les tasks_period
                    $listTaskPeriodToMoveAndCreate = $this->moveAndCreateTaskPeriodAfter($request, $listIdTaskDependant, $listDebutTaskPeriodIndispo, $list);

                    if (count($listTaskPeriodToMoveAndCreate) == 0) {
                        $task = Task::where('id', $taskPeriod[0]['task_id'])->with('workarea', 'skills', 'comments', 'previousTasks', 'documents', 'project', 'periods')->get();
                        throw new ApiException("Il n'y a aucune période dépendante à déplacer.");
                    }

                    //si le tableau retourné contient on renvoie une erreur pour dire à l'utilisateur qu'il faut déplacer dans les heures de travail des utilisateurs
                    else if (end($listTaskPeriodToMoveAndCreate) == "erreur horaires") {
                        $task = Task::where('id', $taskPeriod[0]['task_id'])->with('workarea', 'skills', 'comments', 'previousTasks', 'documents', 'project', 'periods')->get();
                        throw new ApiException("La nouvelle date de fin n'est pas dans les heures de travail des utilisateurs.");
                    }

                    //si la dernière date des tasks_period déplacées ou créées est après la date de livraison, on met à jour la date de livraison
                    if (end($listTaskPeriodToMoveAndCreate["move"]) > $dateLivraison || ($listTaskPeriodToMoveAndCreate["create"] != null && end($listTaskPeriodToMoveAndCreate["create"]) > $dateLivraison)) {

                        if ($listTaskPeriodToMoveAndCreate["create"] != null) {
                            $newDateLivraison = end($listTaskPeriodToMoveAndCreate["move"]) >= end($listTaskPeriodToMoveAndCreate["create"]) ?
                                end($listTaskPeriodToMoveAndCreate["move"]) : end($listTaskPeriodToMoveAndCreate["create"]);
                        } else {
                            $newDateLivraison = end($listTaskPeriodToMoveAndCreate["move"]);
                        }

                        Project::where('id', $projectId)->update([
                            'date' => $newDateLivraison,
                        ]);
                    }
                    $this->mergeTaskPeriod($listTaskPeriodToMoveAndCreate);

                    //on met à jour la date de début et de fin de la task modifiée avec la date de début de la première task period et la date de fin de la dernière task_period de la task
                    $listTaskPeriodModified = TaskPeriod::where('task_id', $task[0]['id'])->get();
                    $firstTaskPeriod = $listTaskPeriodModified->first();
                    $dateDebut = $firstTaskPeriod['start_time'];
                    $dateFin = $firstTaskPeriod['end_time'];
                    foreach ($listTaskPeriodModified as $taskPeriodModified) {
                        if ($taskPeriodModified['start_time'] < $dateDebut) {
                            $dateDebut = $taskPeriodModified['start_time'];
                        }
                        if ($taskPeriodModified['end_time'] > $dateFin) {
                            $dateFin = $taskPeriodModified['end_time'];
                        }
                    }

                    Task::where('id', $task[0]['id'])->update([
                        'date' => $dateDebut,
                        'date_end' => $dateFin,
                    ]);

                    //on met à jour la date de début et de fin des tasks dépendantes s'il y en a
                    if ($listIdTaskDependant != null) {

                        for ($id = 0; $id < count($listIdTaskDependant); $id++) {
                            $firstTaskPeriodTaskDependant = TaskPeriod::where('task_id', $listIdTaskDependant[$id])->get();
                            $firstTaskPeriodTaskDependant = $firstTaskPeriodTaskDependant->first();

                            $dateDebut = $firstTaskPeriodTaskDependant['start_time'];
                            $dateFin = $firstTaskPeriodTaskDependant['end_time'];
                            $taskPeriodModified = TaskPeriod::where('task_id', $listIdTaskDependant[$id])->get();

                            for ($i = 0; $i < count($taskPeriodModified); $i++) {
                                if ($taskPeriodModified[$i]['start_time'] < $dateDebut) {
                                    $dateDebut = $taskPeriodModified[$i]['start_time'];
                                }
                                if ($taskPeriodModified[$i]['end_time'] > $dateFin) {
                                    $dateFin = $taskPeriodModified[$i]['end_time'];
                                }
                            }

                            Task::where('id', $listIdTaskDependant[$id])->update([
                                'date' => $dateDebut,
                                'date_end' => $dateFin,
                            ]);
                        }
                    }
                }
                $task = Task::where('id', $taskPeriod[0]['task_id'])->with('workarea', 'skills', 'comments', 'previousTasks', 'documents', 'project', 'periods')->get();
            } else if ($request->moveChecked == "true" && ($request->start < $taskPeriod[0]['start_time'])) {

                //vérifier si l'ilot et l'utilisateur sont dispos sur la nouvelle période
                $arrayPeriodIndispo = $this->listDebutPeriodIndispo($taskPeriod, $task, "before");
                $listDebutTaskPeriodIndispo = $arrayPeriodIndispo["listDebutTaskPeriodIndispo"];
                $list = $arrayPeriodIndispo["list"];
                $listIdTaskDependant = $arrayPeriodIndispo["listIdTaskDependant"];
                $dateDebutProjet = $arrayPeriodIndispo["date"];
                $dateDemain = Carbon::now()->addDays(1)->format("Y-m-d H:i:s");

                $erreur = false;
                //on parcours toutes les task periods occupées et on regarde si les nouvelles dates de la tak period se superpose avec un task period occupée
                foreach ($list as $period) {
                    //si l'utilisateur veut déplacer la task period sur une autre task period qui utilise le même ilot ou le même utilisateur -> pas maj
                    if (($request->start <= $period['start_time'] && $request->end <= $period['end_time'] && $request->end > $period['start_time']) ||
                        ($request->start <= $period['start_time'] && $request->end >= $period['end_time']) ||
                        ($request->start >= $period['start_time'] && $request->end <= $period['end_time']) ||
                        ($request->start >= $period['start_time'] && $request->end >= $period['end_time'] && $request->start < $period['end_time'])
                    ) {
                        $erreur = true;
                        $task = Task::where('id', $taskPeriod[0]['task_id'])->with('workarea', 'skills', 'comments', 'previousTasks', 'documents', 'project', 'periods')->get();
                        // try {
                        //     return $this->successResponse($task[0], '');
                        // } catch (\Throwable $th) {
                        throw new ApiException("Vous ne pouvez pas déplacer la période ici car l'utilisateur ou le pôle de production n'est pas disponible.");
                        //}
                    }
                }

                //si on peut déplacer la task period à la nouvelle date car l'ilot et l'utilisateur sont dispos -> maj taskPeriod
                if (!$erreur) {
                    //algo pour déplacer et créer les tasks_period
                    $listTaskPeriodToMoveAndCreate = $this->moveAndCreateTaskPeriodBefore($request, $listIdTaskDependant, $listDebutTaskPeriodIndispo, $list);

                    $controllerLog = new Logger('hours');
                    $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
                    $controllerLog->info('listTaskPeriodToMoveAndCreate', [$listTaskPeriodToMoveAndCreate]);

                    if (count($listTaskPeriodToMoveAndCreate) == 0) {
                        $task = Task::where('id', $taskPeriod[0]['task_id'])->with('workarea', 'skills', 'comments', 'previousTasks', 'documents', 'project', 'periods')->get();
                        throw new ApiException("Il n'y a aucune période dépendante à déplacer.");
                    }

                    //si le tableau retourné contient "erreur horaires" on renvoie une erreur pour dire à l'utilisateur qu'il faut déplacer dans les heures de travail des utilisateurs
                    else if (end($listTaskPeriodToMoveAndCreate) == "erreur horaires") {
                        $task = Task::where('id', $taskPeriod[0]['task_id'])->with('workarea', 'skills', 'comments', 'previousTasks', 'documents', 'project', 'periods')->get();
                        throw new ApiException("La nouvelle date de début n'est pas dans les heures de travail des utilisateurs.");
                    }
                    //si le tableau retourné contient "erreur horaires" on renvoie une erreur pour dire à l'utilisateur qu'il faut déplacer dans les heures de travail des utilisateurs
                    else if (end($listTaskPeriodToMoveAndCreate) == "avant aujourd'hui") {
                        $task = Task::where('id', $taskPeriod[0]['task_id'])->with('workarea', 'skills', 'comments', 'previousTasks', 'documents', 'project', 'periods')->get();
                        throw new ApiException("Impossible de déplacer les périodes avant aujourd'hui.");
                    }

                    //si la première date dans le temps des tasks_period déplacées ou créées est avant demain -> erreur
                    if (
                        end($listTaskPeriodToMoveAndCreate["move"]) < $dateDemain ||
                        ($listTaskPeriodToMoveAndCreate["create"] != null && end($listTaskPeriodToMoveAndCreate["create"]) < $dateDemain)
                    ) {
                        throw new ApiException("Les périodes ne peuvent pas être déplacées avant demain.");
                    }

                    //si l'utilisateur n'a pas coché la case pour avancer la date de début de projet
                    //et que la première date dans le temps des tasks_period déplacées ou créées est avant la date de début de projet -> erreur
                    else if (
                        $request->moveDateStartChecked == "false" &&
                        ((end($listTaskPeriodToMoveAndCreate["move"]) < $dateDebutProjet) ||
                            ($listTaskPeriodToMoveAndCreate["create"] != null && end($listTaskPeriodToMoveAndCreate["create"]) < $dateDebutProjet))
                    ) {
                        throw new ApiException("Toutes les périodes ne peuvent pas être déplacées après la date de début du projet. Vous pouvez cocher la case pour déplacer automatiquement la date de début du projet.");
                    }

                    //si l'utilisateur a accepté d'avancer la date de début du projet, on met à jour le champs date_end dans la bdd
                    else /*if($request->moveDateStartChecked == "true")*/ {
                        //si le tableau retourné est vide, on renvoie une erreur pour dire à l'utilisateur qu'il n'y a pas d'autres tasks_period liées

                        //si la première date des tasks_period déplacées ou créées est avant la date de commencement du projet, on met à jour la date de commencement du projet
                        if (end($listTaskPeriodToMoveAndCreate["move"]) < $dateDebutProjet || ($listTaskPeriodToMoveAndCreate["create"] != null && end($listTaskPeriodToMoveAndCreate["create"]) < $dateDebutProjet)) {

                            if ($listTaskPeriodToMoveAndCreate["create"] != null) {
                                $newDateProjet = end($listTaskPeriodToMoveAndCreate["move"]) <= end($listTaskPeriodToMoveAndCreate["create"]) ?
                                    end($listTaskPeriodToMoveAndCreate["move"]) : end($listTaskPeriodToMoveAndCreate["create"]);
                            } else {
                                $newDateProjet = end($listTaskPeriodToMoveAndCreate["move"]);
                            }

                            Project::where('id', $projectId)->update([
                                'start_date' => $newDateProjet,
                            ]);
                        }
                    }

                    $this->mergeTaskPeriod($listTaskPeriodToMoveAndCreate);

                    //on met à jour la date de début et de fin de la task modifiée avec la date de début de la première task period et la date de fin de la dernière task_period de la task
                    $listTaskPeriodModified = TaskPeriod::where('task_id', $task[0]['id'])->get();
                    $firstTaskPeriod = $listTaskPeriodModified->first();
                    $dateDebut = $firstTaskPeriod['start_time'];
                    $dateFin = $firstTaskPeriod['end_time'];
                    foreach ($listTaskPeriodModified as $taskPeriodModified) {
                        if ($taskPeriodModified['start_time'] < $dateDebut) {
                            $dateDebut = $taskPeriodModified['start_time'];
                        }
                        if ($taskPeriodModified['end_time'] > $dateFin) {
                            $dateFin = $taskPeriodModified['end_time'];
                        }
                    }

                    Task::where('id', $task[0]['id'])->update([
                        'date' => $dateDebut,
                        'date_end' => $dateFin,
                    ]);

                    //on met à jour la date de début et de fin des tasks dépendantes s'il y en a
                    if ($listIdTaskDependant != null) {

                        for ($id = 0; $id < count($listIdTaskDependant); $id++) {
                            $firstTaskPeriodTaskDependant = TaskPeriod::where('task_id', $listIdTaskDependant[$id])->get();
                            $firstTaskPeriodTaskDependant = $firstTaskPeriodTaskDependant->first();

                            $dateDebut = $firstTaskPeriodTaskDependant['start_time'];
                            $dateFin = $firstTaskPeriodTaskDependant['end_time'];
                            $taskPeriodModified = TaskPeriod::where('task_id', $listIdTaskDependant[$id])->get();

                            for ($i = 0; $i < count($taskPeriodModified); $i++) {
                                if ($taskPeriodModified[$i]['start_time'] < $dateDebut) {
                                    $dateDebut = $taskPeriodModified[$i]['start_time'];
                                }
                                if ($taskPeriodModified[$i]['end_time'] > $dateFin) {
                                    $dateFin = $taskPeriodModified[$i]['end_time'];
                                }
                            }

                            Task::where('id', $listIdTaskDependant[$id])->update([
                                'date' => $dateDebut,
                                'date_end' => $dateFin,
                            ]);
                        }
                    }
                }
                $task = Task::where('id', $taskPeriod[0]['task_id'])->with('workarea', 'skills', 'comments', 'previousTasks', 'documents', 'project', 'periods')->get();
            } else {
                $projectUpdated = Project::where('id', $projectId)->get();
                //si avant la date de début du projet ou après la date de livraison -> erreur
                if ($request->start < $projectUpdated[0]['start_date']) {
                    throw new ApiException("Vous ne pouvez pas déplacer la période avant la date de début du projet.");
                } else if ($request->end > $projectUpdated[0]['date']) {
                    throw new ApiException("Vous ne pouvez pas déplacer la période après la date de livraison. Veuillez reculer la date de livraison.");
                }
                //sinon mettre à jour la task_period et la task
                else {
                    $item = TaskPeriod::where('id', $request->id);
                    TaskPeriod::where('id', $request->id)->update([
                        'start_time' => $request->start,
                        'end_time' => $request->end,
                    ]);
                    $tasksPeriod = TaskPeriod::where('task_id', $taskPeriod[0]['task_id'])->orderBy('start_time', 'asc')->get();
                    Task::where('id', $taskPeriod[0]['task_id'])->update([
                        'date' => $tasksPeriod[0]['start_time'],
                        'date_end' => $tasksPeriod[sizeof($tasksPeriod) - 1]['end_time'],
                    ]);
                    $task = Task::where('id', $taskPeriod[0]['task_id'])->with('workarea', 'skills', 'comments', 'previousTasks', 'documents', 'project', 'periods')->get();
                }
            }
            return $this->successResponse($task[0], 'Chargement terminé avec succès.');
        } catch (ApiException $th) {
            return $this->errorResponse($th->getMessage(), $th->getHttpCode());
        } catch (\Throwable $th) {
            return $this->errorResponse($th, static::$response_codes['error_server']);
        }
    }

    private function moveAndCreateTaskPeriodBefore(Request $request, array $listIdTaskDependant, array $listDebutTaskPeriodIndispo, array $list)
    {
        $taskPeriod = TaskPeriod::where('id', $request->id)->get();
        $listTaskPeriodToSave = array();
        $listTaskPeriodToCreate = array();
        $listTaskPeriodToDelete = array();
        $taskPeriodOrigine = TaskPeriod::where('id', $request->id)->get();

        array_push($listTaskPeriodToSave, $request->id);
        array_push($listTaskPeriodToSave, $request->start);
        array_push($listTaskPeriodToSave, $request->end);

        $task = Task::where('id', $taskPeriod[0]['task_id'])->with('workarea', 'skills', 'comments', 'previousTasks', 'documents', 'project', 'periods')->get();

        //s'il y a des tasks dépendantes à faire avant, ajouter les tasks_period des tasks dépendantes dans la liste des tasks_period à déplacer
        $listTaskPeriodToMove = array();
        if ($listIdTaskDependant != null) {
            $listTaskPeriodDependantToMove = array();
            foreach ($listIdTaskDependant as $Id) {
                $tasksPeriodDependantTask = TaskPeriod::where('task_id', $Id)->get();
                foreach ($tasksPeriodDependantTask as $taskPeriodDependantTask) {
                    array_push($listTaskPeriodDependantToMove, $taskPeriodDependantTask);
                    array_push($listTaskPeriodToMove, $taskPeriodDependantTask);
                }
            }
        }

        //s'il y a des tasks_periods de la même task avant, déplacer les dernières task_periods de la task où l'utilisateur et le workarea de la task sont dispos
        $tasksPeriodTask = TaskPeriod::where('task_id', $taskPeriod[0]['task_id'])->get();

        foreach ($tasksPeriodTask as $taskPeriodTask) {
            if (
                $taskPeriodTask['end_time'] <= $taskPeriodOrigine[0]['start_time'] && $taskPeriodTask['start_time'] <= $taskPeriodOrigine[0]['start_time']
                && $taskPeriodTask['id'] != $taskPeriodOrigine[0]['id']
            ) {
                array_push($listTaskPeriodToMove, $taskPeriodTask);
            }
        }

        if (count($listTaskPeriodToMove) == 0) {
            return $listTaskPeriodToMove;
        }

        $listUserId = array();
        array_push($listUserId, $task[0]['user_id']);

        $workHours = $this->workHoursUsers($listUserId);

        $newPeriod = CarbonPeriod::create(Carbon::now()/*Carbon::createFromFormat('Y-m-d H:i:s', "2020-01-01 08:00:00")*/, Carbon::createFromFormat('Y-m-d H:i', $request->start));

        if ($newPeriod == null) {
            array_push($listTaskPeriodToSave, "avant aujourd'hui");
            return $listTaskPeriodToSave;
        }

        $heureFinNewPeriod = Carbon::createFromFormat('Y-m-d H:i:s', $newPeriod->getEndDate())->format('H:i');

        $heureDebutTaskPrecedente = $request->start;

        $controllerLog = new Logger('hours');
        $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
        $controllerLog->info('listTaskPeriodToMove', [$listTaskPeriodToMove]);

        $taskPeriodToMove = array_pop($listTaskPeriodToMove);
        $taskIdTaskPeriodToMove = $taskPeriodToMove['task_id'];

        $controllerLog = new Logger('hours');
        $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
        $controllerLog->info('listDebutTaskPeriodIndispo', [$listDebutTaskPeriodIndispo]);

        for ($i = 0; $i < $newPeriod->count(); $i++) {
            $controllerLog = new Logger('hours');
            $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
            $controllerLog->info('$taskPeriodToMove[id]', [$taskPeriodToMove['id']]);

            $controllerLog = new Logger('hours');
            $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
            $controllerLog->info('listTaskPeriodToSave', [$listTaskPeriodToSave]);

            $controllerLog = new Logger('hours');
            $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
            $controllerLog->info('listTaskPeriodToDelete', [$listTaskPeriodToDelete]);

            if ((in_array($taskPeriodToMove['id'], $listTaskPeriodToSave)) || (in_array($taskPeriodToMove['id'], $listTaskPeriodToDelete))) {
                break;
            }
            $dateP = Carbon::createFromFormat('Y-m-d H:i:s', $newPeriod->getEndDate())->subDays($i)->format('Y-m-d');

            $controllerLog = new Logger('hours');
            $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
            $controllerLog->info('dateP', [$dateP]);

            $p = Carbon::createFromFormat('Y-m-d H:i:s', $newPeriod->getEndDate())->subDays($i);

            $dayName = Carbon::create($dateP)->dayName;
            $hoursWork = $workHours[$dayName];

            //s'il n'y a pas d'heures de travail pour la journée on passe un tour de boucle pour ne pas ajouter des tasks_period sur les jours où il n'y a pas de travail
            if (
                ($hoursWork[0] == "00:00:00" || $hoursWork[0] == null) && ($hoursWork[1] == "00:00:00" || $hoursWork[1] == null) &&
                ($hoursWork[2] == "00:00:00" || $hoursWork[2] == null) && ($hoursWork[3] == "00:00:00" || $hoursWork[3] == null)
            ) {
                continue;
            }

            $controllerLog = new Logger('hours');
            $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
            $controllerLog->info('hoursWork', [$hoursWork]);

            $dureePeriodDispoMatin = Carbon::parse($hoursWork[0])->floatDiffInHours(Carbon::parse($hoursWork[1]));
            $dureePeriodDispoApresmidi = Carbon::parse($hoursWork[2])->floatDiffInHours(Carbon::parse($hoursWork[3]));

            $heureDebutTravailMatin = Carbon::createFromFormat('H:i:s', $hoursWork[0])->format('H:i');
            $heureFinTravailMatin = Carbon::createFromFormat('H:i:s', $hoursWork[1])->format('H:i');
            $heureDebutTravailApresMidi = Carbon::createFromFormat('H:i:s', $hoursWork[2])->format('H:i');
            $heureFinTravailApresMidi = Carbon::createFromFormat('H:i:s', $hoursWork[3])->format('H:i');

            // $heuresDisposMatin=Carbon::parse($heureDebutTravailMatin)->floatDiffInHours($heureFinNewPeriod);
            // if($heureFinNewPeriod<=$heureDebutTravailApresMidi){
            //     $heuresDisposApresMidi=Carbon::parse($heureFinTravailApresMidi)->floatDiffInHours($heureDebutTravailApresMidi);
            // }
            // else{
            //     $heuresDisposApresMidi=Carbon::parse($heureDebutTravailApresMidi)->floatDiffInHours($heureFinNewPeriod);
            // }

            $heuresDisposApresMidi = Carbon::parse($heureDebutTravailApresMidi)->floatDiffInHours($heureFinNewPeriod);
            // if($hoursWork[2] == "00:00:00" && $hoursWork[3] == "00:00:00"){
            //     $heuresDisposApresMidi=0;
            // }
            // else{
            //     $heuresDisposApresMidi = Carbon::parse($heureDebutTravailApresMidi)->floatDiffInHours($heureFinNewPeriod);
            // }
            if ($heureFinNewPeriod > $heureFinTravailMatin) {
                $heuresDisposMatin = Carbon::parse($heureFinTravailMatin)->floatDiffInHours($heureDebutTravailMatin);
            } else {
                $heuresDisposMatin = Carbon::parse($heureDebutTravailMatin)->floatDiffInHours($heureFinNewPeriod);
            }
            $controllerLog = new Logger('hours');
            $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
            $controllerLog->info('heuresDisposApresMidi', [$heuresDisposApresMidi]);

            //s'il n'y a pas d'indispo pour ce jour de la période (ni matin ni après-midi)
            if (!in_array($dateP, $listDebutTaskPeriodIndispo)) {
                // if(count($listTaskPeriodToMove)==0){
                //     break;
                // }
                //$taskPeriodToMove=array_pop($listTaskPeriodToMove);
                $controllerLog = new Logger('hours');
                $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
                $controllerLog->info('ok', ['ok']);

                $controllerLog = new Logger('hours');
                $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
                $controllerLog->info('taskPeriodToMove', [$taskPeriodToMove]);

                $dureePeriodToMove = Carbon::parse($taskPeriodToMove['end_time'])->floatDiffInHours(Carbon::parse($taskPeriodToMove['start_time']));

                $controllerLog = new Logger('hours');
                $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
                $controllerLog->info('dureePeriodToMove', [$dureePeriodToMove]);

                $heureDebutPeriodMatin = Carbon::parse($hoursWork[0])->floatDiffInHours(Carbon::parse("00:00:00"));
                $heureFinPeriodMatin = Carbon::parse($hoursWork[1])->floatDiffInHours(Carbon::parse("00:00:00"));
                $heureDebutPeriodApresMidi = Carbon::parse($hoursWork[2])->floatDiffInHours(Carbon::parse("00:00:00"));
                $heureFinPeriodApresMidi = Carbon::parse($hoursWork[3])->floatDiffInHours(Carbon::parse("00:00:00"));

                $controllerLog = new Logger('hours');
                $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
                $controllerLog->info('heureDebutPeriodApresMidi', [$heureDebutPeriodApresMidi]);

                $controllerLog = new Logger('hours');
                $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
                $controllerLog->info('heureFinNewPeriod', [$heureFinNewPeriod]);

                //si la fin de la nouvelle période est compris dans les heures de travail du matin
                // if (($heureFinNewPeriod >= $heureDebutTravailMatin && $heureFinNewPeriod <= $heureDebutTravailApresMidi && ($hoursWork[2] != "00:00:00" || $hoursWork[3] != "00:00:00")) ||
                //     ($heureFinNewPeriod >= $heureDebutTravailMatin && $heureFinNewPeriod <= $heureFinTravailMatin && ($hoursWork[2] == "00:00:00" && $hoursWork[3] == "00:00:00"))) {
                if ($heureFinNewPeriod >= $heureDebutTravailMatin && $heureFinNewPeriod <= $heureDebutTravailApresMidi) {
                    $controllerLog = new Logger('hours');
                    $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
                    $controllerLog->info('ok', ['matin']);

                    // if($hoursWork[2] == "00:00:00" && $hoursWork[3] == "00:00:00"){
                    //     if ($heureFinNewPeriod >= $heureFinTravailMatin) {
                    //         $heuresDisposMatin = Carbon::parse($heureFinTravailMatin)->floatDiffInHours($heureDebutTravailMatin);
                    //     } else {
                    //         $heuresDisposMatin = Carbon::parse($heureDebutTravailMatin)->floatDiffInHours($heureFinNewPeriod);
                    //     }
                    // }else if($hoursWork[2] != "00:00:00" || $hoursWork[3] != "00:00:00"){
                    if ($heureFinNewPeriod > $heureFinTravailMatin) {
                        $heuresDisposMatin = Carbon::parse($heureFinTravailMatin)->floatDiffInHours($heureDebutTravailMatin);
                    } else {
                        $heuresDisposMatin = Carbon::parse($heureDebutTravailMatin)->floatDiffInHours($heureFinNewPeriod);
                    }
                    //}

                    $controllerLog = new Logger('hours');
                    $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
                    $controllerLog->info('heuresDisposMatin ap', [$heuresDisposMatin]);

                    $finJourVeille = Carbon::parse($p)->endOfDay()->subDays(1)->format("Y-m-d H:i:s");

                    while (($heuresDisposMatin >= 0) && ($heureDebutTaskPrecedente >= $finJourVeille)) :
                        // while ((($heuresDisposMatin >= 0) && (($hoursWork[2] != "00:00:00" || $hoursWork[3] != "00:00:00") && ($heureDebutTaskPrecedente >= $finJourVeille))) ||
                        //         (($heuresDisposMatin >= 0 || ($heureDebutTaskPrecedente > $finJourVeille)) && (($hoursWork[2] == "00:00:00" || $hoursWork[3] == "00:00:00")))) :

                        $controllerLog = new Logger('hours');
                        $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
                        $controllerLog->info('heuresDisposMatin av', [$heuresDisposMatin]);

                        $controllerLog = new Logger('hours');
                        $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
                        $controllerLog->info('dureePeriodToMove', [$dureePeriodToMove]);

                        $controllerLog = new Logger('hours');
                        $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
                        $controllerLog->info('heureDebutTaskPrecedente', [$heureDebutTaskPrecedente]);


                        //s'il y a assez de temps pour déplacer la task_period entièrement le matin on la déplace dans la période du matin
                        if ($heuresDisposMatin >= $dureePeriodToMove) {

                            $arrayInfos = $this->moveBeforeEntireTaskMorning($p, $taskPeriodToMove, $workHours, $listTaskPeriodToSave, $listTaskPeriodToCreate, $listTaskPeriodToDelete, $dureePeriodToMove, $heureFinNewPeriod, $heuresDisposMatin, $heureDebutPeriodMatin, $heureFinPeriodMatin, $heureFinPeriodApresMidi);

                            //$heuresDisposMatin=$arrayInfos["heuresDisposMatin"];
                            $heureDebutTaskPrecedente = $arrayInfos["heureDebutTaskPrecedente"];

                            $controllerLog = new Logger('hours');
                            $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
                            $controllerLog->info('listTaskPeriodToMove', [$listTaskPeriodToMove]);

                            $newListTaskPeriod = $this->addInlistTaskPeriodToMoveAndCreate($arrayInfos, $listTaskPeriodToSave, $listTaskPeriodToCreate, $listTaskPeriodToDelete);
                            $listTaskPeriodToSave = $newListTaskPeriod["move"];
                            $listTaskPeriodToCreate = $newListTaskPeriod["create"];
                            $listTaskPeriodToDelete = $newListTaskPeriod["delete"];

                            // TaskPeriod::where('id',$taskPeriodToMove['id'])->update([
                            //     'start_time' => Carbon::parse($p)->startOfDay()->addHours($hoursFinPeriod)->addMinutes($minutesFinPeriod)->format('Y-m-d H:i:s'),
                            //     'end_time' =>Carbon::parse($p)->startOfDay()->addHours($heureFinPeriodInt)->addHours($dureePeriodToMove)->format('Y-m-d H:i:s'),
                            // ]);
                            //s'il n'y a plus de task_period on sort de la boucle sinon on la déplace le matin car il n'y a pas d'indispo pour la journée
                            if (count($listTaskPeriodToMove) == 0) {
                                break;
                            }

                            $taskPeriodToMove = array_pop($listTaskPeriodToMove);

                            $arrayworkHoursTask = $this->workHoursTask($taskIdTaskPeriodToMove, $taskPeriodToMove, $listUserId, $dayName, $heureFinNewPeriod, "before");
                            $taskIdTaskPeriodToMove = $arrayworkHoursTask["taskIdTaskPeriodToMove"];
                            $workHours = $arrayworkHoursTask["workHours"];
                            $listUserId = $arrayworkHoursTask["listUserId"];
                            $heureDebutTravailMatin = $arrayworkHoursTask["heureDebutTravailMatin"];
                            $heureFinTravailMatin = $arrayworkHoursTask["heureFinTravailMatin"];
                            $heureDebutTravailApresMidi = $arrayworkHoursTask["heureDebutTravailApresMidi"];
                            $heureFinTravailApresMidi = $arrayworkHoursTask["heureFinTravailApresMidi"];
                            $heuresDisposMatin = $arrayworkHoursTask["heuresDisposMatin"] - $arrayInfos["dureePeriodToMoveMatin"];
                            $heuresDisposApresMidi = $arrayworkHoursTask["heuresDisposApresMidi"];
                            $heureDebutPeriodMatin = $arrayworkHoursTask["heureDebutPeriodMatin"];
                            $heureFinPeriodMatin = $arrayworkHoursTask["heureFinPeriodMatin"];
                            $heureDebutPeriodApresMidi = $arrayworkHoursTask["heureDebutPeriodApresMidi"];
                            $heureFinPeriodApresMidi = $arrayworkHoursTask["heureFinPeriodApresMidi"];

                            $heureFinNewPeriod = Carbon::parse($heureDebutTaskPrecedente)->format('H:i');
                            $dureePeriodToMove = Carbon::parse($taskPeriodToMove['end_time'])->floatDiffInHours(Carbon::parse($taskPeriodToMove['start_time']));
                        }

                        //s'il n'y a pas assez de temps pour déplacer la task_period entièrement le matin, on la déplace dans la période pour remplir s'il reste du temps le matin et on crée une nouvelle task_period avec le temps restant l'après-midi' veille
                        else {

                            $arrayInfos = $this->moveBeforeTaskMorningCreateTaskAfternoonBefore($p, $listDebutTaskPeriodIndispo, $taskPeriodToMove, $workHours, $listTaskPeriodToSave, $listTaskPeriodToCreate, $listTaskPeriodToDelete, $heureFinNewPeriod, $dureePeriodToMove, $heuresDisposMatin, $heuresDisposApresMidi, $heureDebutPeriodMatin, $heureFinPeriodMatin, $heureFinTravailMatin, $heureDebutPeriodApresMidi, $heureFinPeriodApresMidi, $hoursWork);

                            //$heuresDisposMatin=$arrayInfos["heuresDisposMatin"];
                            //$heuresDisposApresMidi=$arrayInfos["heuresDisposApresMidi"];
                            $heureDebutTaskPrecedente = $arrayInfos["heureDebutTaskPrecedente"];

                            $newListTaskPeriod = $this->addInlistTaskPeriodToMoveAndCreate($arrayInfos, $listTaskPeriodToSave, $listTaskPeriodToCreate, $listTaskPeriodToDelete);
                            $listTaskPeriodToSave = $newListTaskPeriod["move"];
                            $listTaskPeriodToCreate = $newListTaskPeriod["create"];
                            $listTaskPeriodToDelete = $newListTaskPeriod["delete"];

                            $controllerLog = new Logger('hours');
                            $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
                            $controllerLog->info('listTaskPeriodToMove ap', [$listTaskPeriodToMove]);

                            //s'il n'y a plus de task_period on sort de la boucle sinon on la déplace le matin car il n'y a pas d'indispo pour la journée
                            if (count($listTaskPeriodToMove) == 0) {
                                break;
                            }
                            $taskPeriodToMove = array_pop($listTaskPeriodToMove);

                            $arrayworkHoursTask = $this->workHoursTask($taskIdTaskPeriodToMove, $taskPeriodToMove, $listUserId, $dayName, $heureFinNewPeriod, "before");
                            $taskIdTaskPeriodToMove = $arrayworkHoursTask["taskIdTaskPeriodToMove"];
                            $workHours = $arrayworkHoursTask["workHours"];
                            $listUserId = $arrayworkHoursTask["listUserId"];
                            $heureDebutTravailMatin = $arrayworkHoursTask["heureDebutTravailMatin"];
                            $heureFinTravailMatin = $arrayworkHoursTask["heureFinTravailMatin"];
                            $heureDebutTravailApresMidi = $arrayworkHoursTask["heureDebutTravailApresMidi"];
                            $heureFinTravailApresMidi = $arrayworkHoursTask["heureFinTravailApresMidi"];
                            $heuresDisposMatin = $arrayworkHoursTask["heuresDisposMatin"] - $arrayInfos["dureePeriodToMoveMatin"];
                            $heuresDisposApresMidi = $arrayworkHoursTask["heuresDisposApresMidi"] - $arrayInfos["dureePeriodToMoveApresMidi"];
                            $heureDebutPeriodMatin = $arrayworkHoursTask["heureDebutPeriodMatin"];
                            $heureFinPeriodMatin = $arrayworkHoursTask["heureFinPeriodMatin"];
                            $heureDebutPeriodApresMidi = $arrayworkHoursTask["heureDebutPeriodApresMidi"];
                            $heureFinPeriodApresMidi = $arrayworkHoursTask["heureFinPeriodApresMidi"];

                            $heureFinNewPeriod = Carbon::parse($heureDebutTaskPrecedente)->format('H:i');
                            $dureePeriodToMove = Carbon::parse($taskPeriodToMove['end_time'])->floatDiffInHours(Carbon::parse($taskPeriodToMove['start_time']));
                        }
                    endwhile;


                    //s'il n'y a plus de temps disponible le matin on passe un tour pour aller au jour d'avant
                    if ($heuresDisposMatin == 0) {
                        $controllerLog = new Logger('hours');
                        $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
                        $controllerLog->info('heureDebutTaskPrecedente ap', [$heureDebutTaskPrecedente]);

                        //$heureFinNewPeriod = Carbon::createFromFormat('Y-m-d H:i:s', $heureDebutTaskPrecedente)->format('H:i');
                        $heureFinNewPeriod = Carbon::parse($heureDebutTaskPrecedente)->format('H:i');
                        //continue;
                    }
                }

                //si la fin de la nouvelle période est compris dans les heures de travail de l'après midi
                else if ($heureFinNewPeriod > $heureDebutTravailApresMidi && $heureFinNewPeriod <= $heureFinTravailApresMidi) {

                    // $arrayworkHoursTask=$this->workHoursTask($taskIdTaskPeriodToMove, $taskPeriodToMove, $listUserId, $dayName, $heureFinNewPeriod, "before");
                    // $heuresDisposMatin=$arrayworkHoursTask["heuresDisposMatin"];
                    // $heuresDisposApresMidi=$arrayworkHoursTask["heuresDisposApresMidi"];

                    $heureFinMatin = Carbon::parse($hoursWork[2])->floatDiffInHours(Carbon::parse("00:00:00"));

                    $finMatineeJourCourant = Carbon::parse($p)->startOfDay()->addHours($heureFinMatin)->format("Y-m-d H:i:s");

                    while (($heuresDisposApresMidi >= 0) && ($heureDebutTaskPrecedente > $finMatineeJourCourant)) :
                        //s'il y a assez de temps pour déplacer la task_period entièrement l'après-midi on la déplace dans la période de l'après-midi
                        if ($heuresDisposApresMidi >= $dureePeriodToMove) {
                            $arrayInfos = $this->moveBeforeEntireTaskAfternoon($p, $taskPeriodToMove, $workHours, $listTaskPeriodToSave, $listTaskPeriodToCreate, $listTaskPeriodToDelete, $dureePeriodToMove, $heureFinNewPeriod, $heuresDisposApresMidi, $heureDebutPeriodMatin, $heureFinPeriodMatin, $heureFinPeriodApresMidi);

                            //$heuresDisposApresMidi=$arrayInfos["heuresDisposApresMidi"];
                            $heureDebutTaskPrecedente = $arrayInfos["heureDebutTaskPrecedente"];

                            $newListTaskPeriod = $this->addInlistTaskPeriodToMoveAndCreate($arrayInfos, $listTaskPeriodToSave, $listTaskPeriodToCreate, $listTaskPeriodToDelete);
                            $listTaskPeriodToSave = $newListTaskPeriod["move"];
                            $listTaskPeriodToCreate = $newListTaskPeriod["create"];
                            $listTaskPeriodToDelete = $newListTaskPeriod["delete"];
                            //s'il n'y a plus de task_period on sort de la boucle sinon on la déplace le matin car il n'y a pas d'indispo pour la journée
                            if (count($listTaskPeriodToMove) == 0) {
                                break;
                            }
                            $taskPeriodToMove = array_pop($listTaskPeriodToMove);

                            $arrayworkHoursTask = $this->workHoursTask($taskIdTaskPeriodToMove, $taskPeriodToMove, $listUserId, $dayName, $heureFinNewPeriod, "before");
                            $taskIdTaskPeriodToMove = $arrayworkHoursTask["taskIdTaskPeriodToMove"];
                            $workHours = $arrayworkHoursTask["workHours"];
                            $listUserId = $arrayworkHoursTask["listUserId"];
                            $heureDebutTravailMatin = $arrayworkHoursTask["heureDebutTravailMatin"];
                            $heureFinTravailMatin = $arrayworkHoursTask["heureFinTravailMatin"];
                            $heureDebutTravailApresMidi = $arrayworkHoursTask["heureDebutTravailApresMidi"];
                            $heureFinTravailApresMidi = $arrayworkHoursTask["heureFinTravailApresMidi"];
                            $heuresDisposMatin = $arrayworkHoursTask["heuresDisposMatin"];
                            $heuresDisposApresMidi = $arrayworkHoursTask["heuresDisposApresMidi"] - $arrayInfos["dureePeriodToMoveApresMidi"];
                            $heureDebutPeriodMatin = $arrayworkHoursTask["heureDebutPeriodMatin"];
                            $heureFinPeriodMatin = $arrayworkHoursTask["heureFinPeriodMatin"];
                            $heureDebutPeriodApresMidi = $arrayworkHoursTask["heureDebutPeriodApresMidi"];
                            $heureFinPeriodApresMidi = $arrayworkHoursTask["heureFinPeriodApresMidi"];

                            $heureFinNewPeriod = Carbon::parse($heureDebutTaskPrecedente)->format('H:i');
                            $dureePeriodToMove = Carbon::parse($taskPeriodToMove['end_time'])->floatDiffInHours(Carbon::parse($taskPeriodToMove['start_time']));
                        }
                        //s'il n'y a pas assez de temps pour déplacer la task_period entièrement l'apres-midi, on la déplace dans la période pour remplir s'il reste du temps
                        //et on créée une nouvelle task_period avec le temps restant le matin
                        else {

                            $arrayInfos = $this->moveBeforeTaskAfternoonCreateTaskMorning($p, $listDebutTaskPeriodIndispo, $taskPeriodToMove, $workHours, $listTaskPeriodToSave, $listTaskPeriodToCreate, $listTaskPeriodToDelete, $heureFinNewPeriod, $dureePeriodToMove, $heuresDisposApresMidi, $heureDebutPeriodMatin, $heureFinPeriodMatin, $heureDebutPeriodApresMidi, $heureFinPeriodApresMidi, $hoursWork);

                            //$heuresDisposApresMidi=$arrayInfos["heuresDisposApresMidi"];
                            $heureDebutTaskPrecedente = $arrayInfos["heureDebutTaskPrecedente"];

                            $newListTaskPeriod = $this->addInlistTaskPeriodToMoveAndCreate($arrayInfos, $listTaskPeriodToSave, $listTaskPeriodToCreate, $listTaskPeriodToDelete);
                            $listTaskPeriodToSave = $newListTaskPeriod["move"];
                            $listTaskPeriodToCreate = $newListTaskPeriod["create"];
                            $listTaskPeriodToDelete = $newListTaskPeriod["delete"];
                            //s'il n'y a plus de task_period on sort de la boucle sinon on la déplace le matin car il n'y a pas d'indispo pour la journée
                            if (count($listTaskPeriodToMove) == 0) {
                                break;
                            }
                            $taskPeriodToMove = array_pop($listTaskPeriodToMove);

                            $arrayworkHoursTask = $this->workHoursTask($taskIdTaskPeriodToMove, $taskPeriodToMove, $listUserId, $dayName, $heureFinNewPeriod, "before");
                            $taskIdTaskPeriodToMove = $arrayworkHoursTask["taskIdTaskPeriodToMove"];
                            $workHours = $arrayworkHoursTask["workHours"];
                            $listUserId = $arrayworkHoursTask["listUserId"];
                            $heureDebutTravailMatin = $arrayworkHoursTask["heureDebutTravailMatin"];
                            $heureFinTravailMatin = $arrayworkHoursTask["heureFinTravailMatin"];
                            $heureDebutTravailApresMidi = $arrayworkHoursTask["heureDebutTravailApresMidi"];
                            $heureFinTravailApresMidi = $arrayworkHoursTask["heureFinTravailApresMidi"];
                            $heuresDisposMatin = $arrayworkHoursTask["heuresDisposMatin"] - $arrayInfos["dureePeriodToMoveMatin"];
                            $heuresDisposApresMidi = $arrayworkHoursTask["heuresDisposApresMidi"] - $arrayInfos["dureePeriodToMoveApresMidi"];
                            $heureDebutPeriodMatin = $arrayworkHoursTask["heureDebutPeriodMatin"];
                            $heureFinPeriodMatin = $arrayworkHoursTask["heureFinPeriodMatin"];
                            $heureDebutPeriodApresMidi = $arrayworkHoursTask["heureDebutPeriodApresMidi"];
                            $heureFinPeriodApresMidi = $arrayworkHoursTask["heureFinPeriodApresMidi"];

                            $heureFinNewPeriod = Carbon::parse($heureDebutTaskPrecedente)->format('H:i');
                            $dureePeriodToMove = Carbon::parse($taskPeriodToMove['end_time'])->floatDiffInHours(Carbon::parse($taskPeriodToMove['start_time']));
                        }
                    endwhile;

                    //s'il y a une task_period après et qu'elle n'a pas été traitée, on la déplace le matin car il n'y a pas d'indispo pour la journée
                    if (count($listTaskPeriodToMove) == 0 && ((in_array($taskPeriodToMove['id'], $listTaskPeriodToSave)) || (in_array($taskPeriodToMove['id'], $listTaskPeriodToDelete)))) {
                        break;
                    }

                    //$heureFinNewPeriod = Carbon::createFromFormat('Y-m-d H:i:s', $heureDebutTaskPrecedente)->format('H:i');
                    $heureFinNewPeriod = Carbon::parse($heureDebutTaskPrecedente)->format('H:i');

                    if (Carbon::parse($heureDebutTaskPrecedente)->format("H:i") > $heureFinTravailMatin) {
                        $heuresDisposMatin = Carbon::parse($heureFinTravailMatin)->floatDiffInHours($heureDebutTravailMatin);
                    } else {
                        $heuresDisposMatin = Carbon::parse($heureDebutTravailMatin)->floatDiffInHours($heureFinNewPeriod);
                    }

                    //$taskPeriodToMove=array_pop($listTaskPeriodToMove);

                    $dureePeriodToMove = Carbon::parse($taskPeriodToMove['end_time'])->floatDiffInHours(Carbon::parse($taskPeriodToMove['start_time']));

                    $finJourVeille = Carbon::parse($p)->endOfDay()->subDays(1)->format("Y-m-d H:i:s");

                    // if($hoursWork[2] == "00:00:00" && $hoursWork[3] == "00:00:00"){
                    //     $heuresDisposApresMidi=0;
                    // }
                    while (($heuresDisposMatin >= 0) && ($heureDebutTaskPrecedente >= $finJourVeille)) :
                        //s'il y a assez de temps pour déplacer la task_period entièrement le matin on la déplace dans la période du matin
                        if ($heuresDisposMatin >= $dureePeriodToMove) {
                            $arrayInfos = $this->moveBeforeEntireTaskMorning($p, $taskPeriodToMove, $workHours, $listTaskPeriodToSave, $listTaskPeriodToCreate, $listTaskPeriodToDelete, $dureePeriodToMove, $heureFinNewPeriod, $heuresDisposMatin, $heureDebutPeriodMatin, $heureFinPeriodMatin, $heureFinPeriodApresMidi);

                            //$heuresDisposMatin=$arrayInfos["heuresDisposMatin"];
                            $heureDebutTaskPrecedente = $arrayInfos["heureDebutTaskPrecedente"];

                            $newListTaskPeriod = $this->addInlistTaskPeriodToMoveAndCreate($arrayInfos, $listTaskPeriodToSave, $listTaskPeriodToCreate, $listTaskPeriodToDelete);
                            $listTaskPeriodToSave = $newListTaskPeriod["move"];
                            $listTaskPeriodToCreate = $newListTaskPeriod["create"];
                            $listTaskPeriodToDelete = $newListTaskPeriod["delete"];
                            //s'il n'y a plus de task_period on sort de la boucle sinon on la déplace le matin car il n'y a pas d'indispo pour la journée
                            if (count($listTaskPeriodToMove) == 0) {
                                break;
                            }
                            $taskPeriodToMove = array_pop($listTaskPeriodToMove);

                            $arrayworkHoursTask = $this->workHoursTask($taskIdTaskPeriodToMove, $taskPeriodToMove, $listUserId, $dayName, $heureFinNewPeriod, "before");
                            $taskIdTaskPeriodToMove = $arrayworkHoursTask["taskIdTaskPeriodToMove"];
                            $workHours = $arrayworkHoursTask["workHours"];
                            $listUserId = $arrayworkHoursTask["listUserId"];
                            $heureDebutTravailMatin = $arrayworkHoursTask["heureDebutTravailMatin"];
                            $heureFinTravailMatin = $arrayworkHoursTask["heureFinTravailMatin"];
                            $heureDebutTravailApresMidi = $arrayworkHoursTask["heureDebutTravailApresMidi"];
                            $heureFinTravailApresMidi = $arrayworkHoursTask["heureFinTravailApresMidi"];
                            $heuresDisposMatin = $arrayworkHoursTask["heuresDisposMatin"] - $arrayInfos["dureePeriodToMoveMatin"];
                            $heuresDisposApresMidi = $arrayworkHoursTask["heuresDisposApresMidi"];
                            $heureDebutPeriodMatin = $arrayworkHoursTask["heureDebutPeriodMatin"];
                            $heureFinPeriodMatin = $arrayworkHoursTask["heureFinPeriodMatin"];
                            $heureDebutPeriodApresMidi = $arrayworkHoursTask["heureDebutPeriodApresMidi"];
                            $heureFinPeriodApresMidi = $arrayworkHoursTask["heureFinPeriodApresMidi"];

                            $heureFinNewPeriod = Carbon::parse($heureDebutTaskPrecedente)->format('H:i');
                            $dureePeriodToMove = Carbon::parse($taskPeriodToMove['end_time'])->floatDiffInHours(Carbon::parse($taskPeriodToMove['start_time']));
                        }

                        //s'il n'y a pas assez de temps pour déplacer la task_period entièrement le matin, on la déplace dans la période pour remplir s'il reste du temps le matin et on crée une nouvelle task_period avec le temps restant l'après-midi' veille
                        else {
                            $arrayInfos = $this->moveBeforeTaskMorningCreateTaskAfternoonBefore($p, $listDebutTaskPeriodIndispo, $taskPeriodToMove, $workHours, $listTaskPeriodToSave, $listTaskPeriodToCreate, $listTaskPeriodToDelete, $heureFinNewPeriod, $dureePeriodToMove, $heuresDisposMatin, $heuresDisposApresMidi, $heureDebutPeriodMatin, $heureFinPeriodMatin, $heureFinTravailMatin, $heureDebutPeriodApresMidi, $heureFinPeriodApresMidi, $hoursWork);

                            //$heuresDisposMatin=$arrayInfos["heuresDisposMatin"];
                            //$heuresDisposApresMidi=$arrayInfos["heuresDisposApresMidi"];
                            $heureDebutTaskPrecedente = $arrayInfos["heureDebutTaskPrecedente"];

                            $newListTaskPeriod = $this->addInlistTaskPeriodToMoveAndCreate($arrayInfos, $listTaskPeriodToSave, $listTaskPeriodToCreate, $listTaskPeriodToDelete);
                            $listTaskPeriodToSave = $newListTaskPeriod["move"];
                            $listTaskPeriodToCreate = $newListTaskPeriod["create"];
                            $listTaskPeriodToDelete = $newListTaskPeriod["delete"];
                            //s'il n'y a plus de task_period on sort de la boucle sinon on la déplace le matin car il n'y a pas d'indispo pour la journée
                            if (count($listTaskPeriodToMove) == 0) {
                                break;
                            }
                            $taskPeriodToMove = array_pop($listTaskPeriodToMove);

                            $arrayworkHoursTask = $this->workHoursTask($taskIdTaskPeriodToMove, $taskPeriodToMove, $listUserId, $dayName, $heureFinNewPeriod, "before");
                            $taskIdTaskPeriodToMove = $arrayworkHoursTask["taskIdTaskPeriodToMove"];
                            $workHours = $arrayworkHoursTask["workHours"];
                            $listUserId = $arrayworkHoursTask["listUserId"];
                            $heureDebutTravailMatin = $arrayworkHoursTask["heureDebutTravailMatin"];
                            $heureFinTravailMatin = $arrayworkHoursTask["heureFinTravailMatin"];
                            $heureDebutTravailApresMidi = $arrayworkHoursTask["heureDebutTravailApresMidi"];
                            $heureFinTravailApresMidi = $arrayworkHoursTask["heureFinTravailApresMidi"];
                            $heuresDisposMatin = $arrayworkHoursTask["heuresDisposMatin"] - $arrayInfos["dureePeriodToMoveMatin"];
                            $heuresDisposApresMidi = $arrayworkHoursTask["heuresDisposApresMidi"] - $arrayInfos["dureePeriodToMoveApresMidi"];
                            $heureDebutPeriodMatin = $arrayworkHoursTask["heureDebutPeriodMatin"];
                            $heureFinPeriodMatin = $arrayworkHoursTask["heureFinPeriodMatin"];
                            $heureDebutPeriodApresMidi = $arrayworkHoursTask["heureDebutPeriodApresMidi"];
                            $heureFinPeriodApresMidi = $arrayworkHoursTask["heureFinPeriodApresMidi"];

                            $heureFinNewPeriod = Carbon::parse($heureDebutTaskPrecedente)->format('H:i');
                            $dureePeriodToMove = Carbon::parse($taskPeriodToMove['end_time'])->floatDiffInHours(Carbon::parse($taskPeriodToMove['start_time']));
                        }
                    endwhile;


                    //s'il n'y a plus de temps disponible le matin on passe un tour pour aller au jour d'avant
                    if ($heuresDisposMatin == 0) {
                        $heureFinNewPeriod = Carbon::createFromFormat('Y-m-d H:i:s', $heureDebutTaskPrecedente)->format('H:i');
                        //continue;
                    }
                }

                //pas dans les heures de travail des utilisateurs
                else {
                    array_push($listTaskPeriodToSave, "erreur horaires");
                    return $listTaskPeriodToSave;
                }

                //ajouter la task_period qui vient d'être déplacée dans la liste des tasks_periods indisponibles pour ensuite déplacer la suivante après

                array_push($list, $taskPeriodToMove);


                //unset($listTaskPeriodToMove[0]);
                // if(count($listTaskPeriodToMove)==0){
                //     break;
                // }


            }
            //sinon voir si indispo matin ou après midi
            else {
                $controllerLog = new Logger('hours');
                $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
                $controllerLog->info('ok', ['indispo']);

                //si indispo matin on déplace l'après-midi
                foreach ($listDebutTaskPeriodIndispo as $dateDebutIndispo) {

                    $debutIndispo = Carbon::createFromFormat('Y-m-d', $dateDebutIndispo)->format('Y-m-d');
                }

                //si indispo après-midi on déplace le lendemain matin
            }

            // if(count($listTaskPeriodToMove)==0){
            //     break;
            // }

            // }
            // if(count($listTaskPeriodToMove)==0){
            //     break;
            // }
            //$heureFinNewPeriod = Carbon::createFromFormat('Y-m-d H:i:s', $heureDebutTaskPrecedente)->format('H:i');
            $controllerLog = new Logger('hours');
            $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
            $controllerLog->info('heureDebutTaskPrecedente ap', [$heureDebutTaskPrecedente]);

            $heureFinNewPeriod = Carbon::parse($heureDebutTaskPrecedente)->format('H:i');

            $controllerLog = new Logger('hours');
            $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
            $controllerLog->info('heureFinNewPeriod ap', [$heureFinNewPeriod]);
        }
        if (count($listTaskPeriodToMove) > 0) {
            array_push($listTaskPeriodToSave, "avant aujourd'hui");
            return $listTaskPeriodToSave;
        }
        $listTaskPeriodToMoveAndCreate = array(
            "move" => $listTaskPeriodToSave,
            "create" => $listTaskPeriodToCreate,
            "delete" => $listTaskPeriodToDelete,
        );

        return ($listTaskPeriodToMoveAndCreate);
    }

    private function moveBeforeEntireTaskMorning($p, $taskPeriodToMove, $workHours, $listTaskPeriodToSave, $listTaskPeriodToCreate, $listTaskPeriodToDelete, $dureePeriodToMove, $heureFinNewPeriod, $heuresDisposMatin, $heureDebutPeriodMatin, $heureFinPeriodMatin, $heureFinPeriodApresMidi)
    {

        if ($heureFinNewPeriod < $heureFinPeriodMatin) {
            $heureFinPeriodMatin = Carbon::parse($heureFinNewPeriod)->floatDiffInHours(Carbon::parse("00:00:00"));
        }

        $hours = floor($heureFinPeriodMatin - $dureePeriodToMove);

        $minutes = ($heureFinPeriodMatin - $dureePeriodToMove - $hours) * 60;

        $taskPeriodToMove['start_time'] = Carbon::parse($p)->startOfDay()->addHours($hours)->addMinutes($minutes)->format('Y-m-d H:i:s');

        $hours = floor($heureFinPeriodMatin);
        $minutes = ($heureFinPeriodMatin - $hours) * 60;

        $taskPeriodToMove['end_time'] = Carbon::parse($p)->startOfDay()->addHours($hours)->addMinutes($minutes)->format('Y-m-d H:i:s');

        $heureDebutTaskPrecedente = $taskPeriodToMove['start_time'];

        array_push($listTaskPeriodToSave, $taskPeriodToMove['id']);
        array_push($listTaskPeriodToSave, $taskPeriodToMove['start_time']);
        array_push($listTaskPeriodToSave, $taskPeriodToMove['end_time']);

        $controllerLog = new Logger('hours');
        $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
        $controllerLog->info('dureePeriodToMove', [$dureePeriodToMove]);

        $controllerLog = new Logger('hours');
        $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
        $controllerLog->info('heuresDisposMatin', [$heuresDisposMatin]);

        $heuresDisposMatin -= $dureePeriodToMove;

        $arrayInfos = array(
            "heureDebutTaskPrecedente" => $heureDebutTaskPrecedente,
            "dureePeriodToMoveMatin" => $dureePeriodToMove,
            "listTaskPeriodToSave" => $listTaskPeriodToSave,
            "listTaskPeriodToCreate" => $listTaskPeriodToCreate,
            "listTaskPeriodToDelete" => $listTaskPeriodToDelete,
        );
        return $arrayInfos;
    }

    private function moveBeforeTaskMorningCreateTaskAfternoonBefore($p, $listDebutTaskPeriodIndispo, $taskPeriodToMove, $workHours, $listTaskPeriodToSave, $listTaskPeriodToCreate, $listTaskPeriodToDelete, $heureFinNewPeriod, $dureePeriodToMove, $heuresDisposMatin, $heuresDisposApresMidi, $heureDebutPeriodMatin, $heureFinPeriodMatin, $heureFinTravailMatin, $heureDebutPeriodApresMidi, $heureFinPeriodApresMidi, $hoursWork)
    {
        $controllerLog = new Logger('hours');
        $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
        $controllerLog->info('heuresDisposMatin', [$heuresDisposMatin]);

        //s'il reste du temps le matin on remplit la période sinon on crée une nouvelle task_period avec le temps total la veille après-midi
        if ($heuresDisposMatin > 0) {
            if ($heureFinNewPeriod > $heureFinTravailMatin) {
                $heureFinNewPeriod = $heureFinTravailMatin;
            }

            $hours = floor($heureDebutPeriodMatin);
            $minutes = ($heureDebutPeriodMatin - $hours) * 60;

            $taskPeriodToMove['start_time'] = Carbon::parse($p)->startOfDay()->addHours($hours)->addMinutes($minutes)->format('Y-m-d H:i:s');

            $heureFinPeriodInt = Carbon::parse($heureFinNewPeriod)->floatDiffInHours(Carbon::parse("00:00:00"));
            $hoursFinPeriod = floor($heureFinPeriodInt);
            $minutesFinPeriod = ($heureFinPeriodInt - $hoursFinPeriod) * 60;

            $taskPeriodToMove['end_time'] = Carbon::parse($p)->startOfDay()->addHours($hoursFinPeriod)->addMinutes($minutesFinPeriod)->format('Y-m-d H:i:s');

            array_push($listTaskPeriodToSave, $taskPeriodToMove['id']);
            array_push($listTaskPeriodToSave, $taskPeriodToMove['start_time']);
            array_push($listTaskPeriodToSave, $taskPeriodToMove['end_time']);

            $dureePeriodToMove -= Carbon::parse($taskPeriodToMove['end_time'])->floatDiffInHours(Carbon::parse($taskPeriodToMove['start_time']));
            $heuresDisposMatin -= $dureePeriodToMove;
            $heureDebutTaskPrecedente = $taskPeriodToMove['start_time'];

            $dureePeriodToMoveMatin = Carbon::parse($taskPeriodToMove['end_time'])->floatDiffInHours(Carbon::parse($taskPeriodToMove['start_time']));
            $dureePeriodToMoveApresMidi = 0;
            // }
            // else if($hoursWork[2] == "00:00:00" && $hoursWork[3] == "00:00:00" && $heuresDisposMatin == 0){
            //     //on créé une task_period avec le temps restant ou total la veille matin
            //     //$nbJour = $this->nbDaysBeforeWorkDay($p, $workHours, $listDebutTaskPeriodIndispo);

            //     $nbJour=0;

            //     $hours = floor($heureFinPeriodMatin - $dureePeriodToMove);
            //     $minutes = ($heureFinPeriodMatin - $dureePeriodToMove - $hours) * 60;

            //     $newHour = Carbon::parse($p)->startOfDay()->addHours($hours)->addMinutes($minutes)->format('H:i');

            //     $controllerLog = new Logger('hours');
            //     $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')),Logger::INFO);
            //     $controllerLog->info('newHour',[$newHour]);


            //     //si on dépasse avant l'heure de début de travail veille matin, on remplit la veille matin et on créé une nouvelle task_period la veille matin avec le temps restant
            //     if ($newHour < $heureDebutPeriodMatin) {

            //         $hoursDebutMatin = floor($heureDebutPeriodMatin);
            //         $minutesDebutMatin = ($heureDebutPeriodMatin - $hoursDebutMatin) * 60;

            //         $startTime = Carbon::parse($p)->startOfDay()->addHours($hoursDebutMatin)->addMinutes($minutesDebutMatin)->subDays($nbJour)->format('Y-m-d H:i:s');

            //         $hoursFinMatin = floor($heureFinPeriodMatin);
            //         $minutesFinMatin = ($heureFinPeriodMatin - $hoursFinMatin) * 60;

            //         $endTime = Carbon::parse($p)->startOfDay()->addHours($hoursFinMatin)->addMinutes($minutesFinMatin)->subDays($nbJour)->format('Y-m-d H:i:s');

            //         array_push($listTaskPeriodToSave, $taskPeriodToMove['id']);
            //         array_push($listTaskPeriodToSave, $startTime);
            //         array_push($listTaskPeriodToSave, $endTime);

            //         $heureDebutTaskPrecedente = $startTime;
            //         $heuresDisposMatin = 0;
            //         $dureePeriodToMoveMatin = Carbon::parse($endTime)->floatDiffInHours(Carbon::parse($startTime));
            //         $dureePeriodToMove -= Carbon::parse($endTime)->floatDiffInHours(Carbon::parse($startTime));

            //         //on créé une nouvelle task_period avec le temps restant la veille matin

            //         //$p = Carbon::parse($p)->subDays(1);
            //         $nbJour = $this->nbDaysBeforeWorkDay($p, $workHours, $listDebutTaskPeriodIndispo);

            //         $hours = floor($heureFinPeriodMatin - $dureePeriodToMove - $heuresDisposMatin);
            //         $minutes = ($heureFinPeriodMatin - $dureePeriodToMove - $heuresDisposMatin - $hours) * 60;

            //         $startTime = Carbon::parse($p)->startOfDay()->addHours($hours)->addMinutes($minutes)->subDays($nbJour)->format('Y-m-d H:i:s');

            //         $hoursFinMatin = floor($heureFinPeriodMatin);
            //         $minutesFinMatin = ($heureFinPeriodMatin - $hoursFinMatin) * 60;

            //         $endTime = Carbon::parse($p)->startOfDay()->addHours($hoursFinMatin)->addMinutes($minutesFinMatin)->subDays($nbJour)->format('Y-m-d H:i:s');

            //         $controllerLog = new Logger('hours');
            //         $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')),Logger::INFO);
            //         $controllerLog->info('startTime',[$startTime]);

            //         $controllerLog = new Logger('hours');
            //         $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')),Logger::INFO);
            //         $controllerLog->info('endTime',[$endTime]);

            //         array_push($listTaskPeriodToCreate, $taskPeriodToMove['task_id']);
            //         array_push($listTaskPeriodToCreate, $startTime);
            //         array_push($listTaskPeriodToCreate, $endTime);

            //         $heureDebutTaskPrecedente = $startTime;
            //         $heuresDisposApresMidi = 0;
            //         $dureePeriodToMoveMatin = Carbon::parse($endTime)->floatDiffInHours(Carbon::parse($startTime));
            //         $dureePeriodToMoveApresMidi = 0;
            //     }

            //     //sinon on créé une nouvelle task_period avec le temps restant la veille matin
            //     else {

            //         $hours = floor($heureFinPeriodMatin - $dureePeriodToMove);
            //         $minutes = ($heureFinPeriodMatin - $dureePeriodToMove - $hours) * 60;

            //         $startTime = Carbon::parse($p)->startOfDay()->addHours($hours)->addMinutes($minutes)->subDays($nbJour)->format('Y-m-d H:i:s');

            //         $hours = floor($heureFinPeriodMatin);
            //         $minutes = ($heureFinPeriodMatin - $hours) * 60;

            //         $endTime = Carbon::parse($p)->startOfDay()->addHours($hours)->addMinutes($minutes)->subDays($nbJour)->format('Y-m-d H:i:s');

            //         $controllerLog = new Logger('hours');
            //         $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')),Logger::INFO);
            //         $controllerLog->info('startTime',[$startTime]);

            //         $controllerLog = new Logger('hours');
            //         $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')),Logger::INFO);
            //         $controllerLog->info('endTime',[$endTime]);

            //         array_push($listTaskPeriodToSave, $taskPeriodToMove['id']);
            //         array_push($listTaskPeriodToSave, $startTime);
            //         array_push($listTaskPeriodToSave, $endTime);

            //         $heureDebutTaskPrecedente = $startTime;
            //         $heuresDisposApresMidi = 0;

            //         $startT=Carbon::createFromformat('Y-m-d H:i:s',$startTime)->format('H:i');

            //         $start = Carbon::parse($startT)->floatDiffInHours(Carbon::parse("00:00:00"));

            //         $controllerLog = new Logger('hours');
            //         $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')),Logger::INFO);
            //         $controllerLog->info('start',[$start]);

            //         $heuresDisposMatin = $start-$heureDebutPeriodMatin;
            //         //$dureePeriodToMoveMatin = Carbon::parse($endTime)->floatDiffInHours(Carbon::parse($startTime));
            //         $dureePeriodToMoveApresMidi = 0;
            //         $dureePeriodToMoveMatin = 0;

            //         $controllerLog = new Logger('hours');
            //         $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')),Logger::INFO);
            //         $controllerLog->info('heuresDisposMatin ap',[$heuresDisposMatin]);
            //     }
        }
        //sinon on ajoute l'id de la task_period à la liste des tasks_period à supprimer
        else {
            array_push($listTaskPeriodToDelete, $taskPeriodToMove['id']);
            array_push($listTaskPeriodToDelete, $taskPeriodToMove['start_time']);
            array_push($listTaskPeriodToDelete, $taskPeriodToMove['end_time']);
        }

        //if($hoursWork[2] != "00:00:00" || $hoursWork[3] != "00:00:00"){
        //on créé une task_period avec le temps restant ou total
        $nbJour = $this->nbDaysBeforeWorkDay($p, $workHours, $listDebutTaskPeriodIndispo);

        $hours = floor($heureFinPeriodApresMidi - $dureePeriodToMove);
        $minutes = ($heureFinPeriodApresMidi - $dureePeriodToMove - $hours) * 60;

        $newHour = Carbon::parse($p)->startOfDay()->addHours($hours)->addMinutes($minutes)->format('H:i');

        //si on dépasse avant l'heure de début de travail veille après midi, on remplit la veille après-midi et on créé une nouvelle task_period la veille matin avec le temps restant
        if ($newHour < $heureDebutPeriodApresMidi) {

            $hoursDebutAM = floor($heureDebutPeriodApresMidi);
            $minutesDebutAM = ($heureDebutPeriodApresMidi - $hoursDebutAM) * 60;

            $startTime = Carbon::parse($p)->startOfDay()->addHours($hoursDebutAM)->addMinutes($minutesDebutAM)->subDays($nbJour)->format('Y-m-d H:i:s');

            $hoursFinAM = floor($heureFinPeriodApresMidi);
            $minutesFinAM = ($heureFinPeriodApresMidi - $hoursFinAM) * 60;

            $endTime = Carbon::parse($p)->startOfDay()->addHours($hoursFinAM)->addMinutes($minutesFinAM)->subDays($nbJour)->format('Y-m-d H:i:s');

            array_push($listTaskPeriodToCreate, $taskPeriodToMove['task_id']);
            array_push($listTaskPeriodToCreate, $startTime);
            array_push($listTaskPeriodToCreate, $endTime);

            $heureDebutTaskPrecedente = $startTime;
            $heuresDisposApresMidi = 0;
            $dureePeriodToMoveApresMidi = Carbon::parse($endTime)->floatDiffInHours(Carbon::parse($startTime));
            $dureePeriodToMove -= Carbon::parse($endTime)->floatDiffInHours(Carbon::parse($startTime));

            //on créé une nouvelle task_period avec le temps restant la veille matin

            $hours = floor($heureFinPeriodMatin - $dureePeriodToMove - $heuresDisposApresMidi);
            $minutes = ($heureFinPeriodMatin - $dureePeriodToMove - $heuresDisposApresMidi - $hours) * 60;

            $startTime = Carbon::parse($p)->startOfDay()->addHours($hours)->addMinutes($minutes)->subDays($nbJour)->format('Y-m-d H:i:s');

            $hoursFinMatin = floor($heureFinPeriodMatin);
            $minutesFinMatin = ($heureFinPeriodMatin - $hoursFinMatin) * 60;

            $endTime = Carbon::parse($p)->startOfDay()->addHours($hoursFinMatin)->addMinutes($minutesFinMatin)->subDays($nbJour)->format('Y-m-d H:i:s');

            array_push($listTaskPeriodToCreate, $taskPeriodToMove['task_id']);
            array_push($listTaskPeriodToCreate, $startTime);
            array_push($listTaskPeriodToCreate, $endTime);

            $heureDebutTaskPrecedente = $startTime;
            $heuresDisposApresMidi = 0;
            $dureePeriodToMoveMatin = Carbon::parse($endTime)->floatDiffInHours(Carbon::parse($startTime));
        }

        //sinon on créé une nouvelle task_period avec le temps restant la veille après-midi
        else {

            $hours = floor($heureFinPeriodApresMidi - $dureePeriodToMove);
            $minutes = ($heureFinPeriodApresMidi - $dureePeriodToMove - $hours) * 60;

            $startTime = Carbon::parse($p)->startOfDay()->addHours($hours)->addMinutes($minutes)->subDays($nbJour)->format('Y-m-d H:i:s');

            $hours = floor($heureFinPeriodApresMidi);
            $minutes = ($heureFinPeriodApresMidi - $hours) * 60;

            $endTime = Carbon::parse($p)->startOfDay()->addHours($hours)->addMinutes($minutes)->subDays($nbJour)->format('Y-m-d H:i:s');

            array_push($listTaskPeriodToCreate, $taskPeriodToMove['task_id']);
            array_push($listTaskPeriodToCreate, $startTime);
            array_push($listTaskPeriodToCreate, $endTime);

            $heureDebutTaskPrecedente = $startTime;
            $heuresDisposApresMidi -= Carbon::parse($endTime)->floatDiffInHours(Carbon::parse($startTime));
            $dureePeriodToMoveApresMidi = Carbon::parse($endTime)->floatDiffInHours(Carbon::parse($startTime));
            $dureePeriodToMoveMatin = 0;
        }
        //}

        $arrayInfos = array(
            "heureDebutTaskPrecedente" => $heureDebutTaskPrecedente,
            "dureePeriodToMoveMatin" => $dureePeriodToMoveMatin,
            "dureePeriodToMoveApresMidi" => $dureePeriodToMoveApresMidi,
            "listTaskPeriodToSave" => $listTaskPeriodToSave,
            "listTaskPeriodToCreate" => $listTaskPeriodToCreate,
            "listTaskPeriodToDelete" => $listTaskPeriodToDelete,
        );
        return $arrayInfos;
    }

    private function moveBeforeEntireTaskAfternoon($p, $taskPeriodToMove, $workHours, $listTaskPeriodToSave, $listTaskPeriodToCreate, $listTaskPeriodToDelete, $dureePeriodToMove, $heureFinNewPeriod, $heuresDisposApresMidi, $heureDebutPeriodMatin, $heureFinPeriodMatin, $heureFinPeriodApresMidi)
    {

        // if($heureFinNewPeriod<=$heureDebutTravailApresMidi){
        //     $startTime=Carbon::parse($heureDebutTravailApresMidi)->floatDiffInHours(Carbon::parse("00:00:00"));
        // }
        // else{
        //     $startTime=$heureFinNewPeriod;
        // }
        $heureFinPeriodInt = Carbon::parse($heureFinNewPeriod)->floatDiffInHours(Carbon::parse("00:00:00"));
        $hours = floor($heureFinPeriodInt - $dureePeriodToMove);
        $minutes = ($heureFinPeriodInt - $dureePeriodToMove - $hours) * 60;
        $taskPeriodToMove['start_time'] = Carbon::parse($p)->startOfDay()->addHours($hours)->addMinutes($minutes)->format('Y-m-d H:i:s');

        $hours = floor($heureFinPeriodInt);
        $minutes = ($heureFinPeriodInt - $hours) * 60;
        $taskPeriodToMove['end_time'] = Carbon::parse($p)->startOfDay()->addHours($hours)->addMinutes($minutes)->format('Y-m-d H:i:s');

        array_push($listTaskPeriodToSave, $taskPeriodToMove['id']);
        array_push($listTaskPeriodToSave, $taskPeriodToMove['start_time']);
        array_push($listTaskPeriodToSave, $taskPeriodToMove['end_time']);

        $heureDebutTaskPrecedente = $taskPeriodToMove['start_time'];

        $heuresDisposApresMidi -= $dureePeriodToMove;

        $arrayInfos = array(
            "heureDebutTaskPrecedente" => $heureDebutTaskPrecedente,
            "dureePeriodToMoveApresMidi" => $dureePeriodToMove,
            "listTaskPeriodToSave" => $listTaskPeriodToSave,
            "listTaskPeriodToCreate" => $listTaskPeriodToCreate,
            "listTaskPeriodToDelete" => $listTaskPeriodToDelete,
        );
        return $arrayInfos;
    }

    private function moveBeforeTaskAfternoonCreateTaskMorning($p, $listDebutTaskPeriodIndispo, $taskPeriodToMove, $workHours, $listTaskPeriodToSave, $listTaskPeriodToCreate, $listTaskPeriodToDelete, $heureFinNewPeriod, $dureePeriodToMove, $heuresDisposApresMidi, $heureDebutPeriodMatin, $heureFinPeriodMatin, $heureDebutPeriodApresMidi, $heureFinPeriodApresMidi, $hoursWork)
    {
        //s'il reste du temps l'après-midi on remplit la période sinon on crée une nouvelle task_period avec le temps total le matin
        if ($heuresDisposApresMidi > 0) {
            $hours = floor($heureDebutPeriodApresMidi);
            $minutes = ($heureDebutPeriodApresMidi - $hours) * 60;

            $taskPeriodToMove['start_time'] = Carbon::parse($p)->startOfDay()->addHours($hours)->addMinutes($minutes)->format('Y-m-d H:i:s');

            $heureFinPeriodInt = Carbon::parse($heureFinNewPeriod)->floatDiffInHours(Carbon::parse("00:00:00"));
            $hours = floor($heureFinPeriodInt);
            $minutes = ($heureFinPeriodInt - $hours) * 60;

            $taskPeriodToMove['end_time'] = Carbon::parse($p)->startOfDay()->addHours($hours)->addMinutes($minutes)->format('Y-m-d H:i:s');

            array_push($listTaskPeriodToSave, $taskPeriodToMove['id']);
            array_push($listTaskPeriodToSave, $taskPeriodToMove['start_time']);
            array_push($listTaskPeriodToSave, $taskPeriodToMove['end_time']);

            $heureDebutTaskPrecedente = $taskPeriodToMove['start_time'];

            $dureePeriodToMove -= Carbon::parse($taskPeriodToMove['end_time'])->floatDiffInHours(Carbon::parse($taskPeriodToMove['start_time']));
            $heuresDisposApresMidi -= $dureePeriodToMove;

            $dureePeriodToMoveApresMidi = Carbon::parse($taskPeriodToMove['end_time'])->floatDiffInHours(Carbon::parse($taskPeriodToMove['start_time']));
            $dureePeriodToMoveMatin = 0;
        }

        //sinon on ajoute l'id de la task_period à la liste des tasks_period à supprimer
        else {
            array_push($listTaskPeriodToDelete, $taskPeriodToMove['id']);
            array_push($listTaskPeriodToDelete, $taskPeriodToMove['start_time']);
            array_push($listTaskPeriodToDelete, $taskPeriodToMove['end_time']);
        }

        $hours = floor($heureFinPeriodMatin - $dureePeriodToMove);
        $minutes = ($heureFinPeriodMatin - $dureePeriodToMove - $hours) * 60;

        $nbJour = $this->nbDaysBeforeWorkDay($p, $workHours, $listDebutTaskPeriodIndispo);

        $newHour = Carbon::parse($p)->startOfDay()->addHours($hours)->addMinutes($minutes)->format('H:i');

        //si on dépasse avant l'heure de début de travail matin, on remplit le matin et on créé une nouvelle task_period pour la veille après-midi
        if ($newHour < $heureDebutPeriodMatin) {

            $hoursDebutMatin = floor($heureDebutPeriodMatin);
            $minutesDebutMatin = ($heureDebutPeriodMatin - $hoursDebutMatin) * 60;

            $startTime = Carbon::parse($p)->startOfDay()->addHours($hoursDebutMatin)->addMinutes($minutesDebutMatin)->format('Y-m-d H:i:s');

            $hoursFinMatin = floor($heureFinPeriodMatin);
            $minutesFinMatin = ($heureFinPeriodMatin - $hoursFinMatin) * 60;

            $endTime = Carbon::parse($p)->startOfDay()->addHours($hoursFinMatin)->addMinutes($minutesFinMatin)->format('Y-m-d H:i:s');

            $dureePeriodToMoveMatin = Carbon::parse($endTime)->floatDiffInHours(Carbon::parse($startTime));

            array_push($listTaskPeriodToCreate, $taskPeriodToMove['task_id']);
            array_push($listTaskPeriodToCreate, $startTime);
            array_push($listTaskPeriodToCreate, $endTime);

            $heureDebutTaskPrecedente = $startTime;
            $heuresDisposMatin = 0;
            $dureePeriodToMove -= Carbon::parse($endTime)->floatDiffInHours(Carbon::parse($startTime));

            $hours = floor($heureFinPeriodApresMidi - $dureePeriodToMove - $heuresDisposMatin);
            $minutes = ($heureFinPeriodApresMidi - $dureePeriodToMove - $heuresDisposMatin - $hours) * 60;

            $hoursFinAM = floor($heureFinPeriodApresMidi);
            $minutesFinAM = ($heureFinPeriodApresMidi - $hoursFinAM) * 60;

            $startTime = Carbon::parse($p)->startOfDay()->addHours($hours)->addMinutes($minutes)->subDays($nbJour)->format('Y-m-d H:i:s');

            $endTime = Carbon::parse($p)->startOfDay()->addHours($hoursFinAM)->addMinutes($minutesFinAM)->subDays($nbJour)->format('Y-m-d H:i:s');

            array_push($listTaskPeriodToCreate, $taskPeriodToMove['task_id']);
            array_push($listTaskPeriodToCreate, $startTime);
            array_push($listTaskPeriodToCreate, $endTime);

            $heureDebutTaskPrecedente = $startTime;
            $dureePeriodToMoveApresMidi = Carbon::parse($endTime)->floatDiffInHours(Carbon::parse($startTime));
        }

        //sinon on créé une nouvelle task_period avec le temps restant le matin
        else {
            $heureDebutTaskPrecedente = $taskPeriodToMove['start_time'];

            $heureFinPeriodInt = Carbon::parse($heureDebutTaskPrecedente)->floatDiffInHours(Carbon::parse("00:00:00"));
            $hours = floor($heureFinPeriodMatin - $dureePeriodToMove);
            $minutes = ($heureFinPeriodMatin - $dureePeriodToMove - $hours) * 60;

            $startTime = Carbon::parse($p)->startOfDay()->addHours($hours)->addMinutes($minutes)->format('Y-m-d H:i:s');

            $hours = floor($heureFinPeriodMatin);
            $minutes = ($heureFinPeriodMatin - $hours) * 60;

            $endTime = Carbon::parse($p)->startOfDay()->addHours($hours)->addMinutes($minutes)->format('Y-m-d H:i:s');

            array_push($listTaskPeriodToCreate, $taskPeriodToMove['task_id']);
            array_push($listTaskPeriodToCreate, $startTime);
            array_push($listTaskPeriodToCreate, $endTime);

            $heureDebutTaskPrecedente = $startTime;
            $dureePeriodToMoveMatin = Carbon::parse($endTime)->floatDiffInHours(Carbon::parse($startTime));
            $dureePeriodToMoveApresMidi = 0;
        }

        $arrayInfos = array(
            "heureDebutTaskPrecedente" => $heureDebutTaskPrecedente,
            "dureePeriodToMoveMatin" => $dureePeriodToMoveMatin,
            "dureePeriodToMoveApresMidi" => $dureePeriodToMoveApresMidi,
            "listTaskPeriodToSave" => $listTaskPeriodToSave,
            "listTaskPeriodToCreate" => $listTaskPeriodToCreate,
            "listTaskPeriodToDelete" => $listTaskPeriodToDelete,
        );

        return $arrayInfos;
    }

    //-----------------------------------------------------------------------------------------------------------
    //-----------------------------------------------------------------------------------------------------------
    //-----------------------------------------------------------------------------------------------------------
    //-----------------------------------------------------------------------------------------------------------

    private function moveAndCreateTaskPeriodAfter(Request $request, array $listIdTaskDependant, array $listDebutTaskPeriodIndispo, array $list)
    {
        $taskPeriod = TaskPeriod::where('id', $request->id)->get();
        $listTaskPeriodToSave = array();
        $listTaskPeriodToCreate = array();
        $listTaskPeriodToDelete = array();
        $taskPeriodOrigine = TaskPeriod::where('id', $request->id)->get();

        array_push($listTaskPeriodToSave, $request->id);
        array_push($listTaskPeriodToSave, $request->start);
        array_push($listTaskPeriodToSave, $request->end);

        $task = Task::where('id', $taskPeriod[0]['task_id'])->with('workarea', 'skills', 'comments', 'previousTasks', 'documents', 'project', 'periods')->get();

        //s'il y a des tasks_periods de la même task après, déplacer les dernières task_periods de la task où l'utilisateur et le workarea de la task sont dispos
        $tasksPeriodTask = TaskPeriod::where('task_id', $taskPeriod[0]['task_id'])->get();
        $listTaskPeriodToMove = array();
        foreach ($tasksPeriodTask as $taskPeriodTask) {
            if (
                $taskPeriodTask['start_time'] >= $taskPeriodOrigine[0]['end_time'] && $taskPeriodTask['end_time'] >= $taskPeriodOrigine[0]['end_time']
                && $taskPeriodTask['id'] != $taskPeriodOrigine[0]['id']
            ) {
                array_push($listTaskPeriodToMove, $taskPeriodTask);
            }
        }
        //s'il y a des tasks dépendantes à faire après, ajouter les tasks_period des tasks dépendantes dans la liste des tasks_period à déplacer
        if ($listIdTaskDependant != null) {
            $listTaskPeriodDependantToMove = array();
            foreach ($listIdTaskDependant as $Id) {
                $tasksPeriodDependantTask = TaskPeriod::where('task_id', $Id)->get();
                foreach ($tasksPeriodDependantTask as $taskPeriodDependantTask) {
                    array_push($listTaskPeriodDependantToMove, $taskPeriodDependantTask);
                    array_push($listTaskPeriodToMove, $taskPeriodDependantTask);
                }
            }
        }

        if (count($listTaskPeriodToMove) == 0) {
            return $listTaskPeriodToMove;
        }

        $listUserId = array();
        array_push($listUserId, $task[0]['user_id']);

        $workHours = $this->workHoursUsers($listUserId);

        $newPeriod = CarbonPeriod::create(Carbon::createFromFormat('Y-m-d H:i', $request->end), Carbon::now()->addYears(2)->format('Y-m-d H:i'));
        $heureDebutNewPeriod = Carbon::createFromFormat('Y-m-d H:i:s', $newPeriod->first())->format('H:i');
        $taskPeriodToMove = array_shift($listTaskPeriodToMove);

        $taskIdTaskPeriodToMove = $taskPeriodToMove['task_id'];

        $heureFinTaskPrecedente = $request->end;

        $controllerLog = new Logger('hours');
        $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
        $controllerLog->info('listDebutTaskPeriodIndispo', [$listDebutTaskPeriodIndispo]);

        foreach ($newPeriod as $keyNewPeriod => $p) {
            if ((in_array($taskPeriodToMove['id'], $listTaskPeriodToSave)) || (in_array($taskPeriodToMove['id'], $listTaskPeriodToDelete))) {
                break;
            }

            $dateP = Carbon::createFromFormat('Y-m-d H:i:s', $p)->format('Y-m-d');

            $controllerLog = new Logger('hours');
            $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
            $controllerLog->info('dateP', [$dateP]);

            $dayName = Carbon::create($dateP)->dayName;
            $hoursWork = $workHours[$dayName];
            //s'il n'y a pas d'heures de travail pour la journée on passe un tour de boucle pour ne pas ajouter des tasks_period sur les jours où il n'y a pas de travail
            if (
                ($hoursWork[0] == "00:00:00" || $hoursWork[0] == null) && ($hoursWork[1] == "00:00:00" || $hoursWork[1] == null) &&
                ($hoursWork[2] == "00:00:00" || $hoursWork[2] == null) && ($hoursWork[3] == "00:00:00" || $hoursWork[3] == null)
            ) {
                continue;
            }

            $controllerLog = new Logger('hours');
            $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
            $controllerLog->info('hoursWork', [$hoursWork]);

            $dureePeriodDispoMatin = Carbon::parse($hoursWork[0])->floatDiffInHours(Carbon::parse($hoursWork[1]));
            $dureePeriodDispoApresmidi = Carbon::parse($hoursWork[2])->floatDiffInHours(Carbon::parse($hoursWork[3]));

            $heureDebutTravailMatin = Carbon::createFromFormat('H:i:s', $hoursWork[0])->format('H:i');
            $heureFinTravailMatin = Carbon::createFromFormat('H:i:s', $hoursWork[1])->format('H:i');
            $heureDebutTravailApresMidi = Carbon::createFromFormat('H:i:s', $hoursWork[2])->format('H:i');

            $controllerLog = new Logger('hours');
            $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
            $controllerLog->info('heureDebutTravailApresMidi', [$heureDebutTravailApresMidi]);

            $heureFinTravailApresMidi = Carbon::createFromFormat('H:i:s', $hoursWork[3])->format('H:i');

            $controllerLog = new Logger('hours');
            $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
            $controllerLog->info('heureFinTravailApresMidi', [$heureFinTravailApresMidi]);

            $heuresDisposMatin = Carbon::parse($heureFinTravailMatin)->floatDiffInHours($heureDebutNewPeriod);
            // if($hoursWork[2] == "00:00:00" && $hoursWork[3] == "00:00:00"){
            //     $heuresDisposApresMidi=0;
            // }
            // else{
            if ($heureDebutNewPeriod <= $heureDebutTravailApresMidi) {
                $heuresDisposApresMidi = Carbon::parse($heureFinTravailApresMidi)->floatDiffInHours($heureDebutTravailApresMidi);
            } else {
                $heuresDisposApresMidi = Carbon::parse($heureFinTravailApresMidi)->floatDiffInHours($heureDebutNewPeriod);
            }
            //}
            $controllerLog = new Logger('hours');
            $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
            $controllerLog->info('heuresDisposApresMidi', [$heuresDisposApresMidi]);

            //s'il n'y a pas d'indispo pour ce jour de la période (ni matin ni après-midi)
            if (!in_array($dateP, $listDebutTaskPeriodIndispo)) {
                $controllerLog = new Logger('hours');
                $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
                $controllerLog->info('ok', ['ok']);

                $controllerLog = new Logger('hours');
                $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
                $controllerLog->info('taskPeriodToMove', [$taskPeriodToMove]);

                $dureePeriodToMove = Carbon::parse($taskPeriodToMove['end_time'])->floatDiffInHours(Carbon::parse($taskPeriodToMove['start_time']));

                $controllerLog = new Logger('hours');
                $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
                $controllerLog->info('dureePeriodToMove', [$dureePeriodToMove]);

                $heureDebutPeriodMatin = Carbon::parse($hoursWork[0])->floatDiffInHours(Carbon::parse("00:00:00"));
                $heureFinPeriodMatin = Carbon::parse($hoursWork[1])->floatDiffInHours(Carbon::parse("00:00:00"));
                $heureDebutPeriodApresMidi = Carbon::parse($hoursWork[2])->floatDiffInHours(Carbon::parse("00:00:00"));

                $controllerLog = new Logger('hours');
                $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
                $controllerLog->info('heureDebutPeriodApresMidi', [$heureDebutPeriodApresMidi]);

                $heureFinPeriodApresMidi = Carbon::parse($hoursWork[3])->floatDiffInHours(Carbon::parse("00:00:00"));

                //si le début de la nouvelle période est compris dans les heures de travail du matin
                if ($heureDebutNewPeriod >= $heureDebutTravailMatin && $heureDebutNewPeriod < $heureFinTravailMatin) {
                    // if (($heureDebutNewPeriod >= $heureDebutTravailMatin && $heureDebutNewPeriod < $heureFinTravailMatin && ($hoursWork[2] != "00:00:00" || $hoursWork[3] != "00:00:00")) ||
                    //     ($heureDebutNewPeriod >= $heureDebutTravailMatin && $heureDebutNewPeriod <= $heureFinTravailMatin && ($hoursWork[2] == "00:00:00" && $hoursWork[3] == "00:00:00"))) {

                    $controllerLog = new Logger('hours');
                    $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
                    $controllerLog->info('ok', ['matin']);

                    $controllerLog = new Logger('hours');
                    $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
                    $controllerLog->info('heureDebutNewPeriod', [$heureDebutNewPeriod]);

                    $controllerLog = new Logger('hours');
                    $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
                    $controllerLog->info('heureFinTravailMatin', [$heureFinTravailMatin]);

                    $heuresDisposMatin = Carbon::parse($heureFinTravailMatin)->floatDiffInHours($heureDebutNewPeriod);

                    $controllerLog = new Logger('hours');
                    $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
                    $controllerLog->info('heuresDisposMatin', [$heuresDisposMatin]);

                    $controllerLog = new Logger('hours');
                    $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
                    $controllerLog->info('hoursWork[1]', [$hoursWork[1]]);

                    $heureFinMatin = Carbon::parse($hoursWork[1])->floatDiffInHours(Carbon::parse("00:00:00"));

                    $controllerLog = new Logger('hours');
                    $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
                    $controllerLog->info('heureFinMatin', [$heureFinMatin]);

                    $finMatineeJourCourant = Carbon::parse($p)->startOfDay()->addHours($heureFinMatin)->format("Y-m-d H:i:s");

                    $controllerLog = new Logger('hours');
                    $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
                    $controllerLog->info('ok', ['matin ap']);

                    //on remplit jusqu'à la fin de la matinée
                    while (($heuresDisposMatin >= 0) && ($heureFinTaskPrecedente <= $finMatineeJourCourant)) :
                        // while ((($heuresDisposMatin >= 0) && (($hoursWork[2] != "00:00:00" || $hoursWork[3] != "00:00:00") && $heureFinTaskPrecedente <= $finMatineeJourCourant)) ||
                        //         (($heuresDisposMatin > 0) && (($hoursWork[2] == "00:00:00" && $hoursWork[3] == "00:00:00") && $heureFinTaskPrecedente < $finMatineeJourCourant))) :

                        $controllerLog = new Logger('hours');
                        $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
                        $controllerLog->info('heuresDisposMatin', [$heuresDisposMatin]);

                        $controllerLog = new Logger('hours');
                        $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
                        $controllerLog->info('heureFinTaskPrecedente', [$heureFinTaskPrecedente]);

                        //s'il y a assez de temps pour déplacer la task_period entièrement le matin on la déplace dans la période du matin
                        if ($heuresDisposMatin >= $dureePeriodToMove) {

                            $controllerLog = new Logger('hours');
                            $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
                            $controllerLog->info('heuresDisposMatin', [$heuresDisposMatin]);

                            $arrayInfos = $this->moveAfterEntireTaskMorning($p, $taskPeriodToMove, $workHours, $listTaskPeriodToSave, $listTaskPeriodToCreate, $listTaskPeriodToDelete, $dureePeriodToMove, $heureDebutNewPeriod, $heuresDisposMatin, $heureDebutPeriodMatin, $heureFinPeriodMatin, $heureFinPeriodApresMidi);

                            //$heuresDisposMatin=$arrayInfos["heuresDisposMatin"];
                            $controllerLog = new Logger('hours');
                            $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
                            $controllerLog->info('ok', ['ok ap']);

                            $controllerLog = new Logger('hours');
                            $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
                            $controllerLog->info('listTaskPeriodToMove', [$listTaskPeriodToMove]);

                            $newListTaskPeriod = $this->addInlistTaskPeriodToMoveAndCreate($arrayInfos, $listTaskPeriodToSave, $listTaskPeriodToCreate, $listTaskPeriodToDelete);
                            $listTaskPeriodToSave = $newListTaskPeriod["move"];
                            $listTaskPeriodToCreate = $newListTaskPeriod["create"];
                            $listTaskPeriodToDelete = $newListTaskPeriod["delete"];
                            // TaskPeriod::where('id',$taskPeriodToMove['id'])->update([
                            //     'start_time' => Carbon::parse($p)->startOfDay()->addHours($hoursFinPeriod)->addMinutes($minutesFinPeriod)->format('Y-m-d H:i:s'),
                            //     'end_time' =>Carbon::parse($p)->startOfDay()->addHours($heureFinPeriodInt)->addHours($dureePeriodToMove)->format('Y-m-d H:i:s'),
                            // ]);
                            //s'il n'y a plus de task_period on sort de la boucle sinon on la déplace le matin car il n'y a pas d'indispo pour la journée
                            if (count($listTaskPeriodToMove) == 0) {
                                break;
                            }
                            $taskPeriodToMove = array_shift($listTaskPeriodToMove);

                            $arrayworkHoursTask = $this->workHoursTask($taskIdTaskPeriodToMove, $taskPeriodToMove, $listUserId, $dayName, $heureDebutNewPeriod, "next");
                            $taskIdTaskPeriodToMove = $arrayworkHoursTask["taskIdTaskPeriodToMove"];
                            $workHours = $arrayworkHoursTask["workHours"];
                            $listUserId = $arrayworkHoursTask["listUserId"];
                            $heureDebutTravailMatin = $arrayworkHoursTask["heureDebutTravailMatin"];
                            $heureFinTravailMatin = $arrayworkHoursTask["heureFinTravailMatin"];
                            $heureDebutTravailApresMidi = $arrayworkHoursTask["heureDebutTravailApresMidi"];
                            $heureFinTravailApresMidi = $arrayworkHoursTask["heureFinTravailApresMidi"];
                            $heuresDisposMatin = $arrayworkHoursTask["heuresDisposMatin"] - $arrayInfos["dureePeriodToMoveMatin"];
                            $heuresDisposApresMidi = $arrayworkHoursTask["heuresDisposApresMidi"];
                            $heureDebutPeriodMatin = $arrayworkHoursTask["heureDebutPeriodMatin"];
                            $heureFinPeriodMatin = $arrayworkHoursTask["heureFinPeriodMatin"];
                            $heureDebutPeriodApresMidi = $arrayworkHoursTask["heureDebutPeriodApresMidi"];
                            $heureFinPeriodApresMidi = $arrayworkHoursTask["heureFinPeriodApresMidi"];

                            $heureFinTaskPrecedente = $arrayInfos["heureFinTaskPrecedente"];

                            $controllerLog = new Logger('hours');
                            $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
                            $controllerLog->info('heureFinTaskPrecedente ap', [$heureFinTaskPrecedente]);

                            $heureDebutNewPeriod = Carbon::parse($heureFinTaskPrecedente)->format('H:i');

                            $dureePeriodToMove = Carbon::parse($taskPeriodToMove['end_time'])->floatDiffInHours(Carbon::parse($taskPeriodToMove['start_time']));
                        }
                        //s'il n'y a pas assez de temps pour déplacer la task_period entièrement le matin, on la déplace dans la période pour remplir et on crée une nouvelle task_period avec le temps restant sur l'après-midi
                        else {
                            $controllerLog = new Logger('hours');
                            $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
                            $controllerLog->info('heuresDisposMatin', [$heuresDisposMatin]);

                            $arrayInfos = $this->moveAfterTaskMorningCreateTaskAfternoon($p, $listDebutTaskPeriodIndispo, $taskPeriodToMove, $workHours, $listTaskPeriodToSave, $listTaskPeriodToCreate, $listTaskPeriodToDelete, $heureDebutNewPeriod, $dureePeriodToMove, $heuresDisposMatin, $heuresDisposApresMidi, $heureDebutPeriodMatin, $heureFinPeriodMatin, $heureDebutPeriodApresMidi, $heureFinPeriodApresMidi, $hoursWork);

                            //$heuresDisposMatin=$arrayInfos["heuresDisposMatin"];
                            $heureFinTaskPrecedente = $arrayInfos["heureFinTaskPrecedente"];
                            //$heuresDisposApresMidi=$arrayInfos["heuresDisposApresMidi"];

                            $controllerLog = new Logger('hours');
                            $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
                            $controllerLog->info('heureFinTaskPrecedente', [$heureFinTaskPrecedente]);


                            $newListTaskPeriod = $this->addInlistTaskPeriodToMoveAndCreate($arrayInfos, $listTaskPeriodToSave, $listTaskPeriodToCreate, $listTaskPeriodToDelete);
                            $listTaskPeriodToSave = $newListTaskPeriod["move"];
                            $listTaskPeriodToCreate = $newListTaskPeriod["create"];
                            $listTaskPeriodToDelete = $newListTaskPeriod["delete"];
                            //s'il n'y a plus de task_period on sort de la boucle sinon on la déplace le matin car il n'y a pas d'indispo pour la journée
                            if (count($listTaskPeriodToMove) == 0) {
                                break;
                            }
                            $taskPeriodToMove = array_shift($listTaskPeriodToMove);

                            $arrayworkHoursTask = $this->workHoursTask($taskIdTaskPeriodToMove, $taskPeriodToMove, $listUserId, $dayName, $heureDebutNewPeriod, "next");
                            $taskIdTaskPeriodToMove = $arrayworkHoursTask["taskIdTaskPeriodToMove"];
                            $workHours = $arrayworkHoursTask["workHours"];
                            $listUserId = $arrayworkHoursTask["listUserId"];
                            $heureDebutTravailMatin = $arrayworkHoursTask["heureDebutTravailMatin"];
                            $heureFinTravailMatin = $arrayworkHoursTask["heureFinTravailMatin"];
                            $heureDebutTravailApresMidi = $arrayworkHoursTask["heureDebutTravailApresMidi"];
                            $heureFinTravailApresMidi = $arrayworkHoursTask["heureFinTravailApresMidi"];
                            $heuresDisposMatin = $arrayworkHoursTask["heuresDisposMatin"] - $arrayInfos["dureePeriodToMoveMatin"];
                            $heuresDisposApresMidi = $arrayworkHoursTask["heuresDisposApresMidi"] - $arrayInfos["dureePeriodToMoveApresMidi"];
                            $heureDebutPeriodMatin = $arrayworkHoursTask["heureDebutPeriodMatin"];
                            $heureFinPeriodMatin = $arrayworkHoursTask["heureFinPeriodMatin"];
                            $heureDebutPeriodApresMidi = $arrayworkHoursTask["heureDebutPeriodApresMidi"];
                            $heureFinPeriodApresMidi = $arrayworkHoursTask["heureFinPeriodApresMidi"];

                            $heureDebutNewPeriod = Carbon::parse($heureFinTaskPrecedente)->format('H:i');

                            $dureePeriodToMove = Carbon::parse($taskPeriodToMove['end_time'])->floatDiffInHours(Carbon::parse($taskPeriodToMove['start_time']));
                        }
                    endwhile;

                    $controllerLog = new Logger('hours');
                    $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
                    $controllerLog->info('heureFinTaskPrecedente', [$heureFinTaskPrecedente]);

                    //$heureDebutNewPeriod = Carbon::createFromFormat('Y-m-d H:i:s', $heureFinTaskPrecedente)->format('H:i');
                    $heureDebutNewPeriod = Carbon::parse($heureFinTaskPrecedente)->format('H:i');

                    $controllerLog = new Logger('hours');
                    $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
                    $controllerLog->info('heureDebutNewPeriod', [$heureDebutNewPeriod]);

                    $debutJourLendemain = Carbon::parse($p)->startOfDay()->addDays(1)->format("Y-m-d H:i:s");

                    if ((in_array($taskPeriodToMove['id'], $listTaskPeriodToSave)) || (in_array($taskPeriodToMove['id'], $listTaskPeriodToDelete))) {
                        break;
                    }

                    // if($hoursWork[2] == "00:00:00" && $hoursWork[3] == "00:00:00"){
                    //     $heuresDisposApresMidi=0;
                    // }
                    //on remplit jusqu'au lendemain matin
                    while (($heuresDisposApresMidi >= 0) && ($heureFinTaskPrecedente <= $debutJourLendemain)) :

                        $controllerLog = new Logger('hours');
                        $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
                        $controllerLog->info('heuresDisposApresMidi', [$heuresDisposApresMidi]);

                        $controllerLog = new Logger('hours');
                        $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
                        $controllerLog->info('heureFinTaskPrecedente', [$heureFinTaskPrecedente]);

                        $controllerLog = new Logger('hours');
                        $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
                        $controllerLog->info('debutJourLendemain', [$debutJourLendemain]);

                        //s'il y a assez de temps pour déplacer la task_period entièrement l'après-midi on la déplace dans la période de l'après-midi
                        if ($heuresDisposApresMidi >= $dureePeriodToMove) {
                            $arrayInfos = $this->moveAfterEntireTaskAfternoon($p, $taskPeriodToMove, $workHours, $listTaskPeriodToSave, $listTaskPeriodToCreate, $listTaskPeriodToDelete, $dureePeriodToMove, $heureDebutNewPeriod, $heuresDisposApresMidi, $heureDebutPeriodMatin, $heureFinPeriodMatin, $heureDebutTravailApresMidi, $heureFinPeriodApresMidi);

                            //$heuresDisposApresMidi=$arrayInfos["heuresDisposApresMidi"];
                            $heureFinTaskPrecedente = $arrayInfos["heureFinTaskPrecedente"];


                            $newListTaskPeriod = $this->addInlistTaskPeriodToMoveAndCreate($arrayInfos, $listTaskPeriodToSave, $listTaskPeriodToCreate, $listTaskPeriodToDelete);
                            $listTaskPeriodToSave = $newListTaskPeriod["move"];
                            $listTaskPeriodToCreate = $newListTaskPeriod["create"];
                            $listTaskPeriodToDelete = $newListTaskPeriod["delete"];
                            //s'il n'y a plus de task_period on sort de la boucle sinon on la déplace le matin car il n'y a pas d'indispo pour la journée
                            if (count($listTaskPeriodToMove) == 0) {
                                break;
                            }
                            $taskPeriodToMove = array_shift($listTaskPeriodToMove);

                            $arrayworkHoursTask = $this->workHoursTask($taskIdTaskPeriodToMove, $taskPeriodToMove, $listUserId, $dayName, $heureDebutNewPeriod, "next");
                            $taskIdTaskPeriodToMove = $arrayworkHoursTask["taskIdTaskPeriodToMove"];
                            $workHours = $arrayworkHoursTask["workHours"];
                            $listUserId = $arrayworkHoursTask["listUserId"];
                            $heureDebutTravailMatin = $arrayworkHoursTask["heureDebutTravailMatin"];
                            $heureFinTravailMatin = $arrayworkHoursTask["heureFinTravailMatin"];
                            $heureDebutTravailApresMidi = $arrayworkHoursTask["heureDebutTravailApresMidi"];
                            $heureFinTravailApresMidi = $arrayworkHoursTask["heureFinTravailApresMidi"];
                            $heuresDisposMatin = $arrayworkHoursTask["heuresDisposMatin"];
                            $heuresDisposApresMidi = $arrayworkHoursTask["heuresDisposApresMidi"] - $arrayInfos["dureePeriodToMoveApresMidi"];
                            $heureDebutPeriodMatin = $arrayworkHoursTask["heureDebutPeriodMatin"];
                            $heureFinPeriodMatin = $arrayworkHoursTask["heureFinPeriodMatin"];
                            $heureDebutPeriodApresMidi = $arrayworkHoursTask["heureDebutPeriodApresMidi"];
                            $heureFinPeriodApresMidi = $arrayworkHoursTask["heureFinPeriodApresMidi"];

                            $heureDebutNewPeriod = Carbon::parse($heureFinTaskPrecedente)->format('H:i');

                            $dureePeriodToMove = Carbon::parse($taskPeriodToMove['end_time'])->floatDiffInHours(Carbon::parse($taskPeriodToMove['start_time']));
                        }
                        //s'il n'y a pas assez de temps pour déplacer la task_period entièrement l'apres-midi, on la déplace dans la période pour remplir
                        //et on créée une nouvelle task_period avec le temps restant le lendemain matin
                        else {
                            $arrayInfos = $this->moveAfterTaskAfternoonCreateTaskMorningAfter($p, $listDebutTaskPeriodIndispo, $taskPeriodToMove, $workHours, $listTaskPeriodToSave, $listTaskPeriodToCreate, $listTaskPeriodToDelete, $heureDebutNewPeriod, $dureePeriodToMove, $heuresDisposMatin, $heuresDisposApresMidi, $heureDebutPeriodMatin, $heureFinPeriodMatin, $heureDebutTravailApresMidi, $heureDebutPeriodApresMidi, $heureFinPeriodApresMidi, $hoursWork);

                            //$heuresDisposMatin=$arrayInfos["heuresDisposMatin"];
                            //$heuresDisposApresMidi=$arrayInfos["heuresDisposApresMidi"];
                            $heureFinTaskPrecedente = $arrayInfos["heureFinTaskPrecedente"];

                            $newListTaskPeriod = $this->addInlistTaskPeriodToMoveAndCreate($arrayInfos, $listTaskPeriodToSave, $listTaskPeriodToCreate, $listTaskPeriodToDelete);
                            $listTaskPeriodToSave = $newListTaskPeriod["move"];
                            $listTaskPeriodToCreate = $newListTaskPeriod["create"];
                            $listTaskPeriodToDelete = $newListTaskPeriod["delete"];
                            //s'il n'y a plus de task_period on sort de la boucle sinon on la déplace le matin car il n'y a pas d'indispo pour la journée
                            if (count($listTaskPeriodToMove) == 0) {
                                break;
                            }
                            $taskPeriodToMove = array_shift($listTaskPeriodToMove);

                            $arrayworkHoursTask = $this->workHoursTask($taskIdTaskPeriodToMove, $taskPeriodToMove, $listUserId, $dayName, $heureDebutNewPeriod, "next");
                            $taskIdTaskPeriodToMove = $arrayworkHoursTask["taskIdTaskPeriodToMove"];
                            $workHours = $arrayworkHoursTask["workHours"];
                            $listUserId = $arrayworkHoursTask["listUserId"];
                            $heureDebutTravailMatin = $arrayworkHoursTask["heureDebutTravailMatin"];
                            $heureFinTravailMatin = $arrayworkHoursTask["heureFinTravailMatin"];
                            $heureDebutTravailApresMidi = $arrayworkHoursTask["heureDebutTravailApresMidi"];
                            $heureFinTravailApresMidi = $arrayworkHoursTask["heureFinTravailApresMidi"];
                            $heuresDisposMatin = $arrayworkHoursTask["heuresDisposMatin"] - $arrayInfos["dureePeriodToMoveMatin"];
                            $heuresDisposApresMidi = $arrayworkHoursTask["heuresDisposApresMidi"] - $arrayInfos["dureePeriodToMoveApresMidi"];
                            $heureDebutPeriodMatin = $arrayworkHoursTask["heureDebutPeriodMatin"];
                            $heureFinPeriodMatin = $arrayworkHoursTask["heureFinPeriodMatin"];
                            $heureDebutPeriodApresMidi = $arrayworkHoursTask["heureDebutPeriodApresMidi"];
                            $heureFinPeriodApresMidi = $arrayworkHoursTask["heureFinPeriodApresMidi"];

                            $heureDebutNewPeriod = Carbon::parse($heureFinTaskPrecedente)->format('H:i');

                            $dureePeriodToMove = Carbon::parse($taskPeriodToMove['end_time'])->floatDiffInHours(Carbon::parse($taskPeriodToMove['start_time']));
                        }
                    endwhile;
                    $heureDebutNewPeriod = Carbon::createFromFormat('Y-m-d H:i:s', $heureFinTaskPrecedente)->format('H:i');
                }

                //si le début de la nouvelle période est compris dans les heures de travail de l'après midi
                else if ($heureDebutNewPeriod >= $heureFinTravailMatin && $heureDebutNewPeriod <= $heureFinTravailApresMidi) {

                    //$heuresDisposMatin=Carbon::parse($heureFinTravailMatin)->floatDiffInHours(Carbon::parse($heureDebutTravailMatin));

                    $arrayworkHoursTask = $this->workHoursTask($taskIdTaskPeriodToMove, $taskPeriodToMove, $listUserId, $dayName, $heureDebutNewPeriod, "next");
                    $heuresDisposMatin = $arrayworkHoursTask["heuresDisposMatin"];
                    $heuresDisposApresMidi = $arrayworkHoursTask["heuresDisposApresMidi"];

                    $debutJourLendemain = Carbon::parse($p)->startOfDay()->addDays(1)->format("Y-m-d H:i:s");

                    //on remplit jusqu'au lendemain matin
                    while (($heuresDisposApresMidi >= 0) && ($heureFinTaskPrecedente <= $debutJourLendemain)) :

                        //s'il y a assez de temps pour déplacer la task_period entièrement l'après-midi on la déplace dans la période de l'après-midi
                        if ($heuresDisposApresMidi >= $dureePeriodToMove) {
                            $arrayInfos = $this->moveAfterEntireTaskAfternoon($p, $taskPeriodToMove, $workHours, $listTaskPeriodToSave, $listTaskPeriodToCreate, $listTaskPeriodToDelete, $dureePeriodToMove, $heureDebutNewPeriod, $heuresDisposApresMidi, $heureDebutPeriodMatin, $heureFinPeriodMatin, $heureDebutTravailApresMidi, $heureFinPeriodApresMidi);

                            //$heuresDisposApresMidi=$arrayInfos["heuresDisposApresMidi"];
                            $heureFinTaskPrecedente = $arrayInfos["heureFinTaskPrecedente"];


                            $newListTaskPeriod = $this->addInlistTaskPeriodToMoveAndCreate($arrayInfos, $listTaskPeriodToSave, $listTaskPeriodToCreate, $listTaskPeriodToDelete);
                            $listTaskPeriodToSave = $newListTaskPeriod["move"];
                            $listTaskPeriodToCreate = $newListTaskPeriod["create"];
                            $listTaskPeriodToDelete = $newListTaskPeriod["delete"];
                            //s'il n'y a plus de task_period on sort de la boucle sinon on la déplace le matin car il n'y a pas d'indispo pour la journée
                            if (count($listTaskPeriodToMove) == 0) {
                                break;
                            }
                            $taskPeriodToMove = array_shift($listTaskPeriodToMove);

                            $arrayworkHoursTask = $this->workHoursTask($taskIdTaskPeriodToMove, $taskPeriodToMove, $listUserId, $dayName, $heureDebutNewPeriod, "next");
                            $taskIdTaskPeriodToMove = $arrayworkHoursTask["taskIdTaskPeriodToMove"];
                            $workHours = $arrayworkHoursTask["workHours"];
                            $listUserId = $arrayworkHoursTask["listUserId"];
                            $heureDebutTravailMatin = $arrayworkHoursTask["heureDebutTravailMatin"];
                            $heureFinTravailMatin = $arrayworkHoursTask["heureFinTravailMatin"];
                            $heureDebutTravailApresMidi = $arrayworkHoursTask["heureDebutTravailApresMidi"];
                            $heureFinTravailApresMidi = $arrayworkHoursTask["heureFinTravailApresMidi"];
                            $heuresDisposMatin = $arrayworkHoursTask["heuresDisposMatin"];
                            $heuresDisposApresMidi = $arrayworkHoursTask["heuresDisposApresMidi"] - $arrayInfos["dureePeriodToMoveApresMidi"];
                            $heureDebutPeriodMatin = $arrayworkHoursTask["heureDebutPeriodMatin"];
                            $heureFinPeriodMatin = $arrayworkHoursTask["heureFinPeriodMatin"];
                            $heureDebutPeriodApresMidi = $arrayworkHoursTask["heureDebutPeriodApresMidi"];
                            $heureFinPeriodApresMidi = $arrayworkHoursTask["heureFinPeriodApresMidi"];

                            $heureDebutNewPeriod = Carbon::parse($heureFinTaskPrecedente)->format('H:i');

                            $dureePeriodToMove = Carbon::parse($taskPeriodToMove['end_time'])->floatDiffInHours(Carbon::parse($taskPeriodToMove['start_time']));
                        }
                        //s'il n'y a pas assez de temps pour déplacer la task_period entièrement l'apres-midi, on la déplace dans la période pour remplir
                        //et on créée une nouvelle task_period avec le temps restant le lendemain matin
                        else {
                            $arrayInfos = $this->moveAfterTaskAfternoonCreateTaskMorningAfter($p, $listDebutTaskPeriodIndispo, $taskPeriodToMove, $workHours, $listTaskPeriodToSave, $listTaskPeriodToCreate, $listTaskPeriodToDelete, $heureDebutNewPeriod, $dureePeriodToMove, $heuresDisposMatin, $heuresDisposApresMidi, $heureDebutPeriodMatin, $heureFinPeriodMatin, $heureDebutTravailApresMidi, $heureDebutPeriodApresMidi, $heureFinPeriodApresMidi, $hoursWork);

                            //$heuresDisposMatin=$arrayInfos["heuresDisposMatin"];
                            //$heuresDisposApresMidi=$arrayInfos["heuresDisposApresMidi"];
                            $heureFinTaskPrecedente = $arrayInfos["heureFinTaskPrecedente"];


                            $newListTaskPeriod = $this->addInlistTaskPeriodToMoveAndCreate($arrayInfos, $listTaskPeriodToSave, $listTaskPeriodToCreate, $listTaskPeriodToDelete);
                            $listTaskPeriodToSave = $newListTaskPeriod["move"];
                            $listTaskPeriodToCreate = $newListTaskPeriod["create"];
                            $listTaskPeriodToDelete = $newListTaskPeriod["delete"];
                            //s'il n'y a plus de task_period on sort de la boucle sinon on la déplace le matin car il n'y a pas d'indispo pour la journée
                            if (count($listTaskPeriodToMove) == 0) {
                                break;
                            }
                            $taskPeriodToMove = array_shift($listTaskPeriodToMove);

                            $arrayworkHoursTask = $this->workHoursTask($taskIdTaskPeriodToMove, $taskPeriodToMove, $listUserId, $dayName, $heureDebutNewPeriod, "next");
                            $taskIdTaskPeriodToMove = $arrayworkHoursTask["taskIdTaskPeriodToMove"];
                            $workHours = $arrayworkHoursTask["workHours"];
                            $listUserId = $arrayworkHoursTask["listUserId"];
                            $heureDebutTravailMatin = $arrayworkHoursTask["heureDebutTravailMatin"];
                            $heureFinTravailMatin = $arrayworkHoursTask["heureFinTravailMatin"];
                            $heureDebutTravailApresMidi = $arrayworkHoursTask["heureDebutTravailApresMidi"];
                            $heureFinTravailApresMidi = $arrayworkHoursTask["heureFinTravailApresMidi"];
                            $heuresDisposMatin = $arrayworkHoursTask["heuresDisposMatin"] - $arrayInfos["dureePeriodToMoveMatin"];
                            $heuresDisposApresMidi = $arrayworkHoursTask["heuresDisposApresMidi"] - $arrayInfos["dureePeriodToMoveApresMidi"];
                            $heureDebutPeriodMatin = $arrayworkHoursTask["heureDebutPeriodMatin"];
                            $heureFinPeriodMatin = $arrayworkHoursTask["heureFinPeriodMatin"];
                            $heureDebutPeriodApresMidi = $arrayworkHoursTask["heureDebutPeriodApresMidi"];
                            $heureFinPeriodApresMidi = $arrayworkHoursTask["heureFinPeriodApresMidi"];

                            $heureDebutNewPeriod = Carbon::parse($heureFinTaskPrecedente)->format('H:i');

                            $dureePeriodToMove = Carbon::parse($taskPeriodToMove['end_time'])->floatDiffInHours(Carbon::parse($taskPeriodToMove['start_time']));
                        }
                    endwhile;
                    $heureDebutNewPeriod = Carbon::createFromFormat('Y-m-d H:i:s', $heureFinTaskPrecedente)->format('H:i');
                }

                //pas dans les heures de travail des utilisateurs
                else {
                    array_push($listTaskPeriodToSave, "erreur horaires");
                    return $listTaskPeriodToSave;
                }

                //ajouter la task_period qui vient d'être déplacée dans la liste des tasks_periods indisponibles pour ensuite déplacer la suivante après
                array_push($list, $taskPeriodToMove);
            }

            if (count($listTaskPeriodToMove) == 0 && ((in_array($taskPeriodToMove['id'], $listTaskPeriodToSave)) || (in_array($taskPeriodToMove['id'], $listTaskPeriodToDelete)))) {
                break;
            }
            //sinon voir si indispo matin ou après midi
            else if (in_array($dateP, $listDebutTaskPeriodIndispo)) {
                $controllerLog = new Logger('hours');
                $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
                $controllerLog->info('ok', ['indispo']);

                //si indispo matin on déplace l'après-midi
                foreach ($listDebutTaskPeriodIndispo as $dateDebutIndispo) {

                    $debutIndispo = Carbon::createFromFormat('Y-m-d', $dateDebutIndispo)->format('Y-m-d');
                }

                //si indispo après-midi on déplace le lendemain matin
            }
            // }
            // if(count($listTaskPeriodToMove)==0){
            //     break;
            // }
            $controllerLog = new Logger('hours');
            $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
            $controllerLog->info('heureFinTaskPrecedente', [$heureFinTaskPrecedente]);

            //$heureDebutNewPeriod = Carbon::createFromFormat('Y-m-d H:i:s', $heureFinTaskPrecedente)->format('H:i');
            $heureDebutNewPeriod = Carbon::parse($heureFinTaskPrecedente)->format('H:i');

            $controllerLog = new Logger('hours');
            $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
            $controllerLog->info('heureDebutNewPeriod', [$heureDebutNewPeriod]);
        }
        $listTaskPeriodToMoveAndCreate = array(
            "move" => $listTaskPeriodToSave,
            "create" => $listTaskPeriodToCreate,
            "delete" => $listTaskPeriodToDelete
        );

        return ($listTaskPeriodToMoveAndCreate);
    }

    //-----------------------------------------------------------------------------------------------------------
    //-----------------------------------------------------------------------------------------------------------
    //-----------------------------------------------------------------------------------------------------------
    //-----------------------------------------------------------------------------------------------------------

    private function moveAfterEntireTaskMorning($p, $taskPeriodToMove, $workHours, $listTaskPeriodToSave, $listTaskPeriodToCreate, $listTaskPeriodToDelete, $dureePeriodToMove, $heureDebutNewPeriod, $heuresDisposMatin, $heureDebutPeriodMatin, $heureFinPeriodMatin, $heureFinPeriodApresMidi)
    {

        $heureDebutPeriodInt = Carbon::parse($heureDebutNewPeriod)->floatDiffInHours(Carbon::parse("00:00:00"));
        $hoursDebutPeriod = floor($heureDebutPeriodInt);

        $minutesDebutPeriod = ($heureDebutPeriodInt - $hoursDebutPeriod) * 60;

        $taskPeriodToMove['start_time'] = Carbon::parse($p)->startOfDay()->addHours($hoursDebutPeriod)->addMinutes($minutesDebutPeriod)->format('Y-m-d H:i:s');

        $hours = floor($heureDebutPeriodInt + $dureePeriodToMove);
        $minutes = ($heureDebutPeriodInt + $dureePeriodToMove - $hours) * 60;

        $taskPeriodToMove['end_time'] = Carbon::parse($p)->startOfDay()->addHours($hours)->addMinutes($minutes)->format('Y-m-d H:i:s');

        $heureFinTaskPrecedente = $taskPeriodToMove['end_time'];

        array_push($listTaskPeriodToSave, $taskPeriodToMove['id']);
        array_push($listTaskPeriodToSave, $taskPeriodToMove['start_time']);
        array_push($listTaskPeriodToSave, $taskPeriodToMove['end_time']);

        $heuresDisposMatin -= $dureePeriodToMove;

        $arrayInfos = array(
            "heureFinTaskPrecedente" => $heureFinTaskPrecedente,
            "dureePeriodToMoveMatin" => $dureePeriodToMove,
            "listTaskPeriodToSave" => $listTaskPeriodToSave,
            "listTaskPeriodToCreate" => $listTaskPeriodToCreate,
            "listTaskPeriodToDelete" => $listTaskPeriodToDelete,
        );
        return $arrayInfos;
    }

    private function moveAfterTaskMorningCreateTaskAfternoon($p, $listDebutTaskPeriodIndispo, $taskPeriodToMove, $workHours, $listTaskPeriodToSave, $listTaskPeriodToCreate, $listTaskPeriodToDelete, $heureDebutNewPeriod, $dureePeriodToMove, $heuresDisposMatin, $heuresDisposApresMidi, $heureDebutPeriodMatin, $heureFinPeriodMatin, $heureDebutPeriodApresMidi, $heureFinPeriodApresMidi, $hoursWork)
    {
        $controllerLog = new Logger('hours');
        $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
        $controllerLog->info('heuresDisposMatin', [$heuresDisposMatin]);

        //s'il reste du temps le matin on remplit la période sinon on crée une nouvelle task_period avec le temps total l'après-midi
        if ($heuresDisposMatin > 0) {
            $heureDebutPeriodInt = Carbon::parse($heureDebutNewPeriod)->floatDiffInHours(Carbon::parse("00:00:00"));
            $hoursDebutPeriod = floor($heureDebutPeriodInt);
            $minutesDebutPeriod = ($heureDebutPeriodInt - $hoursDebutPeriod) * 60;

            $taskPeriodToMove['start_time'] = Carbon::parse($p)->startOfDay()->addHours($hoursDebutPeriod)->addMinutes($minutesDebutPeriod)->format('Y-m-d H:i:s');

            $hours = floor($heureFinPeriodMatin);
            $minutes = ($heureFinPeriodMatin - $hours) * 60;

            $taskPeriodToMove['end_time'] = Carbon::parse($p)->startOfDay()->addHours($hours)->addMinutes($minutes)->format('Y-m-d H:i:s');

            array_push($listTaskPeriodToSave, $taskPeriodToMove['id']);
            array_push($listTaskPeriodToSave, $taskPeriodToMove['start_time']);
            array_push($listTaskPeriodToSave, $taskPeriodToMove['end_time']);

            $dureePeriodToMove -= Carbon::parse($taskPeriodToMove['end_time'])->floatDiffInHours(Carbon::parse($taskPeriodToMove['start_time']));

            $heuresDisposMatin -= Carbon::parse($taskPeriodToMove['end_time'])->floatDiffInHours(Carbon::parse($taskPeriodToMove['start_time']));
            $heureFinTaskPrecedente = $taskPeriodToMove['end_time'];
            $dureePeriodToMoveMatin = Carbon::parse($taskPeriodToMove['end_time'])->floatDiffInHours(Carbon::parse($taskPeriodToMove['start_time']));
            $dureePeriodToMoveApresMidi = 0;
        }

        //sinon on ajoute l'id de la task_period à la liste des tasks_period à supprimer
        else {
            array_push($listTaskPeriodToDelete, $taskPeriodToMove['id']);
            array_push($listTaskPeriodToDelete, $taskPeriodToMove['start_time']);
            array_push($listTaskPeriodToDelete, $taskPeriodToMove['end_time']);
        }
        $dureePeriodToMoveMatin = 0;

        //if($hoursWork[2] != "00:00:00" || $hoursWork[3] != "00:00:00"){
        //on créé une task_period avec le temps restant ou total
        $hours = floor($heureDebutPeriodApresMidi + $dureePeriodToMove - $heuresDisposMatin);

        $minutes = ($heureDebutPeriodApresMidi + $dureePeriodToMove - $heuresDisposMatin - $hours) * 60;

        $newHour = Carbon::parse($p)->startOfDay()->addHours($hours)->addMinutes($minutes)->format('H:i');

        //si on dépasse l'heure de fin de travail après midi, on remplit et on créé une nouvelle task_period pour le lendemain matin
        if ($newHour > $heureFinPeriodApresMidi) {

            $hoursDebutAM = floor($heureDebutPeriodApresMidi);
            $minutesDebutAM = ($heureDebutPeriodApresMidi - $hoursDebutAM) * 60;

            $startTime = Carbon::parse($p)->startOfDay()->addHours($hoursDebutAM)->addMinutes($minutesDebutAM)->format('Y-m-d H:i:s');

            $hoursFinAM = floor($heureFinPeriodApresMidi);
            $minutesFinAM = ($heureFinPeriodApresMidi - $hoursFinAM) * 60;

            $endTime = Carbon::parse($p)->startOfDay()->addHours($hoursFinAM)->addMinutes($minutesFinAM)->format('Y-m-d H:i:s');

            array_push($listTaskPeriodToCreate, $taskPeriodToMove['task_id']);
            array_push($listTaskPeriodToCreate, $startTime);
            array_push($listTaskPeriodToCreate, $endTime);

            $heureFinTaskPrecedente = $endTime;
            $heuresDisposApresMidi = 0;
            $dureePeriodToMove -= Carbon::parse($endTime)->floatDiffInHours(Carbon::parse($startTime));

            $nbJour = $this->nbDaysNextWorkDay($p, $workHours, $listDebutTaskPeriodIndispo);

            $hours = floor($heureDebutPeriodMatin + $dureePeriodToMove - $heuresDisposApresMidi);
            $minutes = ($heureDebutPeriodMatin + $dureePeriodToMove - $heuresDisposApresMidi - $hours) * 60;

            $hoursDebutMatin = floor($heureDebutPeriodMatin);
            $minutesDebutMatin = ($heureDebutPeriodMatin - $hoursDebutMatin) * 60;

            $startTime = Carbon::parse($p)->startOfDay()->addHours($hoursDebutMatin)->addMinutes($minutesDebutMatin)->addDays($nbJour)->format('Y-m-d H:i:s');

            $endTime = Carbon::parse($p)->startOfDay()->addHours($hours)->addMinutes($minutes)->addDays($nbJour)->format('Y-m-d H:i:s');

            array_push($listTaskPeriodToCreate, $taskPeriodToMove['task_id']);
            array_push($listTaskPeriodToCreate, $startTime);
            array_push($listTaskPeriodToCreate, $endTime);

            $heureFinTaskPrecedente = $endTime;
            $dureePeriodToMoveApresMidi = 0;
        }

        //sinon on créé une nouvelle task_period avec le temps restant
        else {
            $hoursDebutAM = floor($heureDebutPeriodApresMidi);
            $minutesDebutAM = ($heureDebutPeriodApresMidi - $hoursDebutAM) * 60;

            $startTime = Carbon::parse($p)->startOfDay()->addHours($hoursDebutAM)->addMinutes($minutesDebutAM)->format('Y-m-d H:i:s');

            $endTime = Carbon::parse($p)->startOfDay()->addHours($hours)->addMinutes($minutes)->format('Y-m-d H:i:s');

            array_push($listTaskPeriodToCreate, $taskPeriodToMove['task_id']);
            array_push($listTaskPeriodToCreate, $startTime);
            array_push($listTaskPeriodToCreate, $endTime);

            // TaskPeriod::create([
            //     'task_id' => $task[0]['id'],
            //     'start_time' => Carbon::parse($p)->startOfDay()->addHours($heureDebutPeriodApresMidi)->format('Y-m-d H:i:s'),
            //     'end_time' => Carbon::parse($p)->startOfDay()->addHours($hoursDebutPeriod)->addMinutes($minutesDebutPeriod)->format('Y-m-d H:i:s'),
            // ]);
            $heureFinTaskPrecedente = $endTime;
            $heuresDisposApresMidi -= Carbon::parse($endTime)->floatDiffInHours(Carbon::parse($startTime));
            $dureePeriodToMoveApresMidi = Carbon::parse($endTime)->floatDiffInHours(Carbon::parse($startTime));
        }
        //}

        $arrayInfos = array(
            "heureFinTaskPrecedente" => $heureFinTaskPrecedente,
            "dureePeriodToMoveMatin" => $dureePeriodToMoveMatin,
            "dureePeriodToMoveApresMidi" => $dureePeriodToMoveApresMidi,
            "listTaskPeriodToSave" => $listTaskPeriodToSave,
            "listTaskPeriodToCreate" => $listTaskPeriodToCreate,
            "listTaskPeriodToDelete" => $listTaskPeriodToDelete,
        );
        return $arrayInfos;
    }

    private function moveAfterEntireTaskAfternoon($p, $taskPeriodToMove, $workHours, $listTaskPeriodToSave, $listTaskPeriodToCreate, $listTaskPeriodToDelete, $dureePeriodToMove, $heureDebutNewPeriod, $heuresDisposApresMidi, $heureDebutPeriodMatin, $heureFinPeriodMatin, $heureDebutTravailApresMidi, $heureFinPeriodApresMidi)
    {
        if ($heureDebutNewPeriod <= $heureDebutTravailApresMidi) {
            $startTime = Carbon::parse($heureDebutTravailApresMidi)->floatDiffInHours(Carbon::parse("00:00:00"));
        } else {
            $startTime = Carbon::parse($heureDebutNewPeriod)->floatDiffInHours(Carbon::parse("00:00:00"));
        }

        $hours = floor($startTime);
        $minutes = ($startTime - $hours) * 60;

        $startTime = Carbon::parse($p)->startOfDay()->addHours($hours)->addMinutes($minutes)->format("H:i");

        $startTimeInt = Carbon::parse($startTime)->floatDiffInHours(Carbon::parse("00:00:00"));

        $hours = floor($startTimeInt);
        $minutes = ($startTimeInt - $hours) * 60;

        $taskPeriodToMove['start_time'] = Carbon::parse($p)->startOfDay()->addHours($hours)->addMinutes($minutes)->format('Y-m-d H:i:s');

        $hours = floor($startTimeInt + $dureePeriodToMove);
        $minutes = ($startTimeInt + $dureePeriodToMove - $hours) * 60;

        $taskPeriodToMove['end_time'] = Carbon::parse($p)->startOfDay()->addHours($hours)->addMinutes($minutes)->format('Y-m-d H:i:s');

        array_push($listTaskPeriodToSave, $taskPeriodToMove['id']);
        array_push($listTaskPeriodToSave, $taskPeriodToMove['start_time']);
        array_push($listTaskPeriodToSave, $taskPeriodToMove['end_time']);

        $heureFinTaskPrecedente = $taskPeriodToMove['end_time'];

        $heuresDisposApresMidi -= $dureePeriodToMove;

        $arrayInfos = array(
            "heureFinTaskPrecedente" => $heureFinTaskPrecedente,
            "dureePeriodToMoveApresMidi" => $dureePeriodToMove,
            "listTaskPeriodToSave" => $listTaskPeriodToSave,
            "listTaskPeriodToCreate" => $listTaskPeriodToCreate,
            "listTaskPeriodToDelete" => $listTaskPeriodToDelete,
        );

        return $arrayInfos;
    }

    private function moveAfterTaskAfternoonCreateTaskMorningAfter($p, $listDebutTaskPeriodIndispo, $taskPeriodToMove, $workHours, $listTaskPeriodToSave, $listTaskPeriodToCreate, $listTaskPeriodToDelete, $heureDebutNewPeriod, $dureePeriodToMove, $heuresDisposMatin, $heuresDisposApresMidi, $heureDebutPeriodMatin, $heureFinPeriodMatin, $heureDebutTravailApresMidi, $heureDebutPeriodApresMidi, $heureFinPeriodApresMidi, $hoursWork)
    {
        //s'il reste du temps l'après-midi on remplit la période sinon on crée une nouvelle task_period avec le temps total le lendemain matin
        if ($heuresDisposApresMidi > 0) {
            if ($heureDebutNewPeriod <= $heureDebutTravailApresMidi) {
                $startTime = Carbon::parse($heureDebutTravailApresMidi)->floatDiffInHours(Carbon::parse("00:00:00"));
            } else {
                $startTime = Carbon::parse($heureDebutNewPeriod)->floatDiffInHours(Carbon::parse("00:00:00"));
            }

            $hours = floor($startTime);
            $minutes = ($startTime - $hours) * 60;

            $startTime = Carbon::parse($p)->startOfDay()->addHours($hours)->addMinutes($minutes)->format("H:i");

            $startTimeInt = Carbon::parse($startTime)->floatDiffInHours(Carbon::parse("00:00:00"));

            $hours = floor($startTimeInt);
            $minutes = ($startTimeInt - $hours) * 60;

            $taskPeriodToMove['start_time'] = Carbon::parse($p)->startOfDay()->addHours($hours)->addMinutes($minutes)->format('Y-m-d H:i:s');

            $hours = floor($heureFinPeriodApresMidi);
            $minutes = ($heureFinPeriodApresMidi - $hours) * 60;

            $taskPeriodToMove['end_time'] = Carbon::parse($p)->startOfDay()->addHours($hours)->addMinutes($minutes)->format('Y-m-d H:i:s');

            array_push($listTaskPeriodToSave, $taskPeriodToMove['id']);
            array_push($listTaskPeriodToSave, $taskPeriodToMove['start_time']);
            array_push($listTaskPeriodToSave, $taskPeriodToMove['end_time']);

            $heureFinTaskPrecedente = $taskPeriodToMove['end_time'];

            $dureePeriodToMove -= Carbon::parse($taskPeriodToMove['end_time'])->floatDiffInHours(Carbon::parse($taskPeriodToMove['start_time']));

            $heuresDisposApresMidi -= Carbon::parse($taskPeriodToMove['end_time'])->floatDiffInHours(Carbon::parse($taskPeriodToMove['start_time']));

            $dureePeriodToMoveApresMidi = Carbon::parse($taskPeriodToMove['end_time'])->floatDiffInHours(Carbon::parse($taskPeriodToMove['start_time']));
            $dureePeriodToMoveMatin = 0;
        }

        //sinon on ajoute l'id de la task_period à la liste des tasks_period à supprimer
        else {

            array_push($listTaskPeriodToDelete, $taskPeriodToMove['id']);
            array_push($listTaskPeriodToDelete, $taskPeriodToMove['start_time']);
            array_push($listTaskPeriodToDelete, $taskPeriodToMove['end_time']);
        }

        $hours = floor($heureDebutPeriodMatin + $dureePeriodToMove - $heuresDisposApresMidi);
        $minutes = ($heureDebutPeriodMatin + $dureePeriodToMove - $heuresDisposApresMidi - $hours) * 60;

        $nbJour = $this->nbDaysNextWorkDay($p, $workHours, $listDebutTaskPeriodIndispo);
        // if($hoursWork[2] == "00:00:00" && $hoursWork[3] == "00:00:00"){
        //     $nbJour=0;
        // }
        $newHour = Carbon::parse($p)->startOfDay()->addHours($hours)->addMinutes($minutes)->format('H:i');

        //si on dépasse l'heure de fin de travail matin, on remplit le lendemain matin et on créé une nouvelle task_period pour le lendemain après-midi
        if ($newHour > $heureFinPeriodMatin) {

            $hoursDebutMatin = floor($heureDebutPeriodMatin);
            $minutesDebutMatin = ($heureDebutPeriodMatin - $hoursDebutMatin) * 60;

            $startTime = Carbon::parse($p)->startOfDay()->addHours($hoursDebutMatin)->addMinutes($minutesDebutMatin)->addDays($nbJour)->format('Y-m-d H:i:s');

            $hoursFinMatin = floor($heureFinPeriodMatin);
            $minutesFinMatin = ($heureFinPeriodMatin - $hoursFinMatin) * 60;

            $endTime = Carbon::parse($p)->startOfDay()->addHours($hoursFinMatin)->addMinutes($minutesFinMatin)->addDays($nbJour)->format('Y-m-d H:i:s');

            $dureePeriodToMoveMatin = Carbon::parse($endTime)->floatDiffInHours(Carbon::parse($startTime));

            array_push($listTaskPeriodToCreate, $taskPeriodToMove['task_id']);
            array_push($listTaskPeriodToCreate, $startTime);
            array_push($listTaskPeriodToCreate, $endTime);

            $heureFinTaskPrecedente = $endTime;
            $heuresDisposMatin = 0;
            $dureePeriodToMove -= Carbon::parse($endTime)->floatDiffInHours(Carbon::parse($startTime));

            $hours = floor($heureDebutPeriodApresMidi + $dureePeriodToMove - $heuresDisposMatin);
            $minutes = ($heureDebutPeriodApresMidi + $dureePeriodToMove - $heuresDisposMatin - $hours) * 60;

            $hoursDebutAM = floor($heureDebutPeriodApresMidi);
            $minutesDebutAM = ($heureDebutPeriodApresMidi - $hoursDebutAM) * 60;

            $startTime = Carbon::parse($p)->startOfDay()->addHours($hoursDebutAM)->addMinutes($minutesDebutAM)->addDays($nbJour)->format('Y-m-d H:i:s');

            $endTime = Carbon::parse($p)->startOfDay()->addHours($hours)->addMinutes($minutes)->addDays($nbJour)->format('Y-m-d H:i:s');

            array_push($listTaskPeriodToCreate, $taskPeriodToMove['task_id']);
            array_push($listTaskPeriodToCreate, $startTime);
            array_push($listTaskPeriodToCreate, $endTime);

            $heureFinTaskPrecedente = $endTime;
            $dureePeriodToMoveApresMidi = Carbon::parse($endTime)->floatDiffInHours(Carbon::parse($startTime));
        }

        //sinon on créé une nouvelle task_period avec le temps restant le lendemain matin
        else {

            $hours = floor($heureDebutPeriodMatin);
            $minutes = ($heureDebutPeriodMatin - $hours) * 60;

            $startTime = Carbon::parse($p)->startOfDay()->addHours($hours)->addMinutes($minutes)->addDays($nbJour)->format('Y-m-d H:i:s');

            $hours = floor($heureDebutPeriodMatin + $dureePeriodToMove - $heuresDisposApresMidi);
            $minutes = ($heureDebutPeriodMatin + $dureePeriodToMove - $heuresDisposApresMidi - $hours) * 60;

            $endTime = Carbon::parse($p)->startOfDay()->addHours($hours)->addMinutes($minutes)->addDays($nbJour)->format('Y-m-d H:i:s');

            array_push($listTaskPeriodToCreate, $taskPeriodToMove['task_id']);
            array_push($listTaskPeriodToCreate, $startTime);
            array_push($listTaskPeriodToCreate, $endTime);
            $heureFinTaskPrecedente = $endTime;
            $heuresDisposMatin -= Carbon::parse($endTime)->floatDiffInHours(Carbon::parse($startTime));
            $dureePeriodToMoveMatin = Carbon::parse($endTime)->floatDiffInHours(Carbon::parse($startTime));
            $dureePeriodToMoveApresMidi = 0;
        }


        $arrayInfos = array(
            "heureFinTaskPrecedente" => $heureFinTaskPrecedente,
            "dureePeriodToMoveMatin" => $dureePeriodToMoveMatin,
            "dureePeriodToMoveApresMidi" => $dureePeriodToMoveApresMidi,
            "listTaskPeriodToSave" => $listTaskPeriodToSave,
            "listTaskPeriodToCreate" => $listTaskPeriodToCreate,
            "listTaskPeriodToDelete" => $listTaskPeriodToDelete,
        );

        return $arrayInfos;
    }

    private function listDebutPeriodIndispo($taskPeriod, $task, $algo)
    {
        $orderTask = $task[0]['order'];
        $bundle_id = $task[0]['tasks_bundle_id'];
        $taskBundle = TasksBundle::where('id', $bundle_id)->get();
        $projectId = $taskBundle[0]['project_id'];
        $project = Project::where('id', $projectId)->get();
        $tasksProject = Task::where('tasks_bundle_id', $bundle_id)->whereNotNull('date')->whereNotNull('date_end')->get();
        //s'il faut avancer, la date vaut la date de fin de projet
        if ($algo == "next") {
            $date = $project[0]['date'];
        }
        //s'il faut reculer, la date vaut la date de début de projet
        else if ($algo == "before") {
            $date = $project[0]['start_date'];
        }
        //s'il faut avancer, récupérer la liste des id des tasks qui doivent être faites après la task sélectionnée
        $listIdTaskDependant = array();
        if ($orderTask != null && $algo == "next") {
            foreach ($tasksProject as $taskProject) {
                if ($taskProject['order'] != null && $taskProject['order'] > $orderTask) {
                    array_push($listIdTaskDependant, $taskProject['id']);
                }
            }
        }
        //s'il faut reculer, récupérer la liste des id des tasks qui doivent être faites avant la task sélectionnée
        else if ($orderTask != null && $algo == "before") {
            foreach ($tasksProject as $taskProject) {
                if ($taskProject['order'] != null && $taskProject['order'] < $orderTask) {
                    array_push($listIdTaskDependant, $taskProject['id']);
                }
            }
        }
        $user_id = $task[0]['user_id'];
        $workarea_id = $task[0]['workarea_id'];

        //récupérer toutes les tasks avec le même workarea_id
        $tasksWorkarea = Task::where('workarea_id', $workarea_id)->whereNotNull('date')->whereNotNull('date_end')->get();
        $listTaskId = array();
        //s'il y a au moins une task sur le même îlot
        if (count($tasksWorkarea) > 0) {
            //on récupère le max_users de l'îlot
            $workArea = Workarea::where('id', $workarea_id)->get();
            $max_users = $workArea[0]['max_users'];

            //on compte le nombre de tasks en même temps et sur le même îlot
            $nbTasksTogether = 1;
            $maxNbTasksTogether = 0;
            $listTaskTogether = array();
            $listTasksWorkarea = array();
            foreach ($tasksWorkarea as $taskWorkarea) {
                $periodTask = CarbonPeriod::create($taskWorkarea['date'], $taskWorkarea['date_end']);
                $nbTasksTogether = 1;
                array_push($listTasksWorkarea, $taskWorkarea);
                foreach ($tasksWorkarea as $taskworkarea) {
                    if (($taskworkarea != $taskWorkarea) && ($periodTask->contains($taskworkarea['date']) || $periodTask->contains($taskworkarea['date_end']))) {
                        $nbTasksTogether++;
                        if (!in_array($taskworkarea, $listTaskTogether)) {
                            array_push($listTaskTogether, $taskworkarea);
                        }
                        if (!in_array($taskWorkarea, $listTaskTogether)) {
                            array_push($listTaskTogether, $taskWorkarea);
                        }
                    }
                    if ($nbTasksTogether > $maxNbTasksTogether) {
                        $maxNbTasksTogether = $nbTasksTogether;
                    }
                }
            }
            $nbTasksTogether = $maxNbTasksTogether;
            //si le nombre de tasks en même temps sur l'îlot < max_users, on enlève la task de la liste des tasks avec le même workarea car période dispo
            $tasksWorkarea = Task::where('workarea_id', $workarea_id)->whereNotNull('date')->whereNotNull('date_end');
            if ($nbTasksTogether < $max_users) {
                foreach ($listTaskTogether as $taskTogether) {
                    $key = array_search($taskTogether, $listTasksWorkarea);
                    $tasksWorkarea = $tasksWorkarea->where('id', '!=', $listTasksWorkarea[$key]['id']);
                }
            }
        }
        $tasksWorkarea = $tasksWorkarea->get();

        //récupérer toutes les tasks avec le même user_id
        $tasksUser = Task::where('user_id', $user_id)->whereNotNull('date')->whereNotNull('date_end')->get();

        //récupérer tous les id des tasks avec le même user_id et tous les id des tasks avec le même workarea_id
        //sauf les id des tasks dépendantes car elles vont être déplacées

        if ($listIdTaskDependant != null) {
            if (!in_array($tasksWorkarea->first()->id, $listIdTaskDependant) && $tasksWorkarea->first()->id != $task[0]['id']) {
                array_push($listTaskId, $tasksWorkarea->first()->id);
            }
            foreach ($tasksWorkarea as $taskWorkarea) {
                if (!in_array($taskWorkarea->id, $listTaskId) && !in_array($taskWorkarea->id, $listIdTaskDependant) && ($taskWorkarea->id != $task[0]['id'])) {
                    array_push($listTaskId, $taskWorkarea->id);
                }
            }
            foreach ($tasksUser as $taskUser) {
                if (!in_array($taskUser->id, $listTaskId) && !in_array($taskUser->id, $listIdTaskDependant) && ($taskUser->id != $task[0]['id'])) {
                    array_push($listTaskId, $taskUser->id);
                }
            }
        } else {
            if ($tasksWorkarea->first()->id != $task[0]['id']) {
                array_push($listTaskId, $tasksWorkarea->first()->id);
            }
            foreach ($tasksWorkarea as $taskWorkarea) {
                if (!in_array($taskWorkarea->id, $listTaskId) && ($taskWorkarea->id != $task[0]['id'])) {
                    array_push($listTaskId, $taskWorkarea->id);
                }
            }
            foreach ($tasksUser as $taskUser) {
                if (!in_array($taskUser->id, $listTaskId) && ($taskUser->id != $task[0]['id'])) {
                    array_push($listTaskId, $taskUser->id);
                }
            }
        }

        //récupérer toutes les task_periods des tasks qui ont le même utilisateur ou le même workarea grâce à la liste des id
        $listTasksPeriod = array();
        foreach ($listTaskId as $taskId) {
            $item = TaskPeriod::where('task_id', $taskId)->get();
            if (!$item->isEmpty()) {
                array_push($listTasksPeriod, $item);
            }
        }
        $list = array();
        foreach ($listTasksPeriod as $items) {
            foreach ($items as $item) {
                array_push($list, $item);
            }
        }

        $listDebutTaskPeriodIndispo = array();
        foreach ($list as $taskPeriodIndispo) {
            array_push($listDebutTaskPeriodIndispo, Carbon::parse($taskPeriodIndispo['start_time'])->format('Y-m-d'));
        }

        return array(
            "listDebutTaskPeriodIndispo" => $listDebutTaskPeriodIndispo,
            "list" => $list,
            "listIdTaskDependant" => $listIdTaskDependant,
            "date" => $date,
        );
    }

    private function workHoursTask($taskIdTaskPeriodToMove, $taskPeriodToMove, $listUserId, $dayName, $heureNewPeriod, $algo)
    {

        if ($taskIdTaskPeriodToMove != $taskPeriodToMove['task_id']) {
            $taskIdTaskPeriodToMove = $taskPeriodToMove['task_id'];

            //on récupère user_id de la nouvelle task
            $newTask = Task::where('id', $taskIdTaskPeriodToMove)->get();
            $UserIdNewTask = $newTask[0]['user_id'];
            $listUserId[0] = $UserIdNewTask;

            $workHours = $this->workHoursUsers($listUserId);
        } else {
            $workHours = $this->workHoursUsers($listUserId);
        }
        $hoursWork = $workHours[$dayName];

        $heureDebutTravailMatin = Carbon::createFromFormat('H:i:s', $hoursWork[0])->format('H:i');
        $heureFinTravailMatin = Carbon::createFromFormat('H:i:s', $hoursWork[1])->format('H:i');
        $heureDebutTravailApresMidi = Carbon::createFromFormat('H:i:s', $hoursWork[2])->format('H:i');
        $heureFinTravailApresMidi = Carbon::createFromFormat('H:i:s', $hoursWork[3])->format('H:i');

        $heureDebutPeriodMatin = Carbon::parse($hoursWork[0])->floatDiffInHours(Carbon::parse("00:00:00"));
        $heureFinPeriodMatin = Carbon::parse($hoursWork[1])->floatDiffInHours(Carbon::parse("00:00:00"));
        $heureDebutPeriodApresMidi = Carbon::parse($hoursWork[2])->floatDiffInHours(Carbon::parse("00:00:00"));
        $heureFinPeriodApresMidi = Carbon::parse($hoursWork[3])->floatDiffInHours(Carbon::parse("00:00:00"));

        if ($algo == "next") {

            $heuresDisposMatin = Carbon::parse($heureFinTravailMatin)->floatDiffInHours($heureNewPeriod);
            // if($hoursWork[2] == "00:00:00" && $hoursWork[3] == "00:00:00"){
            //     $heuresDisposApresMidi=0;
            // }
            // else{
            if ($heureNewPeriod <= $heureDebutTravailApresMidi) {
                $heuresDisposApresMidi = Carbon::parse($heureFinTravailApresMidi)->floatDiffInHours($heureDebutTravailApresMidi);
            } else {
                $heuresDisposApresMidi = Carbon::parse($heureFinTravailApresMidi)->floatDiffInHours($heureNewPeriod);
            }
            //}
        } else if ($algo == "before") {

            $heuresDisposApresMidi = Carbon::parse($heureDebutTravailApresMidi)->floatDiffInHours($heureNewPeriod);
            // if($hoursWork[2] == "00:00:00" && $hoursWork[3] == "00:00:00"){
            //     $heuresDisposApresMidi=0;
            // }
            // else{
            //     $heuresDisposApresMidi = Carbon::parse($heureDebutTravailApresMidi)->floatDiffInHours($heureNewPeriod);
            // }
            if ($heureNewPeriod > $heureFinTravailMatin) {
                $heuresDisposMatin = Carbon::parse($heureFinTravailMatin)->floatDiffInHours($heureDebutTravailMatin);
            } else {
                $heuresDisposMatin = Carbon::parse($heureDebutTravailMatin)->floatDiffInHours($heureNewPeriod);
            }
        }

        $arrayworkHoursTask = array(
            "taskIdTaskPeriodToMove" => $taskIdTaskPeriodToMove,
            "workHours" => $workHours,
            "listUserId" => $listUserId,
            "heuresDisposMatin" => $heuresDisposMatin,
            "heuresDisposApresMidi" => $heuresDisposApresMidi,
            "heureDebutTravailMatin" => $heureDebutTravailMatin,
            "heureFinTravailMatin" => $heureFinTravailMatin,
            "heureDebutTravailApresMidi" => $heureDebutTravailApresMidi,
            "heureFinTravailApresMidi" => $heureFinTravailApresMidi,
            "heureDebutPeriodMatin" => $heureDebutPeriodMatin,
            "heureFinPeriodMatin" => $heureFinPeriodMatin,
            "heureDebutPeriodApresMidi" => $heureDebutPeriodApresMidi,
            "heureFinPeriodApresMidi" => $heureFinPeriodApresMidi,
        );

        return $arrayworkHoursTask;
    }

    private function addInlistTaskPeriodToMoveAndCreate($arrayInfos, $listTaskPeriodToSave, $listTaskPeriodToCreate, $listTaskPeriodToDelete)
    {
        for ($index = 0; $index < count($arrayInfos["listTaskPeriodToSave"]); $index += 3) {
            if (
                !in_array($arrayInfos["listTaskPeriodToSave"][$index], $listTaskPeriodToSave) &&
                !in_array($arrayInfos["listTaskPeriodToSave"][$index + 1], $listTaskPeriodToSave) ||
                !in_array($arrayInfos["listTaskPeriodToSave"][$index + 2], $listTaskPeriodToSave)
            ) {
                array_push($listTaskPeriodToSave, $arrayInfos["listTaskPeriodToSave"][$index]);
                array_push($listTaskPeriodToSave, $arrayInfos["listTaskPeriodToSave"][$index + 1]);
                array_push($listTaskPeriodToSave, $arrayInfos["listTaskPeriodToSave"][$index + 2]);
            }
        }
        for ($index = 0; $index < count($arrayInfos["listTaskPeriodToCreate"]); $index += 3) {
            if (
                !in_array($arrayInfos["listTaskPeriodToCreate"][$index + 1], $listTaskPeriodToCreate) &&
                !in_array($arrayInfos["listTaskPeriodToCreate"][$index + 2], $listTaskPeriodToCreate)
            ) {
                array_push($listTaskPeriodToCreate, $arrayInfos["listTaskPeriodToCreate"][$index]);
                array_push($listTaskPeriodToCreate, $arrayInfos["listTaskPeriodToCreate"][$index + 1]);
                array_push($listTaskPeriodToCreate, $arrayInfos["listTaskPeriodToCreate"][$index + 2]);
            }
        }
        for ($index = 0; $index < count($arrayInfos["listTaskPeriodToDelete"]); $index++) {
            if (!in_array($arrayInfos["listTaskPeriodToDelete"][$index], $listTaskPeriodToDelete)) {
                array_push($listTaskPeriodToDelete, $arrayInfos["listTaskPeriodToDelete"][$index]);
            }
        }
        return $list = array(
            "move" => $listTaskPeriodToSave,
            "create" => $listTaskPeriodToCreate,
            "delete" => $listTaskPeriodToDelete,
        );
    }

    private function nbDaysNextWorkDay($p, $workHours, $listDebutTaskPeriodIndispo)
    {
        $nbJour = 1;
        $dateP = Carbon::createFromFormat('Y-m-d H:i:s', $p)->addDays($nbJour)->format('Y-m-d');
        $dateLendemain = Carbon::createFromFormat('Y-m-d H:i:s', $p)->addDays($nbJour)->format('Y-m-d');
        $dayNameLendemain = Carbon::create($dateLendemain)->dayName;
        $hoursWorkLendemain = $workHours[$dayNameLendemain];

        while ((($hoursWorkLendemain[0] == "00:00:00" || $hoursWorkLendemain[0] == null) &&
                ($hoursWorkLendemain[1] == "00:00:00" || $hoursWorkLendemain[1] == null) &&
                ($hoursWorkLendemain[2] == "00:00:00" || $hoursWorkLendemain[2] == null) &&
                ($hoursWorkLendemain[3] == "00:00:00" || $hoursWorkLendemain[3] == null))
            || (in_array($dateP, $listDebutTaskPeriodIndispo))
        ) :

            $dateP = Carbon::createFromFormat('Y-m-d H:i:s', $p)->addDays($nbJour)->format('Y-m-d');

            $dateLendemain = Carbon::createFromFormat('Y-m-d H:i:s', $p)->addDays($nbJour)->format('Y-m-d');

            $dayNameLendemain = Carbon::create($dateLendemain)->dayName;

            $hoursWorkLendemain = $workHours[$dayNameLendemain];
            $nbJour++;

        endwhile;

        if ($nbJour > 1) {
            $nbJour--;
        }
        return $nbJour;
    }

    private function nbDaysBeforeWorkDay($p, $workHours, $listDebutTaskPeriodIndispo)
    {
        $nbJour = 1;
        $dateP = Carbon::createFromFormat('Y-m-d H:i:s', $p)->subDays($nbJour)->format('Y-m-d');
        $dateVeille = Carbon::createFromFormat('Y-m-d H:i:s', $p)->subDays($nbJour)->format('Y-m-d');
        $dayNameVeille = Carbon::create($dateVeille)->dayName;
        $hoursWorkVeille = $workHours[$dayNameVeille];

        while ((($hoursWorkVeille[0] == "00:00:00" || $hoursWorkVeille[0] == null) &&
                ($hoursWorkVeille[1] == "00:00:00" || $hoursWorkVeille[1] == null) &&
                ($hoursWorkVeille[2] == "00:00:00" || $hoursWorkVeille[2] == null) &&
                ($hoursWorkVeille[3] == "00:00:00" || $hoursWorkVeille[3] == null))
            || (in_array($dateP, $listDebutTaskPeriodIndispo))
        ) :

            $dateP = Carbon::createFromFormat('Y-m-d H:i:s', $p)->subDays($nbJour)->format('Y-m-d');

            $dateVeille = Carbon::createFromFormat('Y-m-d H:i:s', $p)->subDays($nbJour)->format('Y-m-d');

            $dayNameVeille = Carbon::create($dateVeille)->dayName;

            $hoursWorkVeille = $workHours[$dayNameVeille];
            $nbJour++;

        endwhile;

        if ($nbJour > 1) {
            $nbJour--;
        }
        return $nbJour;
    }

    private function mergeTaskPeriod($listTaskPeriodToMoveAndCreate)
    {
        //on fusionne les tasks_period de la même task et qui se suivent

        for ($i = 0; $i < count($listTaskPeriodToMoveAndCreate["move"]); $i = $i + 3) {
            //élément courant
            $date = Carbon::parse($listTaskPeriodToMoveAndCreate["move"][$i + 1])->format("Y-m-d");
            $startDate = Carbon::parse($listTaskPeriodToMoveAndCreate["move"][$i + 1])->format("H:i");
            $endDate = Carbon::parse($listTaskPeriodToMoveAndCreate["move"][$i + 2])->format("H:i");

            //on récupère l'id de la task courante
            $taskPeriodToMove = TaskPeriod::where('id', $listTaskPeriodToMoveAndCreate["move"][$i])->get();
            $taskId = $taskPeriodToMove[0]['task_id'];
            if ($i < count($listTaskPeriodToMoveAndCreate["move"]) - 3) {
                //élément suivant
                $dateSuivante = Carbon::parse($listTaskPeriodToMoveAndCreate["move"][$i + 4])->format("Y-m-d");
                $startDateSuivante = Carbon::parse($listTaskPeriodToMoveAndCreate["move"][$i + 4])->format("H:i");
                $endDateSuivante = Carbon::parse($listTaskPeriodToMoveAndCreate["move"][$i + 5])->format("H:i");

                //on récupère l'id de la task suivante
                $taskPeriodToMoveSuivante = TaskPeriod::where('id', $listTaskPeriodToMoveAndCreate["move"][$i + 3])->get();
                $taskIdSuivant = $taskPeriodToMoveSuivante[0]['task_id'];
                if ($date == $dateSuivante && $taskId == $taskIdSuivant) {
                    if ($startDate == $endDateSuivante) {

                        $newDateDebutTaskPeriod = $startDateSuivante;

                        $listTaskPeriodToMoveAndCreate["move"][$i + 1] = $listTaskPeriodToMoveAndCreate["move"][$i + 4];
                        $listTaskPeriodToMoveAndCreate["move"][$i + 5] = $listTaskPeriodToMoveAndCreate["move"][$i + 2];
                        // unset($listTaskPeriodToMoveAndCreate["move"][$i+3]);
                        // unset($listTaskPeriodToMoveAndCreate["move"][$i+4]);
                        // unset($listTaskPeriodToMoveAndCreate["move"][$i+5]);
                        // $i=$i+3;
                    } else if ($endDate == $startDateSuivante) {

                        $newDateFinTaskPeriod = $endDateSuivante;

                        $listTaskPeriodToMoveAndCreate["move"][$i + 2] = $listTaskPeriodToMoveAndCreate["move"][$i + 5];
                        $listTaskPeriodToMoveAndCreate["move"][$i + 4] = $listTaskPeriodToMoveAndCreate["move"][$i + 1];
                    }
                }
            }
        }

        $listTaskPeriodToDeleteDouble = array();
        //on enlève les doublons
        for ($i = 0; $i < count($listTaskPeriodToMoveAndCreate["move"]); $i = $i + 3) {
            //élément courant
            $startDate = Carbon::parse($listTaskPeriodToMoveAndCreate["move"][$i + 1])->format("Y-m-d H:i");
            $endDate = Carbon::parse($listTaskPeriodToMoveAndCreate["move"][$i + 2])->format("Y-m-d H:i");

            if ($i < count($listTaskPeriodToMoveAndCreate["move"]) - 3) {
                //élément suivant
                $startDateSuivante = Carbon::parse($listTaskPeriodToMoveAndCreate["move"][$i + 4])->format("Y-m-d H:i");
                $endDateSuivante = Carbon::parse($listTaskPeriodToMoveAndCreate["move"][$i + 5])->format("Y-m-d H:i");

                if ($startDate >= $startDateSuivante && $endDate <= $endDateSuivante) {
                    array_push($listTaskPeriodToDeleteDouble, $listTaskPeriodToMoveAndCreate["move"][$i]);
                }
            }
        }

        $lenghtListe = count($listTaskPeriodToMoveAndCreate["move"]);
        for ($i = 0; $i < $lenghtListe; $i = $i + 3) {
            //si l'id de la task_period est dans la liste des tasks_periods en double
            if (in_array($listTaskPeriodToMoveAndCreate["move"][$i], $listTaskPeriodToDeleteDouble)) {
                //on ajoute la task_period dans la liste des tasks_period à supprimer
                array_push($listTaskPeriodToMoveAndCreate["delete"], $listTaskPeriodToMoveAndCreate["move"][$i]);
                array_push($listTaskPeriodToMoveAndCreate["delete"], $listTaskPeriodToMoveAndCreate["move"][$i + 1]);
                array_push($listTaskPeriodToMoveAndCreate["delete"], $listTaskPeriodToMoveAndCreate["move"][$i + 2]);

                //on enlève la task_period dans la liste des tasks_period à déplacer
                unset($listTaskPeriodToMoveAndCreate["move"][$i]);
                unset($listTaskPeriodToMoveAndCreate["move"][$i + 1]);
                unset($listTaskPeriodToMoveAndCreate["move"][$i + 2]);
            }
        }

        $listTaskPeriodToUpdateBdd = array();
        foreach ($listTaskPeriodToMoveAndCreate["move"] as $taskPeriodToUpdate) {
            array_push($listTaskPeriodToUpdateBdd, $taskPeriodToUpdate);
        }

        for ($i = 0; $i < count($listTaskPeriodToUpdateBdd); $i = $i + 3) {

            //si la date de début de la task_period est dans la liste des tasks_periods à créer et même task
            if (in_array($listTaskPeriodToUpdateBdd[$i + 1], $listTaskPeriodToMoveAndCreate["create"])) {
                $key = array_search($listTaskPeriodToUpdateBdd[$i + 1], $listTaskPeriodToMoveAndCreate["create"]);

                $taskPeriodToMove = TaskPeriod::where('id', $listTaskPeriodToUpdateBdd[$i])->get();
                $taskId = $taskPeriodToMove[0]['task_id'];

                if ($listTaskPeriodToMoveAndCreate["create"][$key - 2] == $taskId) {
                    $newDateDebut = $listTaskPeriodToMoveAndCreate["create"][$key - 1];
                    $listTaskPeriodToUpdateBdd[$i + 1] = $newDateDebut;

                    //on enlève la task_period dans la liste des tasks_period à créer
                    unset($listTaskPeriodToMoveAndCreate["create"][$key - 2]);
                    unset($listTaskPeriodToMoveAndCreate["create"][$key - 1]);
                    unset($listTaskPeriodToMoveAndCreate["create"][$key]);
                }
            }

            //si la date de fin de la task_period est dans la liste des tasks_periods à créer et même task
            else if (in_array($listTaskPeriodToUpdateBdd[$i + 2], $listTaskPeriodToMoveAndCreate["create"])) {
                $key = array_search($listTaskPeriodToUpdateBdd[$i + 2], $listTaskPeriodToMoveAndCreate["create"]);

                $taskPeriodToMove = TaskPeriod::where('id', $listTaskPeriodToUpdateBdd[$i])->get();
                $taskId = $taskPeriodToMove[0]['task_id'];

                if ($listTaskPeriodToMoveAndCreate["create"][$key - 1] == $taskId) {
                    $newDateFin = $listTaskPeriodToMoveAndCreate["create"][$key + 1];
                    $listTaskPeriodToUpdateBdd[$i + 2] = $newDateFin;

                    //on enlève la task_period dans la liste des tasks_period à créer
                    unset($listTaskPeriodToMoveAndCreate["create"][$key - 1]);
                    unset($listTaskPeriodToMoveAndCreate["create"][$key]);
                    unset($listTaskPeriodToMoveAndCreate["create"][$key + 1]);
                }
            }
        }

        $listTaskPeriodToCreateBdd = array();
        foreach ($listTaskPeriodToMoveAndCreate["create"] as $taskPeriodToCreate) {
            array_push($listTaskPeriodToCreateBdd, $taskPeriodToCreate);
        }

        //on met à jour les tasks_period
        for ($i = 0; $i < count($listTaskPeriodToUpdateBdd); $i = $i + 3) {

            $taskToUpdate = TaskPeriod::where('id', $listTaskPeriodToUpdateBdd[$i])->get();

            TaskPeriod::where('id', $listTaskPeriodToUpdateBdd[$i])->update([
                'start_time' => $listTaskPeriodToUpdateBdd[$i + 1],
                'end_time' => $listTaskPeriodToUpdateBdd[$i + 2],
            ]);
        }
        //on crée les tasks_period s'il y en a à créer
        if (count($listTaskPeriodToCreateBdd) > 0) {
            for ($i = 0; $i < count($listTaskPeriodToCreateBdd); $i = $i + 3) {

                TaskPeriod::create([
                    'task_id' => $listTaskPeriodToCreateBdd[$i],
                    'start_time' => $listTaskPeriodToCreateBdd[$i + 1],
                    'end_time' => $listTaskPeriodToCreateBdd[$i + 2],
                ]);
            }
        }
        //on supprime les tasks_period s'il y en a à supprimer
        if (count($listTaskPeriodToMoveAndCreate["delete"]) > 0) {
            for ($i = 0; $i < count($listTaskPeriodToMoveAndCreate["delete"]); $i = $i + 3) {

                $taskToDelete = TaskPeriod::where('id', $listTaskPeriodToMoveAndCreate["delete"][$i])
                    /*->where('start_time', $listTaskPeriodToMoveAndCreate["delete"][$i+1])
                            ->where('end_time', $listTaskPeriodToMoveAndCreate["delete"][$i+2])*/->get();

                $taskToDelete = TaskPeriod::where('id', $listTaskPeriodToMoveAndCreate["delete"][$i])->delete();
            }
        }
    }

    public function addRange(Request $request, int $id)
    {
        $item = Range::find($id)->load('repetitive_tasks');
        if ($error = $this->itemErrors($item, 'edit')) {
            return $error;
        }


        $arrayRequest = $request->all();
        $prefix = $arrayRequest['prefix'];
        $project_id = $arrayRequest['project_id'];
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
            $this->storeDocuments($task, $repetitive_task->documents->pluck('id'));
        }

        $items = Task::where('tasks_bundle_id', $taskBundle->id)->with('workarea', 'skills', 'comments', 'previousTasks', 'documents', 'project')->get();
        return $this->successResponse($items, 'Ajout de gamme terminé avec succès.');
    }

    private function storeSkills(int $task_id, $skills)
    {
        if (count($skills) > 0 && $task_id) {
            foreach ($skills as $skill) {
                TasksSkill::create(['task_id' => $task_id, 'skill_id' => $skill->id]);
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

    public function start(Request $request, int $id)
    {
        $project = Project::find($id);
        if ($error = $this->itemErrors($project, 'edit')) {
            return $error;
        }

        $arrayRequest = $request->all();
        $project->start_date = $arrayRequest['start_date'];
        $users = User::where('company_id', $project->company_id)->with('workHours')->with('unavailabilities', 'skills')->get();
        $workareas = Workarea::where('company_id', $project->company_id)->with('skills')->get();

        // Alertes pour l'utilisateur
        if ($error = $this->checkForStartErrors($project, $users, $workareas)) {
            return $error;
        }

        $nbHoursRequired = 0;
        $nbHoursAvailable = 0;
        $nbHoursUnvailable = 0;

        foreach ($project->tasks as $task) {
            // Hours required
            $nbHoursRequired += $task->estimated_time;
        }

        // Hours Available & Hours Unavailable
        $timeData = $this->calculTimeAvailable($users, $project, $users);

        if ($timeData['total_hours_available'] < $nbHoursRequired) {
            return $this->errorResponse("Le nombre d'heure de travail disponible est insuffisant pour démarrer le projet. Vueillez modifier la date de livraison du projet.");
        }

        return $this->setDateToTasks($project->tasks, $timeData, $users, $project);
    }

    private function checkForStartErrors($project, $users, $workareas)
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
        $workareasHaveSkills ? null : $alerts[] = "Au moins une compétence utilisée dans une tâche n'est pas associée à un pôle de production";

        if (count($alerts) > 0) {
            return $this->errorResponse($alerts[0]);
        }

        return null;
    }

    public function unavailablePeriods(Request $request)
    {

        if ($request->type == "projects" || $request->type == "workarea") {
            //ajouter dans la liste les périodes entre le début et la fin des tasks dépendantes
            //+ les périodes où le nombre de tasks en même temps sur l'ilot >= max_users
            $listTaskPeriods = $this->unavailabilities($request);

            //ajouter dans la liste les tasks attribuées au même utilisateur que celui de la task sélectionnée
            $task = Task::where('id', $request->task_id)->get();
            $user_id = $task[0]['user_id'];
            $tasksUserId = Task::where('user_id', $user_id)->get();
            foreach ($tasksUserId as $keyUser => $taskUserId) {
                if ($taskUserId['id'] != $request->task_id && $taskUserId['date'] != null && $taskUserId['date_end'] != null) {
                    foreach ($listTaskPeriods as $keyTask => $task) {
                        if ($taskUserId['date'] != $task['date'] && $taskUserId['date_end'] != $task['date_end']) {
                            //si la task avec le même workarea_id déborde sur une task à faire avant ou après la task,
                            //la date de fin de la task avec le même workarea_id vaut la date de début de la task à faire
                            if ($taskUserId['date'] <= $task['date'] && $taskUserId['date_end'] <= $task['date_end'] && $taskUserId['date_end'] > $task['date']) {
                                $taskUserId['date_end'] = $task['date'];
                            }
                            //si la task avec le même workarea_id recouvre entièrement une task à faire, supprimer la task à faire dans la liste
                            else if ($taskUserId['date'] <= $task['date'] && $taskUserId['date_end'] >= $task['date_end']) {
                                unset($listTaskPeriods[$keyTask]);
                            }
                            //si la task avec le même workarea_id est recouverte entièrement par une task à faire, supprimer la task avec le même workarea_id dans la liste
                            else if ($taskUserId['date'] >= $task['date'] && $taskUserId['date_end'] <= $task['date_end']) {
                                unset($tasksUserId[$keyUser]);
                            }
                            //si la task avec le même workarea_id n'est pas finie avant une task à faire avant ou après la task,
                            //la date de début de la task avec le même workarea_id vaut la date de fin de la task à faire
                            else if ($taskUserId['date'] >= $task['date'] && $taskUserId['date_end'] >= $task['date_end'] && $taskUserId['date'] < $task['date_end']) {
                                $taskUserId['date'] = $task['date_end'];
                            }
                        }
                    }
                }
            }
            //on ajoute dans la liste toutes les tasks modifiées ou non
            foreach ($tasksUserId as $key => $taskUserId) {
                if ($taskUserId['id'] != $request->task_id && $taskUserId['date'] != null && $taskUserId['date_end'] != null) {
                    // foreach($listTaskPeriods as $key => $task){
                    //     if(!in_array($taskUserId, $listTaskPeriods) && ($taskUserId['date'] != $taskUserId['date_end'])){
                    //         $controllerLog = new Logger('hours');
                    //         $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')),Logger::INFO);
                    //         $controllerLog->info('push',[$taskUserId]);
                    //         array_push($listTaskPeriods,$taskUserId);
                    //     }
                    // }
                    if (!empty($listTaskPeriods)) {
                        foreach ($listTaskPeriods as $keyTask => $task) {

                            if (!in_array($taskUserId, $listTaskPeriods) && ($taskUserId['date'] != $taskUserId['date_end'])) {
                                array_push($listTaskPeriods, $taskUserId);
                            }
                        }
                    } else {
                        if (!in_array($taskUserId, $listTaskPeriods) && ($taskUserId['date'] != $taskUserId['date_end'])) {
                            array_push($listTaskPeriods, $taskUserId);
                        }
                    }
                }
            }
            $list = array();
            foreach ($listTaskPeriods as $listTaskPeriod) {
                array_push($list, $listTaskPeriod);
            }
        } else if ($request->type == "users") {
            //ajouter dans la liste les périodes entre le début et la fin des tasks dépendantes
            //+ les périodes où le nombre de tasks en même temps sur l'ilot >= max_users
            $list = $this->unavailabilities($request);
        }
        //on renvoie côté vue la liste des périodes à griser
        try {
            return $this->successResponse($list, 'Chargement terminé avec succès.');
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage(), static::$response_codes['error_server']);
        }
    }

    private function unavailabilities(Request $request)
    {
        $listTaskPeriods = array();

        //récupérer toutes les task du projet et ajouter toutes les autres task du projet qui ont un order
        //car elles doivent être faites avant ou après la task sélectionnée
        $task = Task::where('id', $request->task_id)->get();
        $orderTask = $task[0]['order'];
        $tasks_bundle_id = $task[0]['tasks_bundle_id'];
        $tasksProject = Task::where('tasks_bundle_id', $tasks_bundle_id)->get();
        foreach ($tasksProject as $taskProject) {
            if ($taskProject['id'] != $request->task_id) {
                $order = $taskProject['order'];
                if ($order != null) {
                    array_push($listTaskPeriods, $taskProject);
                }
            }
        }

        //récupérer toutes les tasks qui ont le même workarea_id que la task sélectionnée
        $workareaId = $task[0]['workarea_id'];
        $workarea = Workarea::where('id', $workareaId)->get();
        $max_users = $workarea[0]['max_users'];
        $tasksWorkArea = Task::where('workarea_id', $workareaId)->get();
        $nbTasks = 1;
        //si max_users du workarea = 1 -> ajouter la task à la liste des événements à griser
        foreach ($tasksWorkArea as $keyWh => $taskWorkArea) {
            if (
                $max_users == 1 && $taskWorkArea['date'] != null && $taskWorkArea['date_end'] != null
                && $taskWorkArea['id'] != $request->task_id
            ) {
                foreach ($listTaskPeriods as $keyTask => $task) {
                    //if($taskWorkArea['date'] != $task['date'] && $taskWorkArea['date_end'] != $task['date_end']){

                    // $controllerLog = new Logger('hours');
                    //         $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')),Logger::INFO);
                    //         $controllerLog->info('$taskWorkArea[date]',[$taskWorkArea['date']]);
                    //         $controllerLog = new Logger('hours');
                    //         $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')),Logger::INFO);
                    //         $controllerLog->info('$task[date]',[$task['date']]);
                    //         $controllerLog = new Logger('hours');
                    //         $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')),Logger::INFO);
                    //         $controllerLog->info('$taskWorkArea[date_end]',[$taskWorkArea['date_end']]);
                    //         $controllerLog = new Logger('hours');
                    //         $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')),Logger::INFO);
                    //         $controllerLog->info('$task[date_end]',[$task['date_end']]);
                    //si la task avec le même workarea_id déborde sur une task à faire avant ou après la task,
                    //la date de fin de la task avec le même workarea_id vaut la date de début de la task à faire
                    if ($taskWorkArea['date'] <= $task['date'] && $taskWorkArea['date_end'] <= $task['date_end'] && $taskWorkArea['date_end'] > $task['date']) {
                        $taskWorkArea['date_end'] = $task['date'];
                    }
                    //si la task avec le même workarea_id recouvre entièrement une task à faire, supprimer la task à faire dans la liste
                    else if ($taskWorkArea['date'] <= $task['date'] && $taskWorkArea['date_end'] >= $task['date_end']) {
                        unset($listTaskPeriods[$keyTask]);
                    }
                    //si la task avec le même workarea_id est recouverte entièrement par une task à faire, supprimer la task avec le même workarea_id dans la liste
                    else if ($taskWorkArea['date'] >= $task['date'] && $taskWorkArea['date_end'] <= $task['date_end']) {
                        unset($tasksWorkArea[$keyWh]);
                    }
                    //si la task avec le même workarea_id n'est pas finie avant une task à faire avant ou après la task,
                    //la date de début de la task avec le même workarea_id vaut la date de fin de la task à faire
                    else if ($taskWorkArea['date'] >= $task['date'] && $taskWorkArea['date_end'] >= $task['date_end'] && $taskWorkArea['date'] < $task['date_end']) {
                        $taskWorkArea['date'] = $task['date_end'];
                    }
                    // //on ajoute dans la liste toutes les tasks modifiées ou non
                    // if(!in_array($taskWorkArea, $listTaskPeriods) && ($taskWorkArea['date'] != $taskWorkArea['date_end'])){
                    //     array_push($listTaskPeriods,$taskWorkArea);
                    // }

                    //}
                }
            }

            //si max_users > 1 -> compter le nombre de tasks en même temps
            else if (
                $max_users > 1 && $taskWorkArea['date'] != null && $taskWorkArea['date_end'] != null
                && $taskWorkArea['id'] != $request->task_id
            ) {
                //test task 5729
                // if($taskWorkArea['date'] == "2021-03-08 09:00:00"){

                //     $taskWorkArea['date']="2021-04-02 10:00:00";
                //     $taskWorkArea['date_end']="2021-04-04 10:00:00";

                // }
                // else if($taskWorkArea['date'] == "2021-03-30 15:00:00"){

                //     $taskWorkArea['date']="2021-04-06 10:00:00";
                //     $taskWorkArea['date_end']="2021-04-10 15:00:00";

                // }
                foreach ($tasksWorkArea as $keyWh => $taskWorkarea) {
                    if ($taskWorkarea['date'] > $taskWorkArea['date'] && $taskWorkarea['date_end'] < $taskWorkArea['date_end']) {
                        $nbTasks++;
                        //si le nombre de tasks à faire en même temps sur le workarea est supérieur au max_users du workarea,
                        // ajouter les périodes dans la liste des événements à griser
                        if ($nbTasks >= $max_users) {
                            //on ajoute dans la liste toutes les tasks modifiées ou non
                            foreach ($listTaskPeriods as $keyTask => $task) {
                                //si la task avec le même workarea_id déborde sur une task à faire avant ou après la task,
                                //la date de fin de la task avec le même workarea_id vaut la date de début de la task à faire
                                if ($taskWorkArea['date'] < $task['date'] && $taskWorkArea['date_end'] < $task['date_end'] && $taskWorkArea['date_end'] > $task['date']) {
                                    $taskWorkArea['date_end'] = $task['date'];
                                }
                                //si la task avec le même workarea_id recouvre entièrement une task à faire, supprimer la task à faire dans la liste
                                else if ($taskWorkArea['date'] <= $task['date'] && $taskWorkArea['date_end'] >= $task['date_end']) {
                                    unset($listTaskPeriods[$keyTask]);
                                }
                                //si la task avec le même workarea_id est recouverte entièrement par une task à faire, supprimer la task avec le même workarea_id dans la liste
                                else if ($taskWorkArea['date'] >= $task['date'] && $taskWorkArea['date_end'] <= $task['date_end']) {
                                    unset($tasksWorkArea[$keyWh]);
                                }
                                //si la task avec le même workarea_id n'est pas finie avant une task à faire avant ou après la task,
                                //la date de début de la task avec le même workarea_id vaut la date de fin de la task à faire
                                else if ($taskWorkArea['date'] >= $task['date'] && $taskWorkArea['date_end'] >= $task['date_end'] && $taskWorkArea['date'] < $task['date_end']) {
                                    $taskWorkArea['date'] = $task['date_end'];
                                }
                                // //on ajoute dans la liste toutes les tasks modifiées ou non
                                // if(!in_array($taskWorkArea, $listTaskPeriods) && ($taskWorkArea['date'] != $taskWorkArea['date_end'])){
                                //     array_push($listTaskPeriods,$taskWorkArea);
                                // }

                            }
                        }
                    }
                }
            }
        }
        //on ajoute dans la liste toutes les tasks modifiées ou non
        foreach ($tasksWorkArea as $keyWh => $taskWorkArea) {
            if ($taskWorkArea['date'] != null && $taskWorkArea['date_end'] != null && $taskWorkArea['id'] != $request->task_id) {
                if (!empty($listTaskPeriods)) {
                    foreach ($listTaskPeriods as $keyTask => $task) {

                        if (!in_array($taskWorkArea, $listTaskPeriods) && ($taskWorkArea['date'] != $taskWorkArea['date_end'])) {
                            // $controllerLog = new Logger('hours');
                            // $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')),Logger::INFO);
                            // $controllerLog->info('push 1',[$taskWorkArea]);
                            array_push($listTaskPeriods, $taskWorkArea);
                        }
                    }
                } else {
                    if (!in_array($taskWorkArea, $listTaskPeriods) && ($taskWorkArea['date'] != $taskWorkArea['date_end'])) {
                        // $controllerLog = new Logger('hours');
                        // $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')),Logger::INFO);
                        // $controllerLog->info('push 1',[$taskWorkArea]);
                        array_push($listTaskPeriods, $taskWorkArea);
                    }
                }
            }
        }
        $list = array();
        foreach ($listTaskPeriods as $listTaskPeriod) {
            array_push($list, $listTaskPeriod);
        }
        return $list;
    }

    private function workHoursUsers($listUserId)
    {
        $workHoursLundi = array(
            "morning_start" => array(),
            "morning_end" => array(),
            "afternoon_start" => array(),
            "afternoon_end" => array(),
        );
        $morning_starts_at_max_Lundi = "00:00:00";
        $morning_ends_at_min_Lundi = "23:59:59";
        $afternoon_starts_at_max_Lundi = "00:00:00";
        $afternoon_ends_at_min_Lundi = "23:59:59";

        $workHoursMardi = array(
            "morning_start" => array(),
            "morning_end" => array(),
            "afternoon_start" => array(),
            "afternoon_end" => array(),
        );
        $morning_starts_at_max_Mardi = "00:00:00";
        $morning_ends_at_min_Mardi = "23:59:59";
        $afternoon_starts_at_max_Mardi = "00:00:00";
        $afternoon_ends_at_min_Mardi = "23:59:59";

        $workHoursMercredi = array(
            "morning_start" => array(),
            "morning_end" => array(),
            "afternoon_start" => array(),
            "afternoon_end" => array(),
        );
        $morning_starts_at_max_Mercredi = "00:00:00";
        $morning_ends_at_min_Mercredi = "23:59:59";
        $afternoon_starts_at_max_Mercredi = "00:00:00";
        $afternoon_ends_at_min_Mercredi = "23:59:59";

        $workHoursJeudi = array(
            "morning_start" => array(),
            "morning_end" => array(),
            "afternoon_start" => array(),
            "afternoon_end" => array(),
        );
        $morning_starts_at_max_Jeudi = "00:00:00";
        $morning_ends_at_min_Jeudi = "23:59:59";
        $afternoon_starts_at_max_Jeudi = "00:00:00";
        $afternoon_ends_at_min_Jeudi = "23:59:59";

        $workHoursVendredi = array(
            "morning_start" => array(),
            "morning_end" => array(),
            "afternoon_start" => array(),
            "afternoon_end" => array(),
        );
        $morning_starts_at_max_Vendredi = "00:00:00";
        $morning_ends_at_min_Vendredi = "23:59:59";
        $afternoon_starts_at_max_Vendredi = "00:00:00";
        $afternoon_ends_at_min_Vendredi = "23:59:59";

        $workHoursSamedi = array(
            "morning_start" => array(),
            "morning_end" => array(),
            "afternoon_start" => array(),
            "afternoon_end" => array(),
        );
        $morning_starts_at_max_Samedi = "00:00:00";
        $morning_ends_at_min_Samedi = "23:59:59";
        $afternoon_starts_at_max_Samedi = "00:00:00";
        $afternoon_ends_at_min_Samedi = "23:59:59";

        $workHoursDimanche = array(
            "morning_start" => array(),
            "morning_end" => array(),
            "afternoon_start" => array(),
            "afternoon_end" => array(),
        );
        $morning_starts_at_max_Dimanche = "00:00:00";
        $morning_ends_at_min_Dimanche = "23:59:59";
        $afternoon_starts_at_max_Dimanche = "00:00:00";
        $afternoon_ends_at_min_Dimanche = "23:59:59";

        for ($i = 0; $i < count($listUserId); $i++) {
            $user_workHours = Workhours::where('user_id', $listUserId[$i])->where('day', 'lundi')->get();
            if(sizeof($user_workHours)>0){
                $user_workHours = $user_workHours[0];
                array_push($workHoursLundi["morning_start"], $user_workHours['morning_starts_at']);
                array_push($workHoursLundi["morning_end"], $user_workHours['morning_ends_at']);
                array_push($workHoursLundi["afternoon_start"], $user_workHours['afternoon_starts_at']);
                array_push($workHoursLundi["afternoon_end"], $user_workHours['afternoon_ends_at']);
            }
            else{
                array_push($workHoursLundi["morning_start"], NULL);
                array_push($workHoursLundi["morning_end"], NULL);
                array_push($workHoursLundi["afternoon_start"], NULL);
                array_push($workHoursLundi["afternoon_end"], NULL);
            }

            $user_workHours = Workhours::where('user_id', $listUserId[$i])->where('day', 'mardi')->get();
            if(sizeof($user_workHours)>0){
                $user_workHours = $user_workHours[0];
                array_push($workHoursMardi["morning_start"], $user_workHours['morning_starts_at']);
                array_push($workHoursMardi["morning_end"], $user_workHours['morning_ends_at']);
                array_push($workHoursMardi["afternoon_start"], $user_workHours['afternoon_starts_at']);
                array_push($workHoursMardi["afternoon_end"], $user_workHours['afternoon_ends_at']);
            }
            else{
                array_push($workHoursMardi["morning_start"], NULL);
                array_push($workHoursMardi["morning_end"], NULL);
                array_push($workHoursMardi["afternoon_start"], NULL);
                array_push($workHoursMardi["afternoon_end"], NULL); 
            }

            $user_workHours = Workhours::where('user_id', $listUserId[$i])->where('day', 'mercredi')->get();
            if(sizeof($user_workHours)>0){
                $user_workHours = $user_workHours[0];
                array_push($workHoursMercredi["morning_start"], $user_workHours['morning_starts_at']);
                array_push($workHoursMercredi["morning_end"], $user_workHours['morning_ends_at']);
                array_push($workHoursMercredi["afternoon_start"], $user_workHours['afternoon_starts_at']);
                array_push($workHoursMercredi["afternoon_end"], $user_workHours['afternoon_ends_at']);
            }
            else{
                array_push($workHoursMercredi["morning_start"], NULL);
                array_push($workHoursMercredi["morning_end"], NULL);
                array_push($workHoursMercredi["afternoon_start"], NULL);
                array_push($workHoursMercredi["afternoon_end"], NULL); 
            }

            $user_workHours = Workhours::where('user_id', $listUserId[$i])->where('day', 'jeudi')->get();
            if(sizeof($user_workHours)>0){
                $user_workHours = $user_workHours[0];
                array_push($workHoursJeudi["morning_start"], $user_workHours['morning_starts_at']);
                array_push($workHoursJeudi["morning_end"], $user_workHours['morning_ends_at']);
                array_push($workHoursJeudi["afternoon_start"], $user_workHours['afternoon_starts_at']);
                array_push($workHoursJeudi["afternoon_end"], $user_workHours['afternoon_ends_at']);
            }
            else{
                array_push($workHoursJeudi["morning_start"], NULL);
                array_push($workHoursJeudi["morning_end"], NULL);
                array_push($workHoursJeudi["afternoon_start"], NULL);
                array_push($workHoursJeudi["afternoon_end"], NULL); 
            }

            $user_workHours = Workhours::where('user_id', $listUserId[$i])->where('day', 'vendredi')->get();
            if(sizeof($user_workHours)>0){
                $user_workHours = $user_workHours[0];
                array_push($workHoursVendredi["morning_start"], $user_workHours['morning_starts_at']);
                array_push($workHoursVendredi["morning_end"], $user_workHours['morning_ends_at']);
                array_push($workHoursVendredi["afternoon_start"], $user_workHours['afternoon_starts_at']);
                array_push($workHoursVendredi["afternoon_end"], $user_workHours['afternoon_ends_at']);
            }
            else{
                array_push($workHoursVendredi["morning_start"], NULL);
                array_push($workHoursVendredi["morning_end"], NULL);
                array_push($workHoursVendredi["afternoon_start"], NULL);
                array_push($workHoursVendredi["afternoon_end"], NULL); 
            }

            $user_workHours = Workhours::where('user_id', $listUserId[$i])->where('day', 'samedi')->get();
            if(sizeof($user_workHours)>0){
                $user_workHours = $user_workHours[0];
                array_push($workHoursSamedi["morning_start"], $user_workHours['morning_starts_at']);
                array_push($workHoursSamedi["morning_end"], $user_workHours['morning_ends_at']);
                array_push($workHoursSamedi["afternoon_start"], $user_workHours['afternoon_starts_at']);
                array_push($workHoursSamedi["afternoon_end"], $user_workHours['afternoon_ends_at']);
            }
            else{
                array_push($workHoursSamedi["morning_start"], NULL);
                array_push($workHoursSamedi["morning_end"], NULL);
                array_push($workHoursSamedi["afternoon_start"], NULL);
                array_push($workHoursSamedi["afternoon_end"], NULL);
            }
            
            $user_workHours = Workhours::where('user_id', $listUserId[$i])->where('day', 'dimanche')->get();
            if(sizeof($user_workHours)>0){
                $user_workHours = $user_workHours[0];
                array_push($workHoursDimanche["morning_start"], $user_workHours['morning_starts_at']);
                array_push($workHoursDimanche["morning_end"], $user_workHours['morning_ends_at']);
                array_push($workHoursDimanche["afternoon_start"], $user_workHours['afternoon_starts_at']);
                array_push($workHoursDimanche["afternoon_end"], $user_workHours['afternoon_ends_at']);
            }
            else{
                array_push($workHoursDimanche["morning_start"], NULL);
                array_push($workHoursDimanche["morning_end"], NULL);
                array_push($workHoursDimanche["afternoon_start"], NULL);
                array_push($workHoursDimanche["afternoon_end"], NULL);
            }
        }

        //$workHours=$workHours[0];
        foreach ($workHoursLundi["morning_start"] as $wh) {
            $wh > $morning_starts_at_max_Lundi ? $morning_starts_at_max_Lundi = $wh : $morning_starts_at_max_Lundi;
        }
        foreach ($workHoursLundi["morning_end"] as $wh) {
            $wh < $morning_ends_at_min_Lundi ? $morning_ends_at_min_Lundi = $wh : $morning_ends_at_min_Lundi;
        }
        foreach ($workHoursLundi["afternoon_start"] as $wh) {
            $wh > $afternoon_starts_at_max_Lundi ? $afternoon_starts_at_max_Lundi = $wh : $afternoon_starts_at_max_Lundi;
        }
        foreach ($workHoursLundi["afternoon_end"] as $wh) {
            $wh < $afternoon_ends_at_min_Lundi ? $afternoon_ends_at_min_Lundi = $wh : $afternoon_ends_at_min_Lundi;
        }

        foreach ($workHoursMardi["morning_start"] as $wh) {
            $wh > $morning_starts_at_max_Mardi ? $morning_starts_at_max_Mardi = $wh : $morning_starts_at_max_Mardi;
        }
        foreach ($workHoursMardi["morning_end"] as $wh) {
            $wh < $morning_ends_at_min_Mardi ? $morning_ends_at_min_Mardi = $wh : $morning_ends_at_min_Mardi;
        }
        foreach ($workHoursMardi["afternoon_start"] as $wh) {
            $wh > $afternoon_starts_at_max_Mardi ? $afternoon_starts_at_max_Mardi = $wh : $afternoon_starts_at_max_Mardi;
        }
        foreach ($workHoursMardi["afternoon_end"] as $wh) {
            $wh < $afternoon_ends_at_min_Mardi ? $afternoon_ends_at_min_Mardi = $wh : $afternoon_ends_at_min_Mardi;
        }

        foreach ($workHoursMercredi["morning_start"] as $wh) {
            $wh > $morning_starts_at_max_Mercredi ? $morning_starts_at_max_Mercredi = $wh : $morning_starts_at_max_Mercredi;
        }
        foreach ($workHoursMercredi["morning_end"] as $wh) {
            $wh < $morning_ends_at_min_Mercredi ? $morning_ends_at_min_Mercredi = $wh : $morning_ends_at_min_Mercredi;
        }
        foreach ($workHoursMercredi["afternoon_start"] as $wh) {
            $wh > $afternoon_starts_at_max_Mercredi ? $afternoon_starts_at_max_Mercredi = $wh : $afternoon_starts_at_max_Mercredi;
        }
        foreach ($workHoursMercredi["afternoon_end"] as $wh) {
            $wh < $afternoon_ends_at_min_Mercredi ? $afternoon_ends_at_min_Mercredi = $wh : $afternoon_ends_at_min_Mercredi;
        }

        foreach ($workHoursJeudi["morning_start"] as $wh) {
            $wh > $morning_starts_at_max_Jeudi ? $morning_starts_at_max_Jeudi = $wh : $morning_starts_at_max_Jeudi;
        }
        foreach ($workHoursJeudi["morning_end"] as $wh) {
            $wh < $morning_ends_at_min_Jeudi ? $morning_ends_at_min_Jeudi = $wh : $morning_ends_at_min_Jeudi;
        }
        foreach ($workHoursJeudi["afternoon_start"] as $wh) {
            $wh > $afternoon_starts_at_max_Jeudi ? $afternoon_starts_at_max_Jeudi = $wh : $afternoon_starts_at_max_Jeudi;
        }
        foreach ($workHoursJeudi["afternoon_end"] as $wh) {
            $wh < $afternoon_ends_at_min_Jeudi ? $afternoon_ends_at_min_Jeudi = $wh : $afternoon_ends_at_min_Jeudi;
        }

        foreach ($workHoursVendredi["morning_start"] as $wh) {
            $wh > $morning_starts_at_max_Vendredi ? $morning_starts_at_max_Vendredi = $wh : $morning_starts_at_max_Vendredi;
        }
        foreach ($workHoursVendredi["morning_end"] as $wh) {
            $wh < $morning_ends_at_min_Vendredi ? $morning_ends_at_min_Vendredi = $wh : $morning_ends_at_min_Vendredi;
        }
        foreach ($workHoursVendredi["afternoon_start"] as $wh) {
            $wh > $afternoon_starts_at_max_Vendredi ? $afternoon_starts_at_max_Vendredi = $wh : $afternoon_starts_at_max_Vendredi;
        }
        foreach ($workHoursVendredi["afternoon_end"] as $wh) {
            $wh < $afternoon_ends_at_min_Vendredi ? $afternoon_ends_at_min_Vendredi = $wh : $afternoon_ends_at_min_Vendredi;
        }

        foreach ($workHoursSamedi["morning_start"] as $wh) {
            $wh > $morning_starts_at_max_Samedi ? $morning_starts_at_max_Samedi = $wh : $morning_starts_at_max_Samedi;
        }
        foreach ($workHoursSamedi["morning_end"] as $wh) {
            $wh < $morning_ends_at_min_Samedi ? $morning_ends_at_min_Samedi = $wh : $morning_ends_at_min_Samedi;
        }
        foreach ($workHoursSamedi["afternoon_start"] as $wh) {
            $wh > $afternoon_starts_at_max_Samedi ? $afternoon_starts_at_max_Samedi = $wh : $afternoon_starts_at_max_Samedi;
        }
        foreach ($workHoursSamedi["afternoon_end"] as $wh) {
            $wh < $afternoon_ends_at_min_Samedi ? $afternoon_ends_at_min_Samedi = $wh : $afternoon_ends_at_min_Samedi;
        }

        foreach ($workHoursDimanche["morning_start"] as $wh) {
            $wh > $morning_starts_at_max_Dimanche ? $morning_starts_at_max_Dimanche = $wh : $morning_starts_at_max_Dimanche;
        }
        foreach ($workHoursDimanche["morning_end"] as $wh) {
            $wh < $morning_ends_at_min_Dimanche ? $morning_ends_at_min_Dimanche = $wh : $morning_ends_at_min_Dimanche;
        }
        foreach ($workHoursDimanche["afternoon_start"] as $wh) {
            $wh > $afternoon_starts_at_max_Dimanche ? $afternoon_starts_at_max_Dimanche = $wh : $afternoon_starts_at_max_Dimanche;
        }
        foreach ($workHoursDimanche["afternoon_end"] as $wh) {
            $wh < $afternoon_ends_at_min_Dimanche ? $afternoon_ends_at_min_Dimanche = $wh : $afternoon_ends_at_min_Dimanche;
        }
        $workHours = array(
            'lundi' => array($morning_starts_at_max_Lundi, $morning_ends_at_min_Lundi, $afternoon_starts_at_max_Lundi, $afternoon_ends_at_min_Lundi),
            'mardi' => array($morning_starts_at_max_Mardi, $morning_ends_at_min_Mardi, $afternoon_starts_at_max_Mardi, $afternoon_ends_at_min_Mardi),
            'mercredi' => array($morning_starts_at_max_Mercredi, $morning_ends_at_min_Mercredi, $afternoon_starts_at_max_Mercredi, $afternoon_ends_at_min_Mercredi),
            'jeudi' => array($morning_starts_at_max_Jeudi, $morning_ends_at_min_Jeudi, $afternoon_starts_at_max_Jeudi, $afternoon_ends_at_min_Jeudi),
            'vendredi' => array($morning_starts_at_max_Vendredi, $morning_ends_at_min_Vendredi, $afternoon_starts_at_max_Vendredi, $afternoon_ends_at_min_Vendredi),
            'samedi' => array($morning_starts_at_max_Samedi, $morning_ends_at_min_Samedi, $afternoon_starts_at_max_Samedi, $afternoon_ends_at_min_Samedi),
            'dimanche' => array($morning_starts_at_max_Dimanche, $morning_ends_at_min_Dimanche, $afternoon_starts_at_max_Dimanche, $afternoon_ends_at_min_Dimanche),
        );
        return $workHours;
    }

    public function workHoursPeriods(Request $request)
    {
        try {
            //vue read planning -> horaires en commun des utilisateurs
            if ($request->task_id == null) {
                if ($request->type == "projects") {
                    //horaires en commun des utilisateurs sur le projet
                    $id = $request->id;
                    $project = Project::find($id);
                    $arrayRequest = $request->all();

                    //skills des tâches du projet :
                    $tasks_bundle = TasksBundle::where('project_id', $id)->get();
                    $tasks = Task::where('tasks_bundle_id', $tasks_bundle[0]->id)->get();
                    $listUserId = array();
                    $listTaskIdProject = array();
                    $skills = array();

                    if ($tasks != null) {
                        //liste des id des utilisateurs du projet
                        if ($tasks->first()->user_id != null) {
                            array_push($listUserId, $tasks->first()->user_id);
                        }
                        foreach ($tasks as $task) {
                            if (!in_array($task->user_id, $listUserId) && $task->user_id != null) {
                                array_push($listUserId, $task->user_id);
                            }
                        }
                    }
                } else if ($request->type == "workarea") {
                    //horaires en commun des utilisateurs qui ont les compétences du workarea
                    $id = $request->id;
                    $workareasSkill = WorkareasSkill::where('workarea_id', $id)->get();

                    $skills = array();
                    foreach ($workareasSkill as $workareaSkill) {
                        array_push($skills, $workareaSkill['skill_id']);
                    }
                    //liste des id des utilisateurs qui ont les compétences du workarea
                    $listUserId = array();
                    foreach ($skills as $skill) {
                        $usersSkill = UsersSkill::where('skill_id', $skill)->get();
                        foreach ($usersSkill as $userSkill) {
                            $item = WorkHours::where('user_id', $userSkill['user_id'])->get();
                            if (!$item->isEmpty() && !in_array($userSkill['user_id'], $listUserId)) {
                                array_push($listUserId, $userSkill['user_id']);
                            }
                        }
                    }
                }
                $workHours = $this->workHoursUsers($listUserId);
            }
            //sinon horaires de l'utilisateur
            else {
                if ($request->type == "projects") {
                    $taskCourante = Task::where('id', $request->task_id)->get();
                    $listUserId = array();

                    //liste des id des utilisateurs du projet
                    if ($taskCourante[0]['user_id'] != null) {
                        array_push($listUserId, $taskCourante[0]['user_id']);
                    }
                } else if ($request->type == "workarea") {
                    //horaires en commun des utilisateurs qui ont les compétences du workarea
                    $id = $request->id;
                    $workareasSkill = WorkareasSkill::where('workarea_id', $id)->get();

                    $skills = array();
                    foreach ($workareasSkill as $workareaSkill) {
                        array_push($skills, $workareaSkill['skill_id']);
                    }
                    //liste des id des utilisateurs qui ont les compétences du workarea
                    $listUserId = array();
                    foreach ($skills as $skill) {
                        $usersSkill = UsersSkill::where('skill_id', $skill)->get();
                        foreach ($usersSkill as $userSkill) {
                            $item = WorkHours::where('user_id', $userSkill['user_id'])->get();
                            if (!$item->isEmpty() && !in_array($userSkill['user_id'], $listUserId)) {
                                array_push($listUserId, $userSkill['user_id']);
                            }
                        }
                    }
                }
                $workHours = $this->workHoursUsers($listUserId);
            }
            //on renvoie côté vue la liste des heures de travail en commun pour chaque jour
            return $this->successResponse($workHours, 'Chargement terminé avec succès.');
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage(), static::$response_codes['error_server']);
        }
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

                                        //On regarde si un îlot est disponible pendant la période
                                        $workareaOk = null;
                                        $workareas = $this->getWorkareasBySkills($task->skills, $project->company_id);

                                        foreach ($workareas as $workarea) {
                                            if (!$taskPlan) {
                                                $tasksWorkarea = Task::where('workarea_id', $workarea->id)->whereNotNull('date')->whereNotNull('date_end')->where('status', '!=', 'done')->get();

                                                //s'il y a plusieurs tasks sur le même îlot
                                                if (count($tasksWorkarea) > 0) {
                                                    //on récupère le max_users de l'îlot
                                                    $workArea = Workarea::where('id', $workarea->id)->get();
                                                    $max_users = $workArea[0]['max_users'];
                                                    //on compte le nombre de tasks en même temps et sur le même îlot
                                                    $nbTasksTogether = 1;
                                                    $maxNbTasksTogether = 0;
                                                    foreach ($tasksWorkarea as $taskWorkarea) {
                                                        $periodTask = CarbonPeriod::create($taskWorkarea['date'], $taskWorkarea['date_end']);
                                                        $nbTasksTogether = 1;
                                                        foreach ($tasksWorkarea as $taskworkarea) {
                                                            if (($taskworkarea != $taskWorkarea) && ($periodTask->contains($taskworkarea['date']) || $periodTask->contains($taskworkarea['date_end']))) {
                                                                $nbTasksTogether++;
                                                            }
                                                            if ($nbTasksTogether > $maxNbTasksTogether) {
                                                                $maxNbTasksTogether = $nbTasksTogether;
                                                            }
                                                        }
                                                    }
                                                    $nbTasksTogether = $maxNbTasksTogether;
                                                    //si le nombre de tasks en même temps sur l'îlot >= max_users, on planifie en dehors des périodes où il y a déjà des tasks sur l'îlot
                                                    if ($nbTasksTogether >= $max_users) {
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
                                                    }
                                                    //sinon on planifie sur les mêmes périodes
                                                    else {
                                                        $taskPlan = true;
                                                        $planifiedTask = $this->planTask($task, $period, $workarea->id);
                                                        $tasksTemp[$keytask] = Task::findOrFail($task->id);
                                                        $start_date_project = $start_date_project == null || $start_date_project > $planifiedTask->date ? $planifiedTask->date : $start_date_project;
                                                        $nbTasksTogether++;
                                                        if ($nbTasksTogether == $max_users) {
                                                            //On supprime les periodes utilisées
                                                            $available_periods_temp = $this->delUsedPeriods($available_periods, $key, $planifiedTask);
                                                        }
                                                        break;
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
            $countPlanified = 0;
            $taskIds = [];

            foreach ($tasksTemp as $taskTemp) {
                array_push($taskIds, $taskTemp->id);

                if (!$taskTemp->date || !$taskTemp->date_end || !$taskTemp->user_id) {
                    $allPlanified = false;
                }
            }

            // En cas d'erreur, on annule les changements et on retourne une erreur
            if (!$allPlanified) {
                Task::whereIn('id', $taskIds)->update(['date' => null, 'date_end' => null, 'user_id' => null, 'workarea_id' => null]);
                throw new Exception("Les plages de travails disponibles sont insuffisantes pour démarrer le projet. Veuillez modifier la date de livraison.");
            }

            // Si toutes les taches ont été planifié, on passe le projet en `doing` et on return success
            Project::findOrFail($project->id)->update(['status' => 'doing', 'start_date' => $start_date_project]);
        } catch (\Throwable $th) {
            //On déprogramme toutes les taches du projet
            $ids = [];
            foreach ($tasks as $task) {
                array_push($ids, $task->id);
            }

            Task::whereIn('id', $ids)->update(['date' => null, 'date_end' => null, 'user_id' => null, 'workarea_id' => null]);
            TaskPeriod::whereIn('task_id', $ids)->delete();

            return $this->errorResponse($th->getMessage());
        }

        return $this->successResponse(true, 'Projet démarré avec succès.');
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
            ->where(function ($query) use ($date) {
                $query->where('date', 'like', '%' . $date->format('Y-m-d') . '%');
                $query->orWhere('date_end', 'like', '%' . $date->format('Y-m-d') . '%');
                $query->orWhere(function ($query) use ($date) {
                    $query->where('date', '<', $date);
                    $query->where('date_end', '>', $date);
                });
            })
            ->get();

        if (count($tasks) > 0) {

            foreach ($tasks as $task) {
                if ($task->periods) {

                    foreach ($task->periods as $period) {
                        if (str_contains($period->start_time, $date->format('Y-m-d')) != false) {

                            $otherTaskPeriod = [
                                'start_time' => Carbon::createFromFormat('Y-m-d H:i:s', $period->start_time),
                                'end_time' => Carbon::createFromFormat('Y-m-d H:i:s', $period->end_time),
                                'period' => CarbonPeriod::create($period->start_time, $period->end_time)
                            ];

                            $tasks_periods[] = $otherTaskPeriod;
                        }
                    }
                }
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
                $testa = $unavailableHours['start_time']->toDateString();
                $testb = $unavailableHours['end_time']->toDateString();

                $test1 = $work_period->contains($unavailableHours['start_time']);
                $test2 = $work_period->contains($unavailableHours['end_time']);

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

                    $test = 'test';
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

                $testa = $hour['start_time']->toDateString();
                $testb = $hour['end_time']->toDateString();

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

                //On transforme la periode pour prendre en compte les taches de l'îlot afin d'avoir les dispo
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
