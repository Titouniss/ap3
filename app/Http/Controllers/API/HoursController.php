<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Hours;
use App\Models\DealingHours;
use App\Models\Project;
use App\Models\WorkHours;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Carbon\CarbonPeriod;
use DatePeriod;
use Illuminate\Support\Facades\Auth;
use Validator;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class HoursController extends Controller
{
    public $successStatus = 200;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $items = Hours::where('user_id', $user->id);
        if ($user->hasRole('superAdmin')) {
            $items = Hours::select('*');
            if ($request->user_id) {
                $items->where('user_id', $request->user_id);
            }
        }
        if ($request->project_id) {
            $items->where('project_id', $request->project_id);
        }

        if ($request->date) {
            switch ($request->period_type) {
                case 'week':
                    $items->whereBetween('date', [Carbon::createFromFormat('d-m-Y', $request->date)->startOfWeek(), Carbon::createFromFormat('d-m-Y', $request->date)->endOfWeek()]);
                    break;
                case 'month':
                    $items->whereMonth('date', Carbon::createFromFormat('d-m-Y', $request->date)->month);
                    break;
                case 'year':
                    $items->whereYear('date', Carbon::createFromFormat('d-m-Y', $request->date)->year);
                    break;

                default:
                    $items->whereDate('date', Carbon::createFromFormat('d-m-Y', $request->date));
                    break;
            }
        }

        $items = $items->with('project', 'user')->orderBy('start_at')->get();

        // Stats
        $stats = [];
        if (!$items->isEmpty()) {
            $stats['total'] = CarbonInterval::hours(0);
            foreach ($items as $item) {
                $stats['total']->add(CarbonInterval::createFromFormat('H:i:s', $item->duration));
            }

            $stats['total'] = $stats['total']->totalHours;

            if ($userId = $user->hasRole('superAdmin') ? $request->user_id : $user->id) {
                $nbWorkDays = WorkHours::where('user_id', $userId)->where('is_active', 1)->count() || 1;
                $workWeekHours = WorkHours::where('user_id', $userId)->where('is_active', 1)->get()->map(function ($day) {
                    $morning = CarbonInterval::createFromFormat('H:i:s', $day->morning_ends_at)->subtract(CarbonInterval::createFromFormat('H:i:s', $day->morning_starts_at));
                    $afternoon = CarbonInterval::createFromFormat('H:i:s', $day->afternoon_ends_at)->subtract(CarbonInterval::createFromFormat('H:i:s', $day->afternoon_starts_at));
                    return $morning->add($afternoon)->totalHours;
                })->sum();

                $defaultWorkHours = 0;
                if ($request->date) {
                    switch ($request->period_type) {
                        case 'week':
                            $defaultWorkHours = $workWeekHours;
                            break;
                        case 'month':
                            $defaultWorkHours = ((Carbon::createFromFormat('d-m-Y', $request->date)->daysInMonth * $nbWorkDays) / 7) * ($workWeekHours / $nbWorkDays);
                            break;
                        case 'year':
                            $period = CarbonPeriod::create(Carbon::createFromFormat('d-m-Y', $request->date)->startOfYear(), '1 month', Carbon::createFromFormat('d-m-Y', $request->date)->endOfYear());
                            foreach ($period as $month) {
                                $defaultWorkHours += (($month->daysInMonth * $nbWorkDays * $workWeekHours) / 7) * ($workWeekHours / $nbWorkDays);
                            }
                            break;
                        default:
                            $defaultWorkHours = $workWeekHours / $nbWorkDays;
                            break;
                    }
                } else {
                    $period = CarbonPeriod::create($items->min('date'), '1 week', $items->max('date'));
                    foreach ($period as $week) {
                        $defaultWorkHours += $workWeekHours;
                    }
                }
                //On veut connaitre également le nombre d'heures effectuées en moins.
                if ($stats['total'] > $defaultWorkHours) {
                    $stats['overtime'] = $stats['total'] - $defaultWorkHours;
                }
                else {
                    $stats['lost_time'] = $defaultWorkHours - $stats['total'];
                }
            }
        }

        return response()->json(['success' => $items, 'stats' => $stats], $this->successStatus);
    }

    /**
     * Show the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $item = Hours::where('id', $id)->with('project', 'user')->first();
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
        $validator = Validator::make($arrayRequest, [
            'user_id' => 'required',
            'project_id' => 'required',
            'start_at' => 'required',
            'end_at' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], $this->successStatus);
        }

        //Set duration
        $start_at = Carbon::parse($arrayRequest['start_at']);
        $end_at = Carbon::parse($arrayRequest['end_at']);

        $parseDuration = $start_at->floatDiffInHours($end_at);     

        if($parseDuration === 0) {
            return response()->json(['error' => "Veuillez inserer une durée"], $this->successStatus); 
        }

        // How many hour user worked this day
        $nb_worked_hours = HoursController::getNbWorkedHours($arrayRequest['user_id'], $parseDuration, $arrayRequest['date']);

        // Expected hours for this day
        $target_work_hours = HoursController::getTargetWorkHours($arrayRequest['user_id'], $arrayRequest['date']);
        if ($target_work_hours === 0) {
            setlocale(LC_TIME, "fr_FR", "French");
            $target_day = strftime("%A", strtotime($arrayRequest['date']));
            return response()->json(['error' => "Vérifier que l'utilisateur ai bien renseigné des horraires de travail pour le " . $target_day], $this->successStatus);
        }

        // Check if user have already hour on this new entry period
        $noLayering = $this->checkIfNewEntryLayeringOtherHours($arrayRequest['user_id'], $start_at, $end_at);

        if(!$noLayering){
            return response()->json(['error' => "Attention, vous ne pouvez pas superposer deux horaires"],  $this->successStatus);
        }
        

        // Compare to user_workhours to define overtimes or not
        if ($nb_worked_hours > $target_work_hours) {
            // Check if value in dealing_hours for this date
            $findDealingHour = DealingHours::where('date', $arrayRequest['date'])->first();

            if(empty($findDealingHour) === false) {
                // Update dealing_hour with difference between nb_worked_hours and $target_work_hours for overtime column
                $item = Hours::create($arrayRequest);
                DealingHours::where('date', $arrayRequest['date'])->update(['overtimes' => ($nb_worked_hours - $target_work_hours)]);
            }
            // Else add new tuple in dealing_hours for this date 
            else {
                //Create new tuple in dealing_hours with user_id, date and overtimes
                $item = Hours::create($arrayRequest);
                $deallingHourItem = DealingHours::create(
                    ['user_id' => $arrayRequest['user_id'], 'date' => $arrayRequest['date'], 'overtimes' => ($nb_worked_hours - $target_work_hours)]                
                );
            }
        } else { 
            $arrayRequest['start_at'] = $start_at->ToDateTimeString();
            $arrayRequest['end_at'] = $end_at->ToDateTimeString();
            $item = Hours::create($arrayRequest);
        }

        $item = Hours::where('id', $item->id)->with('project', 'user')->first();

        return response()->json(['success' => $item], $this->successStatus);
    }

    private function checkIfNewEntryLayeringOtherHours($user_id, $start_at, $end_at, $hour_id = null){

        $test = $start_at->format('Y-m-d');
        $usersHours = $hour_id ? Hours::where('user_id', $user_id)->where('start_at', 'like', '%' . $start_at->format('Y-m-d') . '%')->where('id', '!=', $hour_id)->get()
                      : Hours::where('user_id', $user_id)->where('start_at', 'like', '%' . $start_at->format('Y-m-d') . '%')->get();

        if(count($usersHours) > 0){

            $newPeriod = CarbonPeriod::create($start_at, $end_at);

            foreach($usersHours as $hour){

                $hourPeriod = CarbonPeriod::create($hour->start_at, $hour->end_at);
    
                if( ($newPeriod->contains($hour->start_at) && ($hour->start_at != $start_at && $hour->start_at != $end_at)) 
                    || ($newPeriod->contains($hour->end_at) && ($hour->end_at != $start_at && $hour->end_at != $end_at)) 
                    || ($hourPeriod->contains($start_at) && $hourPeriod->contains($end_at))
                ){

                    return false;
                }
            }

        }
        else{
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
    private function getNbWorkedHours($user_id, $duration, $date)
    {
        // How many hour user worked this day
        $worked_hours = $date ? Hours::where([['user_id', $user_id], ['start_at', 'LIKE', '%' . $date . '%']])->get() : [];

        if(!$worked_hours->isEmpty()){
            $nb_worked_hours = 0;

            foreach ($worked_hours as $wh) {
                $nb_worked_hours += $wh->durationInFloatHour;
            }
            // Add current insert work hours
            return $nb_worked_hours += $duration;
        } else {
            return $nb_worked_hours = $duration;
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @param  string  $date
     * @return \Illuminate\Http\Response
     */
    public static function getTargetWorkHours($user_id, $date)
    {
        // Expected hours for this day
        setlocale(LC_TIME, "fr_FR", "French");
        $target_day = strftime("%A", strtotime($date));

        $work_hours = WorkHours::where([['user_id', $user_id], ['day', $target_day]])->select('morning_starts_at', 'morning_ends_at', 'afternoon_starts_at', 'afternoon_ends_at')->first();
        
        if (!empty($work_hours)) {
            if ($work_hours['morning_starts_at'] !== null && $work_hours['morning_ends_at'] !== null) {
                $parts = explode(':', $work_hours['morning_starts_at']);
                $morning_starts_at = $parts[0] + $parts[1]/60*100 / 100;
                $parts = explode(':', $work_hours['morning_ends_at']);
                $morning_ends_at = $parts[0] + $parts[1]/60*100 / 100;
            } else {
                $morning_starts_at = 0;
                $morning_ends_at = 0;
            }
            if ($work_hours['afternoon_starts_at'] !== null && $work_hours['afternoon_ends_at'] !== null) {
            $parts = explode(':', $work_hours['afternoon_starts_at']);
            $afternoon_starts_at = $parts[0] + $parts[1]/60*100 / 100;
            $parts = explode(':', $work_hours['afternoon_ends_at']);
            $afternoon_ends_at = $parts[0] + $parts[1]/60*100 / 100;
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
            'user_id' => 'required',
            'project_id' => 'required',
            'start_at' => 'required',
            'end_at' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        // Update user hour have worke_hour for this day ? 
        $target_work_hours = HoursController::getTargetWorkHours($arrayRequest['user_id'], $arrayRequest['date']);
            if ($target_work_hours === 0) {
                setlocale(LC_TIME, "fr_FR", "French");
                $target_day = strftime("%A", strtotime($arrayRequest['date']));
                return response()->json(['error' => "Vérifier que l'utilisateur ai bien renseigné des horraires de travail pour le $target_day"], $this->successStatus);
            }

        // Check if user have already hour on this new entry period
        $start_at = Carbon::parse($arrayRequest['start_at']);
        $end_at = Carbon::parse($arrayRequest['end_at']);

        $noLayering = $this->checkIfNewEntryLayeringOtherHours($arrayRequest['user_id'], $start_at, $end_at, $arrayRequest['id']);
        if(!$noLayering){
            return response()->json(['error' => "Attention, vous ne pouvez pas superposer deux horaires"], $this->successStatus);
        }

        // get old hour data
        $old_hours = Hours::where('id', $id)->first();
        $old_date = Carbon::parse($old_hours['start_at']);
        $old_hours['date'] = $old_date->format('Y-m-d');

        $update = Hours::where('id', $id)
            ->update([
                'user_id' => $arrayRequest['user_id'],
                'project_id' => $arrayRequest['project_id'],
                'start_at' => $arrayRequest['start_at'],
                'end_at' => $arrayRequest['end_at'],
                'description' => $arrayRequest['description'],
            ]);
        
            //reset old overtimes compteur
            // How many old hour worked this day
            $nb_worked_hours = HoursController::getNbWorkedHours($old_hours['user_id'], 0, $old_hours['date']);

            // Expected hours for old day
            $target_work_hours = HoursController::getTargetWorkHours($old_hours['user_id'], $old_hours['date']);

            // Check if value in dealing_hours for old date
            $findDealingHour = DealingHours::where([['date', $old_hours['date']], ['user_id', $old_hours['user_id']]])->first();

            if ($nb_worked_hours > $target_work_hours) {
                // Update dealing_hour with difference between nb_worked_hours and $target_work_hours for overtime column
                DealingHours::where('date', $old_hours['date'])->update(['overtimes' => ($nb_worked_hours - $target_work_hours)]);
            } else {
                // Not empty and no used_hour ? Delete dealing_hour tuple
                if ($findDealingHour['used_hours'] === 0 ) {
                    $item = DealingHours::findOrFail($findDealingHour['id']);
                    $item->delete();
                }
                else {
                    DealingHours::where([['date', $old_hours['date']], ['user_id', $old_hours['user_id']]])->update(['overtimes' => 0]);
                }
            }

            //reset new overtimes compteur
            // How many new hour worked this day
            $nb_worked_hours = HoursController::getNbWorkedHours($arrayRequest['user_id'], 0, $arrayRequest['date']);

            // Expected hours for this day
            $target_work_hours = HoursController::getTargetWorkHours($arrayRequest['user_id'], $arrayRequest['date']);

            // Check if value in dealing_hours for this date
            $findDealingHour = DealingHours::where([['date', $arrayRequest['date']], ['user_id', $arrayRequest['user_id']]])->first();

            if ($nb_worked_hours > $target_work_hours) {
                if(!empty($findDealingHour)) {
                    // Update dealing_hour with difference between nb_worked_hours and $target_work_hours for overtime column
                    DealingHours::where('date', $arrayRequest['date'])->update(['overtimes' => ($nb_worked_hours - $target_work_hours)]);
                }                
                else {
                    //Create new tuple in dealing_hours with user_id, date and overtimes
                    $deallingHourItem = DealingHours::create(
                        ['user_id' => $arrayRequest['user_id'], 'date' => $arrayRequest['date'], 'overtimes' => ($nb_worked_hours - $target_work_hours)]                
                    );
                }
            }
        
        if ($update) {
            $item = Hours::find($id)->load('project', 'user');
            return response()->json(['success' => $item], $this->successStatus);
        } else {
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
        $item = Hours::findOrFail($id);
        $date = Carbon::parse($item->start_at);
        $item->delete();

        //reset overtimes compteur for this user and date
        // How many hour worked this day
        $nb_worked_hours = HoursController::getNbWorkedHours($item->user_id, 0, $date->format('Y-m-d'));

        // Expected hours for this day
        $target_work_hours = HoursController::getTargetWorkHours($item->user_id, $item->date);

        // Check if value in dealing_hours for old date
        $findDealingHour = DealingHours::where([['date', $item->date], ['user_id', $item->user_id]])->first();

        if ($nb_worked_hours > $target_work_hours) {
            // Update dealing_hour with difference between nb_worked_hours and $target_work_hours for overtime column
            DealingHours::where('date', $item->date)->update(['overtimes' => ($nb_worked_hours - $target_work_hours)]);
        } else {
            // Not empty and no used_hour ? Delete dealing_hour tuple
            if ($findDealingHour['used_hours'] === 0 ) {
                $dealing_hour = DealingHours::findOrFail($findDealingHour['id']);
                $dealing_hour->delete();
            }
            else {
                DealingHours::where([['date', $item->date], ['user_id', $item->user_id]])->update(['overtimes' => 0]);
            }
        }

        return '';
    }
}
