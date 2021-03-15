<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Models\Hours;
use App\Models\Unavailability;
use App\Models\DealingHours;
use App\Models\Project;
use App\Models\WorkHours;
use App\User;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Auth;

class HoursController extends BaseApiController
{
    protected static $index_load = ['project:projects.id,name,color', 'user:users.id,firstname,lastname,email'];
    protected static $index_append = null;
    protected static $show_load = ['project:projects.id,name,color', 'user:users.id,firstname,lastname,email'];
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

    protected function extraIndexData(Request $request, $items)
    {
        $stats = [];

        $user = Auth::user();
        $paidHolidays = $user->is_admin ? Unavailability::where('reason', 'Congés payés') : Unavailability::where('user_id', $user->id)->where('reason', "Congés payés");

        if ($request->has('user_id')) {
            if (User::where('id', $request->user_id)->doesntExist()) {
                throw new ApiException("Paramètre 'user_id' n'est pas valide.");
            }
            $paidHolidays->where('user_id', $request->user_id);
        }

        if ($request->has('date')) {
            try {
                $date = Carbon::createFromFormat('d-m-Y', $request->date);
                switch ($request->period_type) {
                    case 'week':
                        $paidHolidays->whereBetween('starts_at', [$date->startOfWeek()->format('Y-m-d H:i:s'), $date->endOfWeek()->format('Y-m-d H:i:s')]);
                        break;
                    case 'month':
                        $paidHolidays->whereMonth('starts_at', $date->month)->whereYear('starts_at', $date->year);
                        break;
                    case 'year':
                        $paidHolidays->whereYear('starts_at', $date->year);
                        break;

                    default:
                        $paidHolidays->whereDate('starts_at', $date);
                        break;
                }
            } catch (\Throwable $th) {
                throw new ApiException("Paramètre 'date' n'est pas valide.");
            }
        }

        $paidHolidays = $paidHolidays->get();

        // // Ajout des congés payés au nombre d'heures
        if (!$paidHolidays->isEmpty()) {
            $stats['total'] = CarbonInterval::hours(0);
            foreach ($paidHolidays as $pH) {
                $hours = Carbon::create($pH->ends_at)->diffInHours(Carbon::create($pH->starts_at));
                $stats['total']->add(CarbonInterval::createFromFormat('H', $hours));
            }
        } else {
            $stats['total'] = CarbonInterval::hours(0);
            $stats['total']->add(CarbonInterval::createFromFormat('H', 1));
            $stats['total']->sub(CarbonInterval::createFromFormat('H', 1));
        }
        $listId = array();        
        if (!$items->isEmpty()) {            
            array_push($listId,$items->first()->user_id);            
            foreach ($items as $item) {
                $stats['total']->add(CarbonInterval::createFromFormat('H:i:s', $item->duration));  
                if(!in_array($item->user_id, $listId)){                        
                    array_push($listId,$item->user_id);
                }
            }

            $stats['total'] = $stats['total']->totalHours;            
            if($request->date){
                $nbWorkDays=0;
                $workDayHours=0;
                $workWeekHours=0;            

                for($i=0;$i<count($listId);$i++){
                    $nbWorkDays += WorkHours::where('user_id', $listId[$i])->where('is_active', 1)->count() || 1;
                    $workWeekHours += WorkHours::where('user_id', $listId[$i])->where('is_active', 1)->get()->map(function ($day) {
                        $morning = CarbonInterval::createFromFormat('H:i:s', $day->morning_ends_at)->subtract(CarbonInterval::createFromFormat('H:i:s', $day->morning_starts_at));
                        $afternoon = CarbonInterval::createFromFormat('H:i:s', $day->afternoon_ends_at)->subtract(CarbonInterval::createFromFormat('H:i:s', $day->afternoon_starts_at));
                        return $morning->add($afternoon)->totalHours;
                    })->sum();
                    $workDayHours += HoursController::getTargetWorkHours($listId[$i], Carbon::createFromFormat('d-m-Y', $request->date)->locale('fr_FR')->dayName);
    
                    $ListOfWorkHours= WorkHours::where('user_id', $listId[$i])->where('is_active', 1)->get();
    
                    $ListOfWorkDays=array();
                    foreach($ListOfWorkHours as $day){
                        array_push($ListOfWorkDays,$day->day);
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
                            $nbWorkDaysPerMonth=HoursController::calculeNbWorkDaysPerMonth($request,$ListOfWorkDays,null,1);
                            $defaultWorkHours = $nbWorkDaysPerMonth * ($workWeekHours/count($ListOfWorkDays));
                            break;
                        case 'year':
                            $period = CarbonPeriod::create(Carbon::createFromFormat('d-m-Y', $request->date)->startOfYear(), '1 month', Carbon::createFromFormat('d-m-Y', $request->date)->endOfYear());
                            foreach ($period as $month) {
                                $moisCourant=$month->month;
                                $nbWorkDaysForEachMonth=HoursController::calculeNbWorkDaysPerMonth($request,$ListOfWorkDays,$moisCourant,0);
                                $defaultWorkHours += ($nbWorkDaysForEachMonth * ($workWeekHours/count($ListOfWorkDays)));
                            }
                            break;
                        default:
                            $defaultWorkHours = $workWeekHours / $nbWorkDays;
                            break;
                    }
                } else {
                    $period = CarbonPeriod::create($items->min('start_at'), '1 week', $items->max('end_at'));
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

            else if ($request->date && $userId = $user->is_admin ? $request->user_id : $user->id) {
                $nbWorkDays = WorkHours::where('user_id', $userId)->where('is_active', 1)->count() || 1;
                $workWeekHours = WorkHours::where('user_id', $userId)->where('is_active', 1)->get()->map(function ($day) {
                    $morning = CarbonInterval::createFromFormat('H:i:s', $day->morning_ends_at)->subtract(CarbonInterval::createFromFormat('H:i:s', $day->morning_starts_at));
                    $afternoon = CarbonInterval::createFromFormat('H:i:s', $day->afternoon_ends_at)->subtract(CarbonInterval::createFromFormat('H:i:s', $day->afternoon_starts_at));
                    return $morning->add($afternoon)->totalHours;
                })->sum();
                $workDayHours = HoursController::getTargetWorkHours($userId, Carbon::createFromFormat('d-m-Y', $request->date)->locale('fr_FR')->dayName);

                $ListOfWorkHours= WorkHours::where('user_id', $userId)->where('is_active', 1)->get();

                $ListOfWorkDays=array();
                foreach($ListOfWorkHours as $day){
                    array_push($ListOfWorkDays,$day->day);
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
                            $nbWorkDaysPerMonth=HoursController::calculeNbWorkDaysPerMonth($request,$ListOfWorkDays,null,1);
                            $defaultWorkHours = $nbWorkDaysPerMonth * ($workWeekHours/count($ListOfWorkDays));
                            break;
                        case 'year':
                            $period = CarbonPeriod::create(Carbon::createFromFormat('d-m-Y', $request->date)->startOfYear(), '1 month', Carbon::createFromFormat('d-m-Y', $request->date)->endOfYear());
                            foreach ($period as $month) {
                                $moisCourant=$month->month;
                                $nbWorkDaysForEachMonth=HoursController::calculeNbWorkDaysPerMonth($request,$ListOfWorkDays,$moisCourant,0);
                                $defaultWorkHours += ($nbWorkDaysForEachMonth * ($workWeekHours/count($ListOfWorkDays)));
                            }
                            break;
                        default:
                            $defaultWorkHours = $workWeekHours / $nbWorkDays;
                            break;
                    }
                } else {
                    $period = CarbonPeriod::create($items->min('start_at'), '1 week', $items->max('end_at'));
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

        return collect(['stats' => $stats, 'id_user' => $request->user_id]);
    }

    protected function calculeNbWorkDaysPerMonth(Request $request, $ListOfWorkDays, $month, $showMonth){
        if ($showMonth){
            $premierJour = Carbon::createFromFormat('d-m-Y', $request->date)->firstOfMonth();
            $dernierJour= Carbon::createFromFormat('d-m-Y', $request->date)->lastOfMonth();
        }

        else{
            $premierJour = Carbon::createFromFormat('m', $month)->firstOfMonth();
            $dernierJour= Carbon::createFromFormat('m', $month)->lastOfMonth();
        }
        $countMonday = 0;
        $countTuesday = 0;
        $countWednesday = 0;
        $countThursday = 0;
        $countFriday = 0;
        $countSaturday = 0;
        $countSunday = 0;

        $periodWeek = CarbonPeriod::create($premierJour, '1 day', $dernierJour);
        foreach ($periodWeek as $day) {
            foreach($ListOfWorkDays as $workDay){
                if ($day->isMonday() && $workDay=="lundi"){
                    $countMonday++;
                }
                elseif ($day->isTuesday() && $workDay=="mardi"){
                    $countTuesday++;
                }
                elseif ($day->isWednesday() && $workDay=="mercredi"){
                    $countWednesday++;
                }
                elseif ($day->isThursday() && $workDay=="jeudi"){
                    $countThursday++;
                }
                elseif ($day->isFriday() && $workDay=="vendredi"){
                    $countFriday++;
                }
                elseif ($day->isSaturday() && $workDay=="samedi"){
                    $countSaturday++;
                }
                elseif ($day->isSunday() && $workDay=="dimanche"){
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
            'start_at' => $arrayRequest['start_at'],
            'end_at' => $arrayRequest['end_at'],
            'date' => $arrayRequest['date'],
        ]);


        // How many hour user worked this week
        $listDealingHour = array();
        $nb_worked_hours=0;
        $exist=false;
        $premierJour = Carbon::parse($arrayRequest['start_at'])->startOfWeek()->format('Y-m-d H:i');
        $dernierJour= Carbon::parse($arrayRequest['start_at'])->endOfWeek()->format('Y-m-d H:i');
        $periodWeek = CarbonPeriod::create($premierJour, '1 day', $dernierJour);
        foreach ($periodWeek as $day) {
            // Check if value in dealing_hours for this date
            $findDealingHour = DealingHours::where('user_id', $arrayRequest['user_id'])->where('date', $day)->first();
            if($findDealingHour != null){
                array_push($listDealingHour,$findDealingHour);
                $exist=true;
            }
            $nb_worked_hours += HoursController::getNbWorkedHours($arrayRequest['user_id'], 0, $day->format('Y-m-d'));
        }

        // Expected hours for this week
        $workWeekHours = WorkHours::where('user_id', $arrayRequest['user_id'])->where('is_active', 1)->get()->map(function ($day) {
            $morning = CarbonInterval::createFromFormat('H:i:s', $day->morning_ends_at)->subtract(CarbonInterval::createFromFormat('H:i:s', $day->morning_starts_at));
            $afternoon = CarbonInterval::createFromFormat('H:i:s', $day->afternoon_ends_at)->subtract(CarbonInterval::createFromFormat('H:i:s', $day->afternoon_starts_at));
            return $morning->add($afternoon)->totalHours;
        })->sum();
        if ($workWeekHours === 0) {
            throw new ApiException("Vérifier que l'utilisateur ai bien renseigné des horraires de travail pour la semaine.");
        }
        if (isset($arrayRequest['description'])) {
            $item->update(['description' => $arrayRequest['description']]);
        }

        if ($exist && ($nb_worked_hours - $workWeekHours == 0)){
            $listDealingHour[0]->delete();
            unset($listDealingHour[0]);
        }
        elseif ($exist) {
            // Update dealing_hour with difference between nb_worked_hours and $workWeekHours for overtime column
            $listDealingHour[0]->update(['overtimes' => ($nb_worked_hours - $workWeekHours)]);
        }
        // Else add new tuple in dealing_hours for this date
        elseif (empty($listDealingHour[0]) && ($nb_worked_hours - $workWeekHours != 0)) {
            //Create new tuple in dealing_hours with user_id, date and overtimes
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

        $item->update([
            'user_id' => $arrayRequest['user_id'],
            'project_id' => $arrayRequest['project_id'],
            'start_at' => $arrayRequest['start_at'],
            'end_at' => $arrayRequest['end_at'],
        ]);

        if (isset($arrayRequest['description'])) {
            $item->update(['description' => $arrayRequest['description']]);
        }

        // How many new hour user worked this week
        $listDealingHour=array();
        $nb_worked_hours=0;
        $premierJour = Carbon::parse($arrayRequest['start_at'])->startOfWeek()->format('Y-m-d H:i');
        $dernierJour= Carbon::parse($arrayRequest['start_at'])->endOfWeek()->format('Y-m-d H:i');
        $periodWeek = CarbonPeriod::create($premierJour, '1 day', $dernierJour);
        foreach ($periodWeek as $day) {
            // Check if value in dealing_hours for this date
            $findDealingHour = DealingHours::where('user_id', $arrayRequest['user_id'])->where('date', $day)->first();
            if($findDealingHour != null){
                array_push($listDealingHour,$findDealingHour);
            }
            $nb_worked_hours += HoursController::getNbWorkedHours($arrayRequest['user_id'], 0, $day->format('Y-m-d'));
        }
        if (!empty($listDealingHour[0]) && ($nb_worked_hours - $workWeekHours == 0)){
            $listDealingHour[0]->delete();
            unset($listDealingHour[0]);
        }
        elseif (!empty($listDealingHour[0])) {
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
        $worked_unavailabilities = $date ? Unavailability::where([['user_id', $user_id], ['starts_at', 'LIKE', '%' . $date . '%']])->get() : [];

        $nb_worked_hours = 0;

        if (!$worked_unavailabilities->isEmpty()) {

            foreach ($worked_unavailabilities as $wu) {
                $nb_worked_hours += Carbon::create($wu->ends_at)->diffInHours(Carbon::create($wu->starts_at));
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

        // How many hour worked this week
        $listDealingHour=array();
        $nb_worked_hours=0;
        $premierJour = Carbon::parse($item['start_at'])->startOfWeek()->format('Y-m-d H:i');
        $dernierJour= Carbon::parse($item['start_at'])->endOfWeek()->format('Y-m-d H:i');
        $periodWeek = CarbonPeriod::create($premierJour, '1 day', $dernierJour);
        foreach ($periodWeek as $day) {
            // Check if value in dealing_hours for this date
            $findDealingHour = DealingHours::where('user_id', $item['user_id'])->where('date', $day)->first();
            if($findDealingHour != null){
                array_push($listDealingHour,$findDealingHour);
            }
            $nb_worked_hours += HoursController::getNbWorkedHours($item['user_id'], 0, $day->format('Y-m-d'));
        }
        $nb_worked_hours -= $item['durationInFloatHour'];

        // Expected hours for this week
        $workWeekHours = WorkHours::where('user_id', $item['user_id'])->where('is_active', 1)->get()->map(function ($day) {
            $morning = CarbonInterval::createFromFormat('H:i:s', $day->morning_ends_at)->subtract(CarbonInterval::createFromFormat('H:i:s', $day->morning_starts_at));
            $afternoon = CarbonInterval::createFromFormat('H:i:s', $day->afternoon_ends_at)->subtract(CarbonInterval::createFromFormat('H:i:s', $day->afternoon_starts_at));
            return $morning->add($afternoon)->totalHours;
        })->sum();

        if (!empty($listDealingHour[0]) && ($nb_worked_hours - $workWeekHours == 0)){
            unset($listDealingHour[0]);
            $listDealingHour[0]->delete();
        }
        elseif(!empty($listDealingHour[0])){
            // Update dealing_hour with difference between nb_worked_hours and $target_work_hours for overtime column
            $listDealingHour[0]->update(['overtimes' => ($nb_worked_hours - $workWeekHours)]);
        }
        elseif (empty($listDealingHour[0]) && ($nb_worked_hours - $workWeekHours != 0)){
            //create dealig_hours for this week with difference between nb_worked_hours and $target_work_hours for overtime column
            $deallingHourItem = DealingHours::create(
                    ['user_id' => $item['user_id'], 'date' => $item['created_at'], 'overtimes' => ($nb_worked_hours - $workWeekHours)]
                );
        }

        return parent::destroyItem($item);
    }
}
