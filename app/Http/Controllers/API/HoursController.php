<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Models\Hours;
use App\Models\Unavailability;
use App\Models\DealingHours;
use App\Models\Project;
use App\Models\WorkHours;
use App\Models\TaskTimeSpent;
use App\User;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Auth;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
class HoursController extends BaseApiController
{
    protected static $index_load = ['project:projects.id,name,color', 'user:users.id,firstname,lastname,email,company_id'];
    protected static $index_append = null;
    protected static $show_load = ['project:projects.id,name,color', 'user:users.id,firstname,lastname,email,company_id'];
    protected static $show_append = null;

    protected static $store_validation_array = [
        'user_id' => 'required',
        'project_id' => 'required',
        'start_at' => 'required',
        'end_at' => 'required',
        'date' => 'required',
        'description' => 'nullable'
    ];

    protected static $update_validation_array = [
        'user_id' => 'required',
        'project_id' => 'required',
        'start_at' => 'required',
        'end_at' => 'required',
        'description' => 'nullable'
    ];

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        parent::__construct(Hours::class);
    }

    protected function extraIndexData(Request $request, $items, $nonPaginatedQuery)
    {
        $allItems = $nonPaginatedQuery->get();
        $stats = [];
        $user = Auth::user();
        $paidHolidays = ($user->is_admin || $user->is_manager) ? Unavailability::where('reason', 'Congés payés') : Unavailability::where('user_id', $user->id)->where('reason', "Congés payés");
        $schoolPeriods = ($user->is_admin || $user->is_manager) ? Unavailability::where('reason', 'Période de cours') : Unavailability::where('user_id', $user->id)->where('reason', "Période de cours");
        $publicHolidays = ($user->is_admin || $user->is_manager) ? Unavailability::where('reason', 'Jours fériés') : Unavailability::where('user_id', $user->id)->where('reason', "Jours fériés");
        $overtimesUsed = ($user->is_admin || $user->is_manager) ? Unavailability::where('reason', 'Utilisation heures supplémentaires') : Unavailability::where('user_id', $user->id)->where('reason', "Utilisation heures supplémentaires");
        
        /*$typeIndispo=array("Heures supplémentaires payées",
                            "Utilisation heures supplémentaires",
                            "Jours fériés",
                            "Rendez-vous privé",
                            "Congés payés",
                            "Période de cours",
                            "Arrêt de travail");
        $other = ($user->is_admin || $user->is_manager) ? Unavailability::whereNotIn('reason', $typeIndispo) : Unavailability::where('user_id', $user->id)->whereNotIn('reason', $typeIndispo);*/

        if ($request->has('project_id')) {
            if (Project::where('id', $request->project_id)->doesntExist()) {
                throw new ApiException("Paramètre 'project_id' n'est pas valide.");
            }
            //récupérer les utilisateurs qui ont enregistré des heures pour ce projet
            $hoursProject = Hours::where('project_id', $request->project_id)->get();
            $listUserIdProject = array();
            //array_push($listUserIdProject, $hoursProject[0]['user_id']);
            foreach ($hoursProject as $hourProject) {
                if (!in_array($hourProject['user_id'], $listUserIdProject)) {
                    array_push($listUserIdProject, $hourProject['user_id']);
                }
            }
        }

        if ($request->has('user_id')) {
            if (User::where('id', $request->user_id)->doesntExist()) {
                throw new ApiException("Paramètre 'user_id' n'est pas valide.");
            }
            $paidHolidays->where('user_id', $request->user_id);
            $schoolPeriods->where('user_id', $request->user_id);
            $publicHolidays->where('user_id', $request->user_id);
            $overtimesUsed->where('user_id', $request->user_id);
            //$other->where('user_id', $request->user_id);
        }

        if ($request->has('date')) {
            try {
                $date = Carbon::createFromFormat('d-m-Y', $request->date);
                switch ($request->period_type) {
                    case 'week':
                        $paidHolidays->whereBetween('starts_at', [$date->startOfWeek()->format('Y-m-d H:i:s'), $date->endOfWeek()->format('Y-m-d H:i:s')]);
                        $schoolPeriods->whereBetween('starts_at', [$date->startOfWeek()->format('Y-m-d H:i:s'), $date->endOfWeek()->format('Y-m-d H:i:s')]);
                        $publicHolidays->whereBetween('starts_at', [$date->startOfWeek()->format('Y-m-d H:i:s'), $date->endOfWeek()->format('Y-m-d H:i:s')]);
                        $overtimesUsed->whereBetween('starts_at', [$date->startOfWeek()->format('Y-m-d H:i:s'), $date->endOfWeek()->format('Y-m-d H:i:s')]);
                        //$other->whereBetween('starts_at', [$date->startOfWeek()->format('Y-m-d H:i:s'), $date->endOfWeek()->format('Y-m-d H:i:s')]);
                        break;
                    case 'month':
                        $paidHolidays->whereMonth('starts_at', $date->month)->whereYear('starts_at', $date->year);
                        $schoolPeriods->whereMonth('starts_at', $date->month)->whereYear('starts_at', $date->year);
                        $publicHolidays->whereMonth('starts_at', $date->month)->whereYear('starts_at', $date->year);
                        $overtimesUsed->whereMonth('starts_at', $date->month)->whereYear('starts_at', $date->year);
                        //$other->whereMonth('starts_at', $date->month)->whereYear('starts_at', $date->year);
                        break;
                    case 'year':
                        $paidHolidays->whereYear('starts_at', $date->year);
                        $schoolPeriods->whereYear('starts_at', $date->year);
                        $publicHolidays->whereYear('starts_at', $date->year);
                        $overtimesUsed->whereYear('starts_at', $date->year);
                        //$other->whereYear('starts_at', $date->year);
                        break;

                    default:
                        $paidHolidays->whereDate('starts_at', $date);
                        $schoolPeriods->whereDate('starts_at', $date);
                        $publicHolidays->whereDate('starts_at', $date);
                        $overtimesUsed->whereDate('starts_at', $date);
                        //$other->whereDate('starts_at', $date);
                        break;
                }
            } catch (\Throwable $th) {
                throw new ApiException("Paramètre 'date' n'est pas valide.");
            }
        }

        $paidHolidays = $paidHolidays->get();
        $schoolPeriods = $schoolPeriods->get();
        $publicHolidays = $publicHolidays->get();
        $overtimesUsed = $overtimesUsed->get();
        //$other = $other->get();

        $stats['total'] = CarbonInterval::hours(0);
        // // Ajout des congés payés au nombre d'heures
        if (!$paidHolidays->isEmpty() && !$request->has('project_id')) {
            foreach ($paidHolidays as $pH) {
                $hours = Carbon::create($pH->ends_at)->floatDiffInHours(Carbon::create($pH->starts_at));
                $stats['total']->add(CarbonInterval::hours($hours));
            }
        } else {
            $stats['total']->add(CarbonInterval::createFromFormat('H', 1));
            $stats['total']->sub(CarbonInterval::createFromFormat('H', 1));
        }

        // // Ajout des périodes de cours au nombre d'heures
        if (!$schoolPeriods->isEmpty() && !$request->has('project_id')) {
            foreach ($schoolPeriods as $pH) {
                $hours = Carbon::create($pH->ends_at)->floatDiffInHours(Carbon::create($pH->starts_at));
                $stats['total']->add(CarbonInterval::hours($hours));
            }
        } else {
            $stats['total']->add(CarbonInterval::createFromFormat('H', 1));
            $stats['total']->sub(CarbonInterval::createFromFormat('H', 1));
        }

        // // Ajout des jours fériés au nombre d'heures
        if (!$publicHolidays->isEmpty() && !$request->has('project_id')) {
            foreach ($publicHolidays as $pH) {
                $hours = Carbon::create($pH->ends_at)->floatDiffInHours(Carbon::create($pH->starts_at));
                $stats['total']->add(CarbonInterval::hours($hours));
            }
        } else {
            $stats['total']->add(CarbonInterval::createFromFormat('H', 1));
            $stats['total']->sub(CarbonInterval::createFromFormat('H', 1));
        }

        // // Ajout des heures supplémentaires utilisées au nombre d'heures
        if (!$overtimesUsed->isEmpty() && !$request->has('project_id')) {
            foreach ($overtimesUsed as $pH) {
                $hours = Carbon::create($pH->ends_at)->floatDiffInHours(Carbon::create($pH->starts_at));
                $stats['total']->add(CarbonInterval::hours($hours));
            }
        } else {
            $stats['total']->add(CarbonInterval::createFromFormat('H', 1));
            $stats['total']->sub(CarbonInterval::createFromFormat('H', 1));
        }

        /*// // Ajout des indispos "Autre" au nombre d'heures
        if (!$other->isEmpty() && !$request->has('project_id')) {
            foreach ($other as $pH) {
                $hours = Carbon::create($pH->ends_at)->floatDiffInHours(Carbon::create($pH->starts_at));
                $stats['total']->add(CarbonInterval::hours($hours));
            }
        } else {
            $stats['total']->add(CarbonInterval::createFromFormat('H', 1));
            $stats['total']->sub(CarbonInterval::createFromFormat('H', 1));
        }*/

        $listId = array();
        if (!$allItems->isEmpty()) {
            foreach ($nonPaginatedQuery->get() as $item) {
                $stats['total']->add(CarbonInterval::createFromFormat('H:i:s', $item->duration));
                if (!in_array($item->user_id, $listId)) {
                    array_push($listId, $item->user_id);
                }
            }
            $stats['total'] = $stats['total']->totalHours;
            if ($request->date) 
            {
                $nbWorkDays = 0;
                $workDayHours = 0;
                $workWeekHours = 0;
            if($request->period_type == "week")
            {

                $premierJour = Carbon::parse($request->start_at)->startOfWeek()->format('Y-m-d H:i');
                $dernierJour = Carbon::parse($request->start_at)->endOfWeek()->format('Y-m-d H:i');
                $periodWeek = CarbonPeriod::create($premierJour, '1 day', $dernierJour);
               
                $users = User::where('id', $request->user_id)->whereBetween('start_employment', [Carbon::parse($request->date)->startOfWeek()->format('Y-m-d H:i:s'), Carbon::parse($request->date)->endOfWeek()->format('Y-m-d H:i:s')])->get();
                if(!$users->isEmpty())
                {
                    $firstDateContains = $periodWeek->contains($users[0]->start_employment);
                }
                else
                {
                    $firstDateContains = false;
                }
              
                if($firstDateContains) 
                {
                    $premierJour = Carbon::parse($users[0]->start_employment)->format('Y-m-d H:i');
                    $periodWeek = CarbonPeriod::create($premierJour, '1 day', $dernierJour);
                    $dayOfEmployment = Carbon::parse($users[0]->start_employment)->locale('fr')->dayName;
                    $days = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
                    $key = array_search($dayOfEmployment, $days);
                    $daysUpdate = array_slice($days, $key);     
        
                    foreach($daysUpdate as $day)
                    {   
                       
                            $workDayHours = WorkHours::where('user_id',$request->user_id)->where('is_active', 1)->where('day','=',$day)->get()->map(function ($day) {      
                        
                                
                            if($day->morning_ends_at != null)
                            {
                                $morning = CarbonInterval::createFromFormat('H:i:s', $day->morning_ends_at)->subtract(CarbonInterval::createFromFormat('H:i:s', $day->morning_starts_at));
                            }
                            else
                            {
                                $morning = CarbonInterval::hours(0);
                            }
            
                            if($day->afternoon_ends_at != null)
                            {
                                $afternoon = CarbonInterval::createFromFormat('H:i:s', $day->afternoon_ends_at)->subtract(CarbonInterval::createFromFormat('H:i:s', $day->afternoon_starts_at));
                            }
                            else
                            {
                                    $afternoon = CarbonInterval::hours(0);
                            }
                                return $morning->add($afternoon)->totalHours;
                            })->sum();  
                            $workWeekHours += $workDayHours;

                            $workDayHours += HoursController::getTargetWorkHours($request->user_id, Carbon::createFromFormat('d-m-Y', $request->date)->locale('fr_FR')->dayName);
                               
                          
                               
                                $ListOfWorkDays = array();
                                foreach ($daysUpdate as $day) {
                                    $work = WorkHours::where('user_id',$request->user_id)->where('is_active', 1)->where('day','=',$day)->get();
                                    
                                    if(!$work->isEmpty() && $work[0]->day == $day)
                                    {
                                     array_push($ListOfWorkDays, $day);
                                    }
                               
                               
                                } 
                                
                    }
                }
                else{
                       
                    for ($i = 0; $i < count($listId); $i++) {
                        $nbWorkDays += WorkHours::where('user_id', $listId[$i])->where('is_active', 1)->count() || 1;
                        $workWeekHours = WorkHours::where('user_id', $listId[$i])->where('is_active', 1)->get()->map(function ($day) {
                            if($day->morning_ends_at != null)
                            {
                                 $morning = CarbonInterval::createFromFormat('H:i:s', $day->morning_ends_at)->subtract(CarbonInterval::createFromFormat('H:i:s', $day->morning_starts_at));
                            }
                            else
                            {
                                $morning = CarbonInterval::hours(0);
                            }
                               if($day->afternoon_ends_at != null)
                                {
                                    $afternoon = CarbonInterval::createFromFormat('H:i:s', $day->afternoon_ends_at)->subtract(CarbonInterval::createFromFormat('H:i:s', $day->afternoon_starts_at));
                                }
                                else
                                {
                                    $afternoon = CarbonInterval::hours(0);
                                }
                            return $morning->add($afternoon)->totalHours;
                        })->sum();
                        $workDayHours += HoursController::getTargetWorkHours($listId[$i], Carbon::createFromFormat('d-m-Y', $request->date)->locale('fr_FR')->dayName);
    
                        $ListOfWorkHours = WorkHours::where('user_id', $listId[$i])->where('is_active', 1)->get();
    
                        $ListOfWorkDays = array();
                        foreach ($ListOfWorkHours as $day) {
                            array_push($ListOfWorkDays, $day->day);
                            
                        }
                    }
                }
            }
                
                    
                
                    else{
                       
                        for ($i = 0; $i < count($listId); $i++) {
                            $nbWorkDays += WorkHours::where('user_id', $listId[$i])->where('is_active', 1)->count() || 1;
                            $workWeekHours = WorkHours::where('user_id', $listId[$i])->where('is_active', 1)->get()->map(function ($day) {
                                if($day->morning_ends_at != null)
                                {
                                     $morning = CarbonInterval::createFromFormat('H:i:s', $day->morning_ends_at)->subtract(CarbonInterval::createFromFormat('H:i:s', $day->morning_starts_at));
                                }
                                else
                                {
                                    $morning = CarbonInterval::hours(0);
                                }
                                   if($day->afternoon_ends_at != null)
                                    {
                                        $afternoon = CarbonInterval::createFromFormat('H:i:s', $day->afternoon_ends_at)->subtract(CarbonInterval::createFromFormat('H:i:s', $day->afternoon_starts_at));
                                    }
                                    else
                                    {
                                        $afternoon = CarbonInterval::hours(0);
                                    }
                                return $morning->add($afternoon)->totalHours;
                            })->sum();
                            $workDayHours += HoursController::getTargetWorkHours($listId[$i], Carbon::createFromFormat('d-m-Y', $request->date)->locale('fr_FR')->dayName);
        
                            $ListOfWorkHours = WorkHours::where('user_id', $listId[$i])->where('is_active', 1)->get();
        
                            $ListOfWorkDays = array();
                            foreach ($ListOfWorkHours as $day) {
                                array_push($ListOfWorkDays, $day->day);
                                
                            }
                        }
                    }
                
                


                $defaultWorkHours = 0;
                if ($request->date) {
                    switch ($request->period_type) {
                        case 'day':
                            $defaultWorkHours = $workDayHours;
                            break;
                        case 'week':
                            $defaultWorkHours = $workWeekHours;
                            break;
                        case 'month':
                            $nbWorkDaysPerMonth = $this->calculeNbWorkDaysPerMonth($request, $ListOfWorkDays, null, 1);
                            $defaultWorkHours = $nbWorkDaysPerMonth * ($workWeekHours / count($ListOfWorkDays));
                            break;
                        case 'year':
                            $period = CarbonPeriod::create(Carbon::createFromFormat('d-m-Y', $request->date)->startOfYear(), '1 month', Carbon::createFromFormat('d-m-Y', $request->date)->endOfYear());
                            foreach ($period as $month) {
                                $moisCourant = $month->month;
                                $nbWorkDaysForEachMonth = $this->calculeNbWorkDaysPerMonth($request, $ListOfWorkDays, $moisCourant, 0);
                                $defaultWorkHours += ($nbWorkDaysForEachMonth * ($workWeekHours / count($ListOfWorkDays)));
                               
                            }
                            break;
                        default:
                            $defaultWorkHours = $workWeekHours / $nbWorkDays;
                            break;
                    }
                } else {
                   
                    $period = CarbonPeriod::create($allItems->min('start_at'), '1 week', $allItems->max('end_at'));
                    foreach ($period as $week) {
                        $defaultWorkHours += $workWeekHours;
                    }
                }

                //On veut connaitre également le nombre d'heures effectuées en moins.        

                    if ($stats['total'] > $defaultWorkHours)
                        {
                        $stats['overtime'] = $stats['total'] - $defaultWorkHours;
                      
                        } 
                        else {
                            $stats['lost_time'] = $defaultWorkHours - $stats['total'];
                         
                        }
    
                   
               
                    }else if ($request->date && $userId = ($user->is_admin || $user->is_manager) ? $request->user_id : $user->id) {
                $nbWorkDays = WorkHours::where('user_id', $userId)->where('is_active', 1)->count() || 1;
               
                $workWeekHours = WorkHours::where('user_id', $userId)->where('is_active', 1)->get()->map(function ($day) {
                    if($day->morning_ends_at != null)
                    {
                         $morning = CarbonInterval::createFromFormat('H:i:s', $day->morning_ends_at)->subtract(CarbonInterval::createFromFormat('H:i:s', $day->morning_starts_at));
                    }
                    else
                    {
                        $morning = CarbonInterval::hours(0);
                    }
                       if($day->afternoon_ends_at != null)
                        {
                            $afternoon = CarbonInterval::createFromFormat('H:i:s', $day->afternoon_ends_at)->subtract(CarbonInterval::createFromFormat('H:i:s', $day->afternoon_starts_at));
                        }
                        else
                        {
                            $afternoon = CarbonInterval::hours(0);
                        }
                    return $morning->add($afternoon)->totalHours;
                })->sum();
                $workDayHours = HoursController::getTargetWorkHours($userId, Carbon::createFromFormat('d-m-Y', $request->date)->locale('fr_FR')->dayName);

                $ListOfWorkHours = WorkHours::where('user_id', $userId)->where('is_active', 1)->get();

                $ListOfWorkDays = array();
                foreach ($ListOfWorkHours as $day) {
                    array_push($ListOfWorkDays, $day->day);
                }

                $defaultWorkHours = 0;
                if ($request->date) {
                    switch ($request->period_type) {
                        case 'day':
                            $defaultWorkHours = $workDayHours;
                            break;
                        case 'week':
                            $defaultWorkHours = $workWeekHours;
                            break;
                        case 'month':
                            $nbWorkDaysPerMonth = $this->calculeNbWorkDaysPerMonth($request, $ListOfWorkDays, null, 1);
                            $defaultWorkHours = $nbWorkDaysPerMonth * ($workWeekHours / count($ListOfWorkDays));
                            
                            break;
                        case 'year':
                            $period = CarbonPeriod::create(Carbon::createFromFormat('d-m-Y', $request->date)->startOfYear(), '1 month', Carbon::createFromFormat('d-m-Y', $request->date)->endOfYear());
                            foreach ($period as $month) {
                                $moisCourant = $month->month;
                                $nbWorkDaysForEachMonth = $this->calculeNbWorkDaysPerMonth($request, $ListOfWorkDays, $moisCourant, 0);
                                $defaultWorkHours += ($nbWorkDaysForEachMonth * ($workWeekHours / count($ListOfWorkDays)));
                            }
                            break;
                        default:
                            $defaultWorkHours = $workWeekHours / $nbWorkDays;
                            break;
                    }
                } else {
                    $period = CarbonPeriod::create($allItems->min('start_at'), '1 week', $allItems->max('end_at'));
                    foreach ($period as $week) {
                        $defaultWorkHours += $workWeekHours;
                    }
                } 
                

                //On veut connaitre également le nombre d'heures effectuées en moins.
                if ($stats['total'] > $defaultWorkHours) {
                    $stats['overtime'] = $stats['total'] - $defaultWorkHours;
                } else {
                    $stats['lost_time'] = $defaultWorkHours - $stats['total'];
                }
            }
        }
            
        return collect(['stats' => $stats]);
    }

    protected function calculeNbWorkDaysPerMonth(Request $request, $ListOfWorkDays, $month, $showMonth)
    {
        if ($showMonth) {
            
            $premierJour = Carbon::createFromFormat('d-m-Y', $request->date)->firstOfMonth();
            $dernierJour = Carbon::createFromFormat('d-m-Y', $request->date)->lastOfMonth();
            
        } else {
            $premierJour = Carbon::createFromFormat('m', $month)->firstOfMonth();
            $dernierJour = Carbon::createFromFormat('m', $month)->lastOfMonth();
        }
        $countMonday = 0;
        $countTuesday = 0;
        $countWednesday = 0;
        $countThursday = 0;
        $countFriday = 0;
        $countSaturday = 0;
        $countSunday = 0;

        $periodWeek = CarbonPeriod::create($premierJour, '1 day', $dernierJour);
       
        $users = User::where('id', $request->user_id)->get();
        if(!$users->isEmpty())
        {
            $firstDateContains = $periodWeek->contains($users[0]->start_employment);

        }
        else
        {
            $firstDateContains = false;
        }
        if($firstDateContains)
        {
         
            $premierJour = Carbon::createFromFormat('Y-m-d H:i:s', $users[0]->start_employment)->format("Y-m-d");
        }
            $periodWeek = CarbonPeriod::create($premierJour, '1 day', $dernierJour);
            
            foreach ($periodWeek as $day) {
             
                foreach ($ListOfWorkDays as $workDay) {
                    if ($day->isMonday() && $workDay == "lundi") {
                        $countMonday++;
                    } elseif ($day->isTuesday() && $workDay == "mardi") {
                        $countTuesday++;
                    } elseif ($day->isWednesday() && $workDay == "mercredi") {
                        $countWednesday++;
                    } elseif ($day->isThursday() && $workDay == "jeudi") {
                        $countThursday++;
                    } elseif ($day->isFriday() && $workDay == "vendredi") {
                        $countFriday++;
                    } elseif ($day->isSaturday() && $workDay == "samedi") {
                        $countSaturday++;
                    } elseif ($day->isSunday() && $workDay == "dimanche") {
                        $countSunday++;
                    }
                }
            }
            $nbWorkDaysPerMonth = $countMonday + $countTuesday + $countWednesday + $countThursday + $countFriday + $countSaturday + $countSunday;
        return $nbWorkDaysPerMonth;
    }

    protected function filterIndexQuery(Request $request, $query)
    {
        $user = Auth::user();
        if (!$user->is_admin) {
            if ($user->is_manager) {
                $query->join('users', function ($join) use ($user) {
                    $join->on('hours.user_id', '=', 'users.id')
                        ->where('users.company_id', $user->company_id);
                });
            } else {
                $query->where('user_id', $user->id);
            }
        }

        if ($request->has('user_id')) {
            if (User::where('id', $request->user_id)->doesntExist()) {
                throw new ApiException("Paramètre 'user_id' n'est pas valide.");
            }
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('project_id')) {
            if (Project::where('id', $request->project_id)->doesntExist()) {
                throw new ApiException("Paramètre 'project_id' n'est pas valide.");
            }
            $query->where('project_id', $request->project_id);
        }

        if ($request->has('date')) {
            try {
                $date = Carbon::createFromFormat('d-m-Y', $request->date);
                switch ($request->period_type) {
                    case 'week':
                        $query->whereBetween('start_at', [$date->startOfWeek()->format('Y-m-d H:i:s'), $date->endOfWeek()->format('Y-m-d H:i:s')]);
                        break;
                    case 'month':
                        $query->whereMonth('start_at', $date->month)->whereYear('start_at', $date->year);
                        break;
                    case 'year':
                        $query->whereYear('start_at', $date->year);
                        break;

                    default:
                        $query->whereDate('start_at', $date);
                        break;
                }
            } catch (\Throwable $th) {
                throw new ApiException("Paramètre 'date' n'est pas valide.");
            }
        }

        if (!$request->has('order_by')) {
            $query->orderBy('start_at');
        }
    }

    protected function storeItem(array $arrayRequest)
    {
       
        //Set duration
        $start_at = Carbon::parse($arrayRequest['start_at']);
        $end_at = Carbon::parse($arrayRequest['end_at']);

        $parseDuration = $start_at->floatDiffInHours($end_at);

        if ($parseDuration === 0) {
            throw new ApiException("Veuillez inserer une durée.");
        }

        // Check if user have already hour on this new entry period
        if (!$this->checkIfNewEntryLayeringOtherHours($arrayRequest['user_id'], $start_at, $end_at)) {
            throw new ApiException("Attention, vous ne pouvez pas superposer deux horaires.");
        }

        $item = Hours::create([
            'user_id' => $arrayRequest['user_id'],
            'project_id' => $arrayRequest['project_id'],
            'task_id' => $arrayRequest['task_id'],
            'start_at' => $arrayRequest['start_at'],
            'end_at' => $arrayRequest['end_at'],
            'date' => $arrayRequest['date'],
        ]);

        $TaskTimeSpentOfDay=TaskTimeSpent::where('task_id', $arrayRequest['task_id'])->where('user_id', $arrayRequest['user_id'])->where('date', $arrayRequest['date'])->get();
       
        if($TaskTimeSpentOfDay->isEmpty()){
            TaskTimeSpent::create([
                'date' => Carbon::parse($arrayRequest['start_at'])->startOfDay(),
                'duration' => $parseDuration,
                'user_id' => $arrayRequest['user_id'],
                'task_id' => $arrayRequest['task_id'],
            ]);
        } 
        else{
            $TaskTimeSpentOfDay[0]->update([
                'duration' => $TaskTimeSpentOfDay[0]['duration']+$parseDuration,
            ]);
        }       

        // How many hour user worked this week
        $listDealingHour = array();
        $nb_worked_hours = 0;
        $exist = false;
        
        $premierJour = Carbon::parse($arrayRequest['start_at'])->startOfWeek()->format('Y-m-d H:i');
        $dernierJour = Carbon::parse($arrayRequest['start_at'])->endOfWeek()->format('Y-m-d H:i');
        $periodWeek = CarbonPeriod::create($premierJour, '1 day', $dernierJour);
        $users = User::where('id', $arrayRequest['user_id'])->whereBetween('start_employment', [Carbon::parse($arrayRequest['date'])->startOfWeek()->format('Y-m-d H:i:s'), Carbon::parse($arrayRequest['date'])->endOfWeek()->format('Y-m-d H:i:s')])->get();
        if(!$users->isEmpty())
        {
            $firstDateContains = $periodWeek->contains($users[0]->start_employment);
            
        }
        else
        {
            $firstDateContains = false;
        }
      
            $workWeekHours = 0;

        if($firstDateContains)
        {
            $premierJour = Carbon::parse($users[0]->start_employment)->format('Y-m-d H:i');
            $periodWeek = CarbonPeriod::create($premierJour, '1 day', $dernierJour);
            $dayOfEmployment = Carbon::parse($users[0]->start_employment)->locale('fr')->dayName;
            $days = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
            $key = array_search($dayOfEmployment, $days);
            $daysUpdate = array_slice($days, $key);     
    

            foreach($daysUpdate as $day)
            {   
          
                $workDayHours = WorkHours::where('user_id', $arrayRequest['user_id'])->where('is_active', 1)->where('day','=',$day)->get()->map(function ($day) {      
               
                    
                if($day->morning_ends_at != null)
                {
                     $morning = CarbonInterval::createFromFormat('H:i:s', $day->morning_ends_at)->subtract(CarbonInterval::createFromFormat('H:i:s', $day->morning_starts_at));
                }
                else
                {
                    $morning = CarbonInterval::hours(0);
                }

                if($day->afternoon_ends_at != null)
                {
                        $afternoon = CarbonInterval::createFromFormat('H:i:s', $day->afternoon_ends_at)->subtract(CarbonInterval::createFromFormat('H:i:s', $day->afternoon_starts_at));
                }
                else
                {
                        $afternoon = CarbonInterval::hours(0);
                }
                    return $morning->add($afternoon)->totalHours;
                })->sum();  
             
                $workWeekHours += $workDayHours;
               
            }
          
           
        } 
        else
        {
            $workWeekHours = WorkHours::where('user_id', $arrayRequest['user_id'])->where('is_active', 1)->get()->map(function ($day) {
              
                if($day->morning_ends_at != null)
                {
                     $morning = CarbonInterval::createFromFormat('H:i:s', $day->morning_ends_at)->subtract(CarbonInterval::createFromFormat('H:i:s', $day->morning_starts_at));
                }
                else
                {
                    $morning = CarbonInterval::hours(0);
                }
                   if($day->afternoon_ends_at != null)
                    {
                        $afternoon = CarbonInterval::createFromFormat('H:i:s', $day->afternoon_ends_at)->subtract(CarbonInterval::createFromFormat('H:i:s', $day->afternoon_starts_at));
                    }
                    else
                    {
                        $afternoon = CarbonInterval::hours(0);
                    }
                return $morning->add($afternoon)->totalHours;
            })->sum();

        }
     
        
        foreach ($periodWeek as $day) {
            // Check if value in dealing_hours for this date
            $findDealingHour = DealingHours::where('user_id', $arrayRequest['user_id'])->where('date', $day)->first();
            if ($findDealingHour != null) {
                array_push($listDealingHour, $findDealingHour);
                $exist = true;
            }
          
            $nb_worked_hours += HoursController::getNbWorkedHours($arrayRequest['user_id'], 0, $day->format('Y-m-d'));
        }

        // Expected hours for this week
        
      
       
        if ($workWeekHours === 0) {
            throw new ApiException("Vérifier que l'utilisateur ai bien renseigné des horraires de travail pour la semaine.");
        }
        if (isset($arrayRequest['description'])) {
            $item->update(['description' => $arrayRequest['description']]);
        }

        if ($exist && ($nb_worked_hours - $workWeekHours == 0)) {
            $listDealingHour[0]->delete();
            unset($listDealingHour[0]);
        } elseif ($exist) {
            if ($listDealingHour[0]['date'] == '2001-01-01') {
                throw new ApiException('Erreur date 2001-01-01.');
            }
            // Update dealing_hour with difference between nb_worked_hours and $workWeekHours for overtime column
            $listDealingHour[0]->update(['overtimes' => ($nb_worked_hours - $workWeekHours)]);
        }
        // Else add new tuple in dealing_hours for this date
        elseif (empty($listDealingHour[0]) && ($nb_worked_hours - $workWeekHours != 0)) {
            //Create new tuple in dealing_hours with user_id, date and overtimes


                $deallingHourItem = DealingHours::create(
                    ['user_id' => $arrayRequest['user_id'], 'date' => $arrayRequest['date'], 'overtimes' => ($nb_worked_hours - $workWeekHours)]);
                  
                
            }
            else{
                  $deallingHourItem = DealingHours::create(
                ['user_id' => $arrayRequest['user_id'], 'date' => $arrayRequest['date'], 'overtimes' => ($nb_worked_hours - $workWeekHours)]
            );
            }
        return $item;
    }

    protected function updateItem($item, array $arrayRequest)
    {
        $start_at = Carbon::parse($arrayRequest['start_at']);
        $end_at = Carbon::parse($arrayRequest['end_at']);

        $parseDuration = $start_at->floatDiffInHours($end_at);
        // Expected hours for this week
        $workWeekHours = WorkHours::where('user_id', $arrayRequest['user_id'])->where('is_active', 1)->get()->map(function ($day) {
            $morning = CarbonInterval::createFromFormat('H:i:s', $day->morning_ends_at)->subtract(CarbonInterval::createFromFormat('H:i:s', $day->morning_starts_at));
            $afternoon = CarbonInterval::createFromFormat('H:i:s', $day->afternoon_ends_at)->subtract(CarbonInterval::createFromFormat('H:i:s', $day->afternoon_starts_at));
            return $morning->add($afternoon)->totalHours;
        })->sum();
        if ($workWeekHours === 0) {
            throw new ApiException("Vérifier que l'utilisateur ai bien renseigné des horraires de travail pour la semaine.");
        }

        // Check if user have already hour on this new entry period
        if (!$this->checkIfNewEntryLayeringOtherHours($arrayRequest['user_id'], $start_at, $end_at, $arrayRequest['id'])) {
            throw new ApiException("Attention, vous ne pouvez pas superposer deux horaires.");
        }

        $oldDuration=$item['durationInFloatHour'];

        $item->update([
            'user_id' => $arrayRequest['user_id'],
            'project_id' => $arrayRequest['project_id'],
            'task_id' => $arrayRequest['task_id'],
            'start_at' => $arrayRequest['start_at'],
            'end_at' => $arrayRequest['end_at'],
        ]);

        if (isset($arrayRequest['description'])) {
            $item->update(['description' => $arrayRequest['description']]);
        }

        $TaskTimeSpentOfDay=TaskTimeSpent::where('task_id', $arrayRequest['task_id'])->where('user_id', $arrayRequest['user_id'])->where('date', $arrayRequest['date'])->get();
       
        if($TaskTimeSpentOfDay->isEmpty()){
            TaskTimeSpent::create([
                'date' => Carbon::parse($arrayRequest['start_at'])->startOfDay(),
                'duration' => $parseDuration,
                'user_id' => $arrayRequest['user_id'],
                'task_id' => $arrayRequest['task_id'],
            ]);
        } 
        else{
            $TaskTimeSpentOfDay[0]->update([
                'duration' => $TaskTimeSpentOfDay[0]['duration']+$parseDuration-$oldDuration,
            ]);
        }     

        // How many new hour user worked this week
        $listDealingHour = array();
        $nb_worked_hours = 0;
        $premierJour = Carbon::parse($arrayRequest['start_at'])->startOfWeek()->format('Y-m-d H:i');
        $dernierJour = Carbon::parse($arrayRequest['start_at'])->endOfWeek()->format('Y-m-d H:i');
        $periodWeek = CarbonPeriod::create($premierJour, '1 day', $dernierJour);
        foreach ($periodWeek as $day) {
            // Check if value in dealing_hours for this date
            $findDealingHour = DealingHours::where('user_id', $arrayRequest['user_id'])->where('date', $day)->first();
            if ($findDealingHour != null) {
                array_push($listDealingHour, $findDealingHour);
            }
            $nb_worked_hours += HoursController::getNbWorkedHours($arrayRequest['user_id'], 0, $day->format('Y-m-d'));
        }
        if (!empty($listDealingHour[0]) && ($nb_worked_hours - $workWeekHours == 0)) {
            $listDealingHour[0]->delete();
            unset($listDealingHour[0]);
        } elseif (!empty($listDealingHour[0])) {
            if ($listDealingHour[0]['date'] == '2001-01-01') {
                throw new ApiException('Erreur date 2001-01-01.');
            }
            // Update dealing_hour with difference between nb_worked_hours and $target_work_hours for overtime column
            $listDealingHour[0]->update(['overtimes' => ($nb_worked_hours - $workWeekHours)]);

        } elseif (empty($listDealingHour[0]) && ($nb_worked_hours - $workWeekHours != 0)) {
            //Create new tuple in dealing_hours with user_id, date and overtimes
            $deallingHourItem = DealingHours::create(
                ['user_id' => $arrayRequest['user_id'], 'date' => $arrayRequest['date'], 'overtimes' => ($nb_worked_hours - $workWeekHours)]
            );
        }

        return $item;
    }

    private function checkIfNewEntryLayeringOtherHours($user_id, $start_at, $end_at, $hour_id = null)
    {

        $test = $start_at->format('Y-m-d');
        $usersHours = $hour_id ? Hours::where('user_id', $user_id)->where('start_at', 'like', '%' . $start_at->format('Y-m-d') . '%')->where('id', '!=', $hour_id)->get()
            : Hours::where('user_id', $user_id)->where('start_at', 'like', '%' . $start_at->format('Y-m-d') . '%')->get();

        if (count($usersHours) > 0) {

            $newPeriod = CarbonPeriod::create($start_at, $end_at);

            foreach ($usersHours as $hour) {

                $hourPeriod = CarbonPeriod::create($hour->start_at, $hour->end_at);

                if (($newPeriod->contains($hour->start_at) && ($hour->start_at != $start_at && $hour->start_at != $end_at))
                    || ($newPeriod->contains($hour->end_at) && ($hour->end_at != $start_at && $hour->end_at != $end_at))
                    || ($hourPeriod->contains($start_at) && $hourPeriod->contains($end_at))
                ) {

                    return false;
                }
            }
        } else {
            return true;
        }
        return true;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @param  float  $duration
     * @param  string  $date
     * @return \Illuminate\Http\Response
     */
    private static function getNbWorkedHours($user_id, $duration, $date)
    {
        // How many hour user worked this day
        $worked_hours = $date ? Hours::where([['user_id', $user_id], ['start_at', 'LIKE', '%' . $date . '%']])->get() : [];
        // add some Unavailability
        $typeIndispo=array("Heures supplémentaires payées", "Rendez-vous privé", "Arrêt de travail");
        $worked_unavailabilities = $date ? Unavailability::where([['user_id', $user_id], ['starts_at', 'LIKE', '%' . $date . '%']])->whereNotIn('reason', $typeIndispo)->get() : [];

        /*$worked_unavailabilities = $date ? Unavailability::where([['user_id', $user_id], ['starts_at', 'LIKE', '%' . $date . '%'], ['reason', "Congés payés"]])
            ->orWhere([['user_id', $user_id], ['starts_at', 'LIKE', '%' . $date . '%'], ['reason', "Jours fériés"]])
            ->orWhere([['user_id', $user_id], ['starts_at', 'LIKE', '%' . $date . '%'], ['reason', "Période de cours"]])
            ->orWhere([['user_id', $user_id], ['starts_at', 'LIKE', '%' . $date . '%'], ['reason', "Utilisation heures supplémentaires"]])->get() : [];*/

        $nb_worked_hours = 0;

        if (!$worked_unavailabilities->isEmpty()) {

            foreach ($worked_unavailabilities as $wu) {
                $nb_worked_hours += Carbon::create($wu->ends_at)->floatDiffInHours(Carbon::create($wu->starts_at));
            }
        }

        if (!$worked_hours->isEmpty()) {

            foreach ($worked_hours as $wh) {
                $nb_worked_hours += $wh->durationInFloatHour;
            }
            // Add current insert work hours
            return $nb_worked_hours += $duration;
        } else {
            return $nb_worked_hours += $duration;
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @param  string  $date
     * @return \Illuminate\Http\Response
     */
    public static function getTargetWorkHours($user_id, $target_day)
    {
        $work_hours = WorkHours::where([['user_id', $user_id], ['day', $target_day]])->select('morning_starts_at', 'morning_ends_at', 'afternoon_starts_at', 'afternoon_ends_at')->first();

        if (!empty($work_hours)) {
            if ($work_hours['morning_starts_at'] !== null && $work_hours['morning_ends_at'] !== null) {
                $parts = explode(':', $work_hours['morning_starts_at']);
                $morning_starts_at = $parts[0] + $parts[1] / 60 * 100 / 100;
                $parts = explode(':', $work_hours['morning_ends_at']);
                $morning_ends_at = $parts[0] + $parts[1] / 60 * 100 / 100;
            } else {
                $morning_starts_at = 0;
                $morning_ends_at = 0;
            }
            if ($work_hours['afternoon_starts_at'] !== null && $work_hours['afternoon_ends_at'] !== null) {
                $parts = explode(':', $work_hours['afternoon_starts_at']);
                $afternoon_starts_at = $parts[0] + $parts[1] / 60 * 100 / 100;
                $parts = explode(':', $work_hours['afternoon_ends_at']);
                $afternoon_ends_at = $parts[0] + $parts[1] / 60 * 100 / 100;
            } else {
                $afternoon_starts_at = 0;
                $afternoon_ends_at = 0;
            }

            $target_work_hours = ($morning_ends_at - $morning_starts_at) + ($afternoon_ends_at - $afternoon_starts_at);
            return $target_work_hours;
        } else {
            return 0;
        }
    }

    protected function destroyItem($item)
    {
        $date = Carbon::parse($item->start_at);

        $TaskTimeSpentOfDay=TaskTimeSpent::where('task_id', $item['task_id'])->where('user_id', $item['user_id'])->where('date', Carbon::parse($item['start_at'])->format('Y-m-d'))->get();
       
        if(!$TaskTimeSpentOfDay->isEmpty()){
            $TaskTimeSpentOfDay[0]->update([
                'duration' => $TaskTimeSpentOfDay[0]['duration']-$item['durationInFloatHour'],
            ]);
        } 

        // How many hour worked this week
        $listDealingHour = array();
        $nb_worked_hours = 0;
        $premierJour = Carbon::parse($item['start_at'])->startOfWeek()->format('Y-m-d H:i');
        $dernierJour = Carbon::parse($item['start_at'])->endOfWeek()->format('Y-m-d H:i');
        $periodWeek = CarbonPeriod::create($premierJour, '1 day', $dernierJour);
        foreach ($periodWeek as $day) {
            // Check if value in dealing_hours for this date
            $findDealingHour = DealingHours::where('user_id', $item['user_id'])->where('date', $day)->first();
            if ($findDealingHour != null) {
                array_push($listDealingHour, $findDealingHour);
            }
            $nb_worked_hours += HoursController::getNbWorkedHours($item['user_id'], 0, $day->format('Y-m-d'));
        }
        $nb_worked_hours -= $item['durationInFloatHour'];

        // Expected hours for this week
        $workWeekHours = WorkHours::where('user_id', $item['user_id'])->where('is_active', 1)->get()->map(function ($day) {
            if($day->morning_ends_at != null)
                {
                     $morning = CarbonInterval::createFromFormat('H:i:s', $day->morning_ends_at)->subtract(CarbonInterval::createFromFormat('H:i:s', $day->morning_starts_at));
                }
                else
                {
                    $morning = CarbonInterval::hours(0);
                }
                   if($day->afternoon_ends_at != null)
                    {
                        $afternoon = CarbonInterval::createFromFormat('H:i:s', $day->afternoon_ends_at)->subtract(CarbonInterval::createFromFormat('H:i:s', $day->afternoon_starts_at));
                    }
                    else
                    {
                        $afternoon = CarbonInterval::hours(0);
                    }
            return $morning->add($afternoon)->totalHours;
        })->sum();

        if (!empty($listDealingHour[0]) && ($nb_worked_hours - $workWeekHours == 0)) {
            unset($listDealingHour[0]);
            $listDealingHour[0]->delete();
        } elseif (!empty($listDealingHour[0])) {
            if ($listDealingHour[0]['date'] == '2001-01-01') {
                throw new ApiException('Erreur date 2001-01-01.');
            }
            // Update dealing_hour with difference between nb_worked_hours and $target_work_hours for overtime column
            $listDealingHour[0]->update(['overtimes' => ($nb_worked_hours - $workWeekHours)]);
        } elseif (empty($listDealingHour[0]) && ($nb_worked_hours - $workWeekHours != 0)) {
            //create dealig_hours for this week with difference between nb_worked_hours and $target_work_hours for overtime column
            $deallingHourItem = DealingHours::create(
                ['user_id' => $item['user_id'], 'date' => $item['created_at'], 'overtimes' => ($nb_worked_hours - $workWeekHours)]
            );
        }

        return parent::destroyItem($item);
    }
}
