<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use App\User;
use App\Models\Range;
use App\Models\Task;
use App\Models\TasksSkill;
use App\Models\PreviousTask;
use App\Models\TasksBundle;
use Illuminate\Support\Facades\Auth;
use Validator;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Carbon\CarbonPeriod;


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

        foreach($item->repetitive_tasks as $repetitive_task){
            $task = Task::create([
                'name' => $prefix. ' - ' .$repetitive_task->name,
                'order' => $repetitive_task->order,
                'description' => $repetitive_task->description,
                'estimated_time' => $repetitive_task->estimated_time,
                'tasks_bundle_id' => $taskBundle->id,
                'created_by' => $user->id,
                'workarea_id' => $repetitive_task->workarea_id,
                'status' => 'todo',
            ]);
            isset($tasksArrayByOrder[$task->order]) ?
            array_push($tasksArrayByOrder[$task->order], $task->id) : $tasksArrayByOrder[$task->order] = [$task->id] ;
            
            $key = array_search($task->order, array_keys($tasksArrayByOrder));
            array_push($test, ['name_key' => $key]);
            $key > 0 ? $this->attributePreviousTask($tasksArrayByOrder, $key, $task->id) : '';

            $this->storeSkills($task->id, $repetitive_task->skills);
        }
        
        $items = Task::where('tasks_bundle_id', $taskBundle->id)->with('workarea', 'skills', 'comments', 'previousTasks')->get();
        return response()->json(['success' => $items], $this-> successStatus); 
    }

    private function storeSkills(int $task_id, $skills){
        if(count($skills) > 0 && $task_id){
            foreach ($skills as $skill) {
                TasksSkill::create(['task_id' => $task_id, 'skill_id' => $skill->id]);
            } 
        }
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

    private function attributePreviousTask($tasksArrayByOrder, $key, $taskId){
        $keys = array_keys($tasksArrayByOrder);
        foreach ($tasksArrayByOrder[$keys[$key-1]] as $previousTaskId){
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

    /**
     * forceDelete the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function forceDelete($id)
    {
        $item = Project::findOrFail($id);
        $item->delete();

        $item = Project::withTrashed()->findOrFail($id);
        $item->forceDelete();
        return '';
    }

    public function start($id){

        $project = Project::find($id);
        $nbHoursRequired = 0; 
        $nbHoursAvailable = 0;
        $nbHoursUnvailable = 0;

        foreach($project->tasks as $task){
            // Hours required
            $nbHoursRequired += $task->estimated_time;
        }

        $users = User::where('company_id', $project->company_id)->with('workHours')->with('unavailabilities')->get();
     
        // Hours Available & Hours Unavailable
        return $TimeData = $this->calculTimeAvailable($users, $project->date);



        $response = [
            'nbHoursRequired' => $nbHoursRequired,
            'nbHoursAvailable' => $TimeData['total_hours'],
            'nbHoursUnvailable' => $TimeData['total_hours_unavailable'],
            'details' => $TimeData,
            'users' => $users,
        ];

        return response()->json(['success' => $response], $this-> successStatus);  
    }


    private function calculTimeAvailable($users, $date_end){
        $hoursAvailable = [];

        // Get days today date -> end date
        $start_date = Carbon::now()->addDays('1')->startOfDay();
        $end_date = Carbon::createFromFormat('Y-m-d H:i:s', $date_end)->subDays('1')->endOfDay();

        $period = CarbonPeriod::create($start_date, $end_date);

        foreach($period as $t){
            $hoursAvailable[] = [
                'date' => $t,
                'day_label' => $t->format('l')
            ];
        }

        $totalHours = 0;     
        $totalHoursUnavailable = 0;     

        // foreach days foreach users get day get hours
        foreach($hoursAvailable as $key => $data){
            $test[] = $data['day_label']; 
            $totalDateHours = 0;
            switch (true) {
                case ($data['day_label'] == 'Monday' || $data['day_label'] == 'Lundi'):

                    $dayHours = $this->getHoursAvailableByDay('lundi', $data['date'], $users);
                    //Raf already task in this date

                    if($dayHours){
                        $totalHours += $dayHours['total'];
                        $totalHoursUnavailable += $dayHours['total_unavailable'];
                        $data['users'] = $dayHours['users'];
                        $data['total_hours'] = $dayHours['total'];
                        $data['total_hours_unavailable'] = $dayHours['total_unavailable'];

                        $hoursAvailable[$key] = $data;
                    }
                    break;

                case ($data['day_label'] == 'Tuesday' || $data['day_label'] == 'Mardi'):

                    $dayHours = $this->getHoursAvailableByDay('mardi', $data['date'], $users);
                    //Raf already task in this date

                    if($dayHours){
                        $totalHours += $dayHours['total'];
                        $totalHoursUnavailable += $dayHours['total_unavailable'];
                        $data['users'] = $dayHours['users'];
                        $data['total_hours'] = $dayHours['total'];
                        $data['total_hours_unavailable'] = $dayHours['total_unavailable'];

                        $hoursAvailable[$key] = $data;
                    }
                    break;

                case ($data['day_label'] == 'Wednesday' || $data['day_label'] == 'Mercredi'):

                    $dayHours = $this->getHoursAvailableByDay('mercredi', $data['date'], $users);
                    //Raf already task in this date

                    if($dayHours){
                        $totalHours += $dayHours['total'];
                        $totalHoursUnavailable += $dayHours['total_unavailable'];
                        $data['users'] = $dayHours['users'];
                        $data['total_hours'] = $dayHours['total'];
                        $data['total_hours_unavailable'] = $dayHours['total_unavailable'];

                        $hoursAvailable[$key] = $data;
                    }
                    break;

                case ($data['day_label'] == 'Thursday' || $data['day_label'] == 'Jeudi'):

                    $dayHours = $this->getHoursAvailableByDay('jeudi', $data['date'], $users);
                    //Raf already task in this date
    
                    if($dayHours){
                        $totalHours += $dayHours['total'];
                        $totalHoursUnavailable += $dayHours['total_unavailable'];
                        $data['users'] = $dayHours['users'];
                        $data['total_hours'] = $dayHours['total'];
                        $data['total_hours_unavailable'] = $dayHours['total_unavailable'];

                        $hoursAvailable[$key] = $data;
                    }
                    break;

                case ($data['day_label'] == 'Friday' || $data['day_label'] == 'Vendredi'):

                    $dayHours = $this->getHoursAvailableByDay('vendredi', $data['date'], $users);
                    //Raf already task in this date

                    if($dayHours){
                        $totalHours += $dayHours['total'];
                        $totalHoursUnavailable += $dayHours['total_unavailable'];
                        $data['users'] = $dayHours['users'];
                        $data['total_hours'] = $dayHours['total'];
                        $data['total_hours_unavailable'] = $dayHours['total_unavailable'];

                        $hoursAvailable[$key] = $data;
                    }
                    break;

                case ($data['day_label'] == 'Saturday' || $data['day_label'] == 'Samedi'):

                    $dayHours = $this->getHoursAvailableByDay('samedi', $data['date'], $users);
                    //Raf already task in this date

                    if($dayHours){
                        $totalHours += $dayHours['total'];
                        $totalHoursUnavailable += $dayHours['total_unavailable'];
                        $data['users'] = $dayHours['users'];
                        $data['total_hours'] = $dayHours['total'];
                        $data['total_hours_unavailable'] = $dayHours['total_unavailable'];

                        $hoursAvailable[$key] = $data;
                    }
                    break;

                case ($data['day_label'] == 'Sunday' || $data['day_label'] == 'Dimanche'):

                    $dayHours = $this->getHoursAvailableByDay('dimanche', $data['date'], $users);
                    //Raf already task in this date

                    if($dayHours){
                        $totalHours += $dayHours['total'];
                        $totalHoursUnavailable += $dayHours['total_unavailable'];
                        $data['users'] = $dayHours['users'];
                        $data['total_hours'] = $dayHours['total'];
                        $data['total_hours_unavailable'] = $dayHours['total_unavailable'];

                        $hoursAvailable[$key] = $data;
                    }
                    break;
                }

            $totalHours += $totalDateHours;   
        }

        $hoursAvailable['total_hours'] = $totalHours;
        $hoursAvailable['total_hours_unavailable'] = $totalHoursUnavailable;
        
        return $hoursAvailable;
    }

    private function getHoursAvailableByDay($day, $date, $users){
        $hours = [
            'total' => 0,
            'total_unavailable' => 0,
            'users' => []
        ];
        $conflictUnavailableDate = false;

        foreach($users as $user){
            $userHours = [
                'user_id' => $user->id,
                'hours' => 0,
                'hours_unavailable' => 0
            ];
            
            $unAvailablePeriods = count($user->unavailabilities) > 0? $this->transformDatesToPeriod($user->unavailabilities) : null;

            foreach($user->workHours as $dayHours){

                if($dayHours->day == $day && (string)$dayHours->is_active){

                    //Hours available
                    if($dayHours->morning_ends_at && $dayHours->morning_starts_at){
                        $morningEnd = Carbon::createFromFormat('Y-m-d H:i:s', '2000-01-01' . ' ' .$dayHours->morning_ends_at);
                        $morningStart = Carbon::createFromFormat('Y-m-d H:i:s', '2000-01-01' . ' ' .$dayHours->morning_starts_at);

                        $userHours['hours'] += Carbon::parse($morningEnd)->floatDiffInHours(Carbon::parse($morningStart));
                    }
                    if($dayHours->afternoon_ends_at && $dayHours->afternoon_starts_at){
                        $AfternoonEnd = Carbon::createFromFormat('Y-m-d H:i:s', '2000-01-01' . ' ' .$dayHours->afternoon_ends_at);
                        $AfternoonStart = Carbon::createFromFormat('Y-m-d H:i:s', '2000-01-01' . ' ' .$dayHours->afternoon_starts_at);

                        $userHours['hours'] += Carbon::parse($AfternoonEnd)->floatDiffInHours(Carbon::parse($AfternoonStart));
                    }

                    //Hours unavailable
                    if($unAvailablePeriods){

                        $workDate = Carbon::createFromFormat('Y-m-d H:i:s', $date);
                        $hoursUnavailableByPeriod = [];

                        foreach($unAvailablePeriods as $period){
                            
                            //On regarde si la période ne comprend que une seule journée
                            if(count($period['period']) == 1){

                                $unavailableDate = $period['period']->getStartDate();

                                if($unavailableDate == $workDate){

                                    //On calcul le nombre d'heures d'indisponibilité
                                    $hoursUnavailableByPeriod[] = $this->calculNbHoursUnavailable($dayHours, $period);
                                }
                            } 
                            else{ //plusieurs journées
                                $startDate = $period['period']->getStartDate();
                                $endDate = $period['period']->getEndDate();

                                foreach($period['period'] as $periodDay){
  
                                    if($periodDay == $workDate){
                                        if($workDate == $startDate){
                                            $hoursUnavailableByPeriod[] = $this->calculNbHoursUnavailablePeriod($dayHours, $periodDay, $period['start_date']);
                                        }else if($workDate == $endDate){
                                            $hoursUnavailableByPeriod[] = $this->calculNbHoursUnavailablePeriod($dayHours, $periodDay, null, $period['end_date']);
                                        }else{
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
                }
            }

            if($userHours['hours'] != 0){
                $hours['total'] += $userHours['hours'];
                $hours['total_unavailable'] += $userHours['hours_unavailable'];
                $hours['users'][] = $userHours;
            }
        }
        return empty($hours) ? null : $hours;
    }

    private function calculNbHoursUnavailable($dayHours, $period, $hours_unavailable = null){

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
        if($dayHours->morning_ends_at && $dayHours->morning_starts_at){
            $morningEnd = Carbon::createFromFormat('Y-m-d H:i:s', $period['period']->getStartDate()->format('Y-m-d'). ' ' .$dayHours->morning_ends_at);
            $morningStart = Carbon::createFromFormat('Y-m-d H:i:s', $period['period']->getStartDate()->format('Y-m-d'). ' ' .$dayHours->morning_starts_at);

            //Matin
            if($period['start_date'] < $morningEnd){
                $startMorningUnavailable = null;
                $endMorningUnavailable = null;

                //On regarde si la l'heure du début de l'indisponibilité ne commence pas avant l'horaire d'embauche du matin
                if($period['start_date'] > $morningStart){
                    $startMorningUnavailable = $period['start_date'];
                }
                else{ //Sinon, on prend l'heure d'embauche
                    $startMorningUnavailable = $morningStart;
                }

                //On regarde si la l'heure de fin de l'indisponibilité ne commence pas après l'horaire de débauche du midi
                if($period['end_date'] <= $morningEnd){
                    $endMorningUnavailable = $period['end_date'];
                }
                else{ //Sinon, on prend de débauche du midi.
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

        if($dayHours->afternoon_ends_at && $dayHours->afternoon_starts_at){
            $AfternoonEnd = Carbon::createFromFormat('Y-m-d H:i:s', $period['period']->getStartDate()->format('Y-m-d'). ' ' .$dayHours->afternoon_ends_at);
            $AfternoonStart = Carbon::createFromFormat('Y-m-d H:i:s', $period['period']->getStartDate()->format('Y-m-d'). ' ' .$dayHours->afternoon_starts_at);

            //Après midi
            if($period['end_date'] > $AfternoonStart){
                $startAfternoonUnavailable = null;
                $endAfternoonUnavailable = null;

                //On regarde si la l'heure du début de l'indisponibilité ne commence pas avant l'horaire d'embauche d'après-midi
                if($period['start_date'] > $AfternoonStart){
                    $startAfternoonUnavailable = $period['start_date'];
                }
                else{ //Sinon, on prend l'heure d'embauche d'après-midi
                    $startAfternoonUnavailable = $AfternoonStart;
                }

                //On regarde si la l'heure de fin de l'indisponibilité ne commence pas après l'horaire de débauche du soir
                if($period['end_date'] <= $AfternoonEnd){
                    $endAfternoonUnavailable = $period['end_date'];
                }
                else{ //Sinon, on prend de débauche du midi.
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


    private function calculNbHoursUnavailablePeriod($dayHours, $day, $fisrt_date = null, $last_date = null){

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
        if($dayHours->morning_ends_at && $dayHours->morning_starts_at){
            $morningEnd = Carbon::createFromFormat('Y-m-d H:i:s', $day->format('Y-m-d'). ' ' .$dayHours->morning_ends_at);
            $morningStart = Carbon::createFromFormat('Y-m-d H:i:s', $day->format('Y-m-d'). ' ' .$dayHours->morning_starts_at);

            //Matin
            if($startDateTime < $morningEnd){
                $startMorningUnavailable = null;
                $endMorningUnavailable = null;

                //On regarde si la l'heure du début de l'indisponibilité ne commence pas avant l'horaire d'embauche du matin
                if($startDateTime > $morningStart){
                    $startMorningUnavailable = $startDateTime;
                }
                else{ //Sinon, on prend l'heure d'embauche
                    $startMorningUnavailable = $morningStart;
                }

                //On regarde si la l'heure de fin de l'indisponibilité ne commence pas après l'horaire de débauche du midi
                if($endDateTime <= $morningEnd){
                    $endMorningUnavailable = $endDateTime;
                }
                else{ //Sinon, on prend de débauche du midi.
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

        if($dayHours->afternoon_ends_at && $dayHours->afternoon_starts_at){
            $AfternoonEnd = Carbon::createFromFormat('Y-m-d H:i:s', $day->format('Y-m-d'). ' ' .$dayHours->afternoon_ends_at);
            $AfternoonStart = Carbon::createFromFormat('Y-m-d H:i:s', $day->format('Y-m-d'). ' ' .$dayHours->afternoon_starts_at);

            //Après midi
            if($endDateTime > $AfternoonStart){
                $startAfternoonUnavailable = null;
                $endAfternoonUnavailable = null;

                //On regarde si la l'heure du début de l'indisponibilité ne commence pas avant l'horaire d'embauche d'après-midi
                if($startDateTime > $AfternoonStart){
                    $startAfternoonUnavailable = $startDateTime;
                }
                else{ //Sinon, on prend l'heure d'embauche d'après-midi
                    $startAfternoonUnavailable = $AfternoonStart;
                }

                //On regarde si la l'heure de fin de l'indisponibilité ne commence pas après l'horaire de débauche du soir
                if($endDateTime <= $AfternoonEnd){
                    $endAfternoonUnavailable = $endDateTime;
                }
                else{ //Sinon, on prend de débauche du soir.
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

    private function mergeHoursUnavailable($hoursUnavailableByPeriod){

        //On regarde si la journée contient une ou plusieurs indisponnibilités
        if(count($hoursUnavailableByPeriod) == 1){
            $response = $hoursUnavailableByPeriod[0];
        }
        else{
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

            foreach($hoursUnavailableByPeriod as $hours){

                if($hours['morning']['hours'] != 0){
                    $hoursUnavailable['morning']['hours'] += $hours['morning']['hours'];
                    $hoursUnavailable['morning']['periods'][] = $hours['morning']['periods'];
                }

                if($hours['afternoon']['hours'] != 0){
                    $hoursUnavailable['afternoon']['hours'] += $hours['afternoon']['hours'];
                    $hoursUnavailable['afternoon']['periods'][] = $hours['afternoon']['periods'];
                }
            }
            $response = $hoursUnavailable;
        }
        return $response;

    }
 
    private function transformDatesToPeriod($unavailabilities){

        if(isset($unavailabilities[0])){
            $periods = [];
            foreach($unavailabilities as $unavailability){
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
        }
        else{
            return false;
        }
    }

}