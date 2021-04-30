<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ApiException;
use App\User;
use App\Models\Unavailability;
use App\Models\WorkHours;
use App\Models\DealingHours;
use App\Models\Hours;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Carbon\CarbonInterval;

class UnavailabilityController extends BaseApiController
{
    protected static $index_load = ['user:users.id,firstname,lastname,email'];
    protected static $index_append = null;
    protected static $show_load = ['user:users.id,firstname,lastname,email'];
    protected static $show_append = null;

    protected static $store_validation_array = [
        'starts_at' => 'required|date',
        'ends_at' => 'required|date',
        'reason' => 'required'
    ];

    protected static $update_validation_array = [
        'starts_at' => 'required|date',
        'ends_at' => 'required|date',
        'reason' => 'required'
    ];

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        parent::__construct(Unavailability::class);
    }

    protected function extraIndexData(Request $request, $items, $nonPaginatedQuery)
    {
        return collect(['id_user' => $request->user_id]);
    }

    protected function filterIndexQuery(Request $request, $query)
    {
        if ($request->has('hours_taken_name')) {
            $query->where('reason', $request->hours_taken_name);
        }
        if ($request->has('date')) {
            try {
                $date = Carbon::createFromFormat('d-m-Y', $request->date);
                switch ($request->period_type) {
                    case 'week':
                        $query->whereBetween('starts_at', [$date->startOfWeek()->format('Y-m-d H:i:s'), $date->endOfWeek()->format('Y-m-d H:i:s')]);
                        break;
                    case 'month':
                        $query->whereMonth('starts_at', $date->month)->whereYear('starts_at', $date->year);
                        break;
                    case 'year':
                        $query->whereYear('starts_at', $date->year);
                        break;

                    default:
                        $query->whereDate('starts_at', $date);
                        break;
                }
            } catch (\Throwable $th) {
                throw new ApiException("Paramètre 'date' n'est pas valide.");
            }
        }
        if ($request->has('user_id')) {
            if (User::where('id', $request->user_id)->doesntExist()) {
                throw new ApiException("Paramètre 'user_id' n'est pas valide.");
            }
            $query->where('user_id', $request->user_id);
        } else {
            $query->where('user_id', Auth::id());
        }
        if (!$request->has('order_by')) {
            $query->orderBy('starts_at');
        }
    }

    protected function storeItem(array $arrayRequest)
    {
        if ($arrayRequest['user_id'] == null) {
            $arrayRequest['user_id'] = Auth::id();
        }

        $unavailabilities = Unavailability::where('user_id', $arrayRequest['user_id'])->orderby('starts_at', 'asc')->get();


        $arrayRequest_starts = Carbon::createFromFormat('Y-m-d H:i', $arrayRequest['starts_at'])->locale('fr_FR');
        $arrayRequest_ends = Carbon::createFromFormat('Y-m-d H:i', $arrayRequest['ends_at'])->locale('fr_FR');
        $duration = (floatval(explode(':', $arrayRequest_ends->format('H:i'))[0])) - (floatval(explode(':', $arrayRequest_starts->format('H:i'))[0]));

        // Overtimes verification
        $Overtimes = DealingHoursController::getOvertimes($arrayRequest['user_id'], true);
        $OvertimesToUse = $Overtimes['overtimes'] - $Overtimes['usedOvertimes'] - $Overtimes['payedOvertimes'];

        //hours worked verification
        $hoursWorked=Hours::where('user_id', $arrayRequest['user_id'])->get();
        foreach($hoursWorked as $hourWorked){
            $hourWorked_starts = Carbon::createFromFormat('Y-m-d H:i:s', $hourWorked['start_at'])->locale('fr_FR');
            $hourWorked_ends = Carbon::createFromFormat('Y-m-d H:i:s', $hourWorked['end_at'])->locale('fr_FR');
            if (($arrayRequest_starts->between($hourWorked_starts, $hourWorked_ends)
                        && $arrayRequest_ends->between($hourWorked_starts, $hourWorked_ends))
                    || ($hourWorked_starts->between($arrayRequest_starts, $arrayRequest_ends)
                        && $hourWorked_ends->between($arrayRequest_starts, $arrayRequest_ends))
                    || ($arrayRequest_starts == $hourWorked_starts->format('Y-m-d H:i')
                        && $arrayRequest_ends == $hourWorked_ends->format('Y-m-d H:i')
                    || ($arrayRequest_ends->gt($hourWorked_ends->format('Y-m-d H:i'))
                        && ($arrayRequest_starts->between($hourWorked_starts, $hourWorked_ends)
                            && $arrayRequest_starts->ne($hourWorked_ends)))
                    || ($arrayRequest_starts->lt($hourWorked_starts->format('Y-m-d H:i')) 
                        && ($arrayRequest_ends->between($hourWorked_starts, $hourWorked_ends) 
                        && $arrayRequest_ends->ne($hourWorked_starts))))
                ){
                    throw new ApiException("Vous ne pouvez pas ajouter l'indisponibilité car vous avez déjà des heures de travail enregistrées sur cette période.");
                }
        }
        if ($arrayRequest['reason'] == 'Utilisation heures supplémentaires' || $arrayRequest['reason'] == 'Heures supplémentaires payées') {
            if ($OvertimesToUse < $duration) {
                throw new ApiException("Vous ne disposez pas assez d'heures supplémentaires.");
            }
            if ($arrayRequest_starts->format("Y-m-d") != $arrayRequest_ends->format("Y-m-d") && $arrayRequest['reason'] != 'Heures supplémentaires payées') {
                throw new ApiException("Vous ne pouvez pas prendre d'heures supplémentaires sur plusieurs jours.");
            }
            // Expected hours for this day
            $day = $arrayRequest_starts->dayName;
            $workDuration = HoursController::getTargetWorkHours($arrayRequest['user_id'], $day);
            if ($workDuration === 0 && $arrayRequest['reason'] != 'Heures supplémentaires payées') {
                throw new ApiException("Il n'y a pas d'heures de travail prévues pour " . $day . ".");
            } else if ($workDuration < $duration && $arrayRequest['reason'] != 'Heures supplémentaires payées') {
                throw new ApiException("Vous ne pouvez pas utiliser plus d'heures supplémentaires que d'heures de travail prévues.");
            }
        }

        // Unavailability already existe or layering
        if (!$unavailabilities->isEmpty()) {
            foreach ($unavailabilities as $unavailability) {
                if ($unavailability['reason'] == "Heures supplémentaires payées") {
                    continue;
                }
                $unavailability_starts = Carbon::createFromFormat('Y-m-d H:i:s', $unavailability->starts_at);
                $unavailability_ends = Carbon::createFromFormat('Y-m-d H:i:s', $unavailability->ends_at);

                if (($arrayRequest_starts->between($unavailability_starts, $unavailability_ends)
                        && $arrayRequest_ends->between($unavailability_starts, $unavailability_ends))
                    || ($unavailability_starts->between($arrayRequest_starts, $arrayRequest_ends)
                        && $unavailability_ends->between($arrayRequest_starts, $arrayRequest_ends))
                    || ($arrayRequest_starts == $unavailability_starts->format('Y-m-d H:i')
                        && $arrayRequest_ends == $unavailability_ends->format('Y-m-d H:i'))
                ) {
                    // Vérifier qu'elle n'est pas englobé, dedans ou déjà présente
                    throw new ApiException("Une indisponibilité existe déjà sur cette période.");
                } else if (
                    $arrayRequest_ends->gt($unavailability_ends->format('Y-m-d H:i'))
                    && ($arrayRequest_starts->between($unavailability_starts, $unavailability_ends)
                        && $arrayRequest_starts->ne($unavailability_ends))
                ) {
                    // Vérifier que ça ne mort pas par le starts_at
                    throw new ApiException("Le début de l'indisponibilité dépasse sur une autre.");
                } else if ($arrayRequest_starts->lt($unavailability_starts->format('Y-m-d H:i')) && ($arrayRequest_ends->between($unavailability_starts, $unavailability_ends) && $arrayRequest_ends->ne($unavailability_starts))) {
                    // Verifier que ça ne mort pas par le ends_at
                    throw new ApiException("La fin de l'indisponibilité dépasse sur une autre.");
                }
            }
        }

        // Ajouter l'indisponibilité
        if (in_array($arrayRequest['reason'], ['Congés payés', 'Jours fériés', 'Période de cours'])) {
            return $this->setPaidHolidays($arrayRequest, $arrayRequest_starts, $arrayRequest_ends);
        } else {
            $item = Unavailability::create([
                'starts_at' => $arrayRequest['starts_at'],
                'ends_at' => $arrayRequest['ends_at'],
                'reason' => $arrayRequest['reason'],
                'user_id' => $arrayRequest['user_id'],
            ]);
            $this->addOrUpdateOvertimes([$item]);
            return $item;
        }
    }

    protected function updateItem($item, array $arrayRequest)
    {
        if ($arrayRequest['user_id'] == null) {
            $arrayRequest['user_id'] = Auth::id();
        }
        $arrayRequest_starts = Carbon::createFromFormat('Y-m-d H:i', $arrayRequest['starts_at']);
        $arrayRequest_ends = Carbon::createFromFormat('Y-m-d H:i', $arrayRequest['ends_at']);

        $unavailabilities = Unavailability::where('user_id', $arrayRequest['user_id'])->orderby('starts_at', 'asc')->get();

        //hours worked verification
        $hoursWorked=Hours::where('user_id', $arrayRequest['user_id'])->get();
        foreach($hoursWorked as $hourWorked){
            $hourWorked_starts = Carbon::createFromFormat('Y-m-d H:i:s', $hourWorked['start_at'])->locale('fr_FR');
            $hourWorked_ends = Carbon::createFromFormat('Y-m-d H:i:s', $hourWorked['end_at'])->locale('fr_FR');
            if (($arrayRequest_starts->between($hourWorked_starts, $hourWorked_ends)
                        && $arrayRequest_ends->between($hourWorked_starts, $hourWorked_ends))
                    || ($hourWorked_starts->between($arrayRequest_starts, $arrayRequest_ends)
                        && $hourWorked_ends->between($arrayRequest_starts, $arrayRequest_ends))
                    || ($arrayRequest_starts == $hourWorked_starts->format('Y-m-d H:i')
                        && $arrayRequest_ends == $hourWorked_ends->format('Y-m-d H:i')
                    || ($arrayRequest_ends->gt($hourWorked_ends->format('Y-m-d H:i'))
                        && ($arrayRequest_starts->between($hourWorked_starts, $hourWorked_ends)
                            && $arrayRequest_starts->ne($hourWorked_ends)))
                    || ($arrayRequest_starts->lt($hourWorked_starts->format('Y-m-d H:i')) 
                        && ($arrayRequest_ends->between($hourWorked_starts, $hourWorked_ends) 
                        && $arrayRequest_ends->ne($hourWorked_starts))))
                ){
                    throw new ApiException("Vous ne pouvez pas modifier l'indisponibilité car vous avez déjà des heures de travail enregistrées sur cette période.");
                }
        }
        
        $passage = 0;

        foreach ($unavailabilities as $unavailability) {
            if ($unavailability['reason'] == "Heures supplémentaires payées") {
                continue;
            }
            $unavailability_starts = Carbon::createFromFormat('Y-m-d H:i:s', $unavailability->starts_at);
            $unavailability_ends = Carbon::createFromFormat('Y-m-d H:i:s', $unavailability->ends_at);

            $passage++;

            if (
                ($item->id != intval($unavailability->id))
                && (($arrayRequest_starts->between($unavailability_starts, $unavailability_ends)
                    && $arrayRequest_ends->between($unavailability_starts, $unavailability_ends))
                    || ($unavailability_starts->between($arrayRequest_starts, $arrayRequest_ends)
                        && $unavailability_ends->between($arrayRequest_starts, $arrayRequest_ends))
                    || ($arrayRequest_starts == $unavailability_starts->format('Y-m-d H:i')
                        && $arrayRequest_ends == $unavailability_ends->format('Y-m-d H:i')))
            ) {
                // Vérifier qu'elle n'est pas englobé, dedans ou déjà présente
                throw new ApiException("Une indisponibilité existe déjà sur cette période.");
            } else if (
                ($item->id != intval($unavailability->id))
                && ($arrayRequest_ends->gt($unavailability_ends->format('Y-m-d H:i'))
                    && ($arrayRequest_starts->between($unavailability_starts, $unavailability_ends)
                        && $arrayRequest_starts->ne($unavailability_ends)))
            ) {
                // Vérifier que ça ne mort pas par le starts_at
                throw new ApiException("Le début de l'indisponibilité dépasse sur une autre.");
            } else if (
                ($item->id != intval($unavailability->id))
                && ($arrayRequest_starts->lt($unavailability_starts->format('Y-m-d H:i'))
                    && ($arrayRequest_ends->between($unavailability_starts, $unavailability_ends)
                        && $arrayRequest_ends->ne($unavailability_starts)))
            ) {
                // Verifier que ça ne mort pas par le ends_at
                throw new ApiException("La fin de l'indisponibilité dépasse sur une autre.");
            }
        }

        $old_item = clone $item;
        // Update l'indisponibilité
        $item->update([
            'starts_at' => $arrayRequest['starts_at'],
            'ends_at' => $arrayRequest['ends_at'],
            'reason' => $arrayRequest['reason']
        ]);

        //no overtime change
        if (!in_array($old_item->reason, ['Congés payés', 'Jours fériés', 'Période de cours', 'Utilisation heures supplémentaires']) && !in_array($item->reason, ['Congés payés', 'Jours fériés', 'Période de cours', 'Utilisation heures supplémentaires'])) {

            //Do nothing
        }
        //add Overtime
        else if (!in_array($old_item->reason, ['Congés payés', 'Jours fériés', 'Période de cours', 'Utilisation heures supplémentaires']) && in_array($item->reason, ['Congés payés', 'Jours fériés', 'Période de cours', 'Utilisation heures supplémentaires'])) {

            $this->addOrUpdateOvertimes([$item]);
        }
        //del Overtime
        else if (in_array($old_item->reason, ['Congés payés', 'Jours fériés', 'Période de cours', 'Utilisation heures supplémentaires']) && !in_array($item->reason, ['Congés payés', 'Jours fériés', 'Période de cours', 'Utilisation heures supplémentaires'])) {

            $this->delOvertimes([$old_item]);
        }
        //compare hours for addOrDelOvertime
        else {
            $this->delOvertimes([$old_item]);
            $this->addOrUpdateOvertimes([$item]);
        }

        return $item;
    }

    protected function destroyItem($item)
    {

        $this->delOvertimes([$item]);

        return parent::destroyItem($item);
    }

    /**
     * Set paid holidays unavailabilities.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    private function setPaidHolidays($arrayRequest, $arrayRequest_starts, $arrayRequest_ends)
    {
        $workDuration = HoursController::getTargetWorkHours($arrayRequest['user_id'], $arrayRequest['starts_at']);
        $date_start = $arrayRequest_starts->format('Y-m-d');
        $date_end = $arrayRequest_ends->format('Y-m-d');
        $datetime_start = Carbon::parse($arrayRequest['starts_at']);
        $datetime_end = Carbon::parse($arrayRequest['ends_at']);


        //pour chaque jours je note les noms de jours dans un tableau
        $dayDuration = $arrayRequest_starts->startOfDay()->diffInDays($arrayRequest_ends->endOfDay());
        $days = [];
        $workingDaysNames = [];

        for ($i = 0; $i <= $dayDuration; $i++) {
            $date = Carbon::create($date_start)->addDays($i)->format('Y-m-d');
            $dayName = Carbon::create($date_start)->addDays($i)->locale('fr_FR')->dayName;
            array_push($days, $date);
            if (!in_array($dayName, $workingDaysNames)) {
                array_push($workingDaysNames, $dayName);
            }
        }

        // Au moins un jours où il doit travailler doit etre dans le tableau
        $userWorkingDays = [];
        $userWorkingHours = [];
        foreach ($workingDaysNames as $key => $dN) {
            $daysWorkinghours = WorkHours::where([['user_id', $arrayRequest['user_id']], ['is_active', 1], ['day', $dN]])->get();
            foreach ($daysWorkinghours as $key => $dWH) {
                // On ajout les horraires de travail
                array_push($userWorkingDays, $dWH->day);
                array_push($userWorkingHours, $dWH);
            }
        }
        if (count($userWorkingDays) <= 0) {
            throw new ApiException("Vous ne travaillez pas sur cette période.");
        }

        // Pour chaques $days je regarde s'il est dans $userWorkingDays

        $itemIds = [];
        $items = [];
        // return response()->json(['error' => [$days, $userWorkingDays, $userWorkingHours]], 400);
        foreach ($days  as $key => $d) {
            //d est il un jour ou je travaille ?
            $dayTemp = Carbon::create($d)->locale('fr_FR')->dayName;
            if (in_array($dayTemp, $userWorkingDays)) {
                $index = array_search($dayTemp, array_column($userWorkingHours, 'day'));
                $dayWorkingHours = $userWorkingHours[$index];


                //Si horraire le matin
                if ($dayWorkingHours->morning_starts_at !== null && $dayWorkingHours->morning_ends_at != null) {

                    $new_request = [];
                    $morning_starts_at = Carbon::parse($d . " " . $dayWorkingHours->morning_starts_at);
                    $morning_ends_at = Carbon::parse($d . " " . $dayWorkingHours->morning_ends_at);

                    //Si la periode est comprise et ou englobe le matin on ajoute une indispo
                    if ($datetime_start <= $morning_starts_at) {
                        $new_request["starts_at"] = $morning_starts_at;
                    } else if ($datetime_start < $morning_ends_at) {
                        $new_request["starts_at"] = $datetime_start;
                    }

                    if ($datetime_end >= $morning_ends_at) {
                        $new_request["ends_at"] = $morning_ends_at;
                    } else if ($datetime_end > $morning_starts_at) {
                        $new_request["ends_at"] = $datetime_end;
                    }

                    if (isset($new_request['starts_at']) && isset($new_request['ends_at'])) {
                        $new_request['user_id'] =  $arrayRequest['user_id'];
                        $new_request['reason'] =  $arrayRequest['reason'];

                        $item = Unavailability::create($new_request);
                        array_push($items, $item);
                        array_push($itemIds, $item->id);
                    }
                }

                //Si horraire l'après-midi
                if ($dayWorkingHours->afternoon_starts_at !== null && $dayWorkingHours->afternoon_ends_at != null) {

                    $new_request = [];
                    $afternoon_starts_at = Carbon::parse($d . " " . $dayWorkingHours->afternoon_starts_at);
                    $afternoon_ends_at = Carbon::parse($d . " " . $dayWorkingHours->afternoon_ends_at);

                    //Si la periode est comprise et ou englobe le matin on ajoute une indispo
                    if ($datetime_start <= $afternoon_starts_at) {
                        $new_request["starts_at"] = $afternoon_starts_at;
                    } else if ($datetime_start < $afternoon_ends_at) {
                        $new_request["starts_at"] = $datetime_start;
                    }

                    if ($datetime_end >= $afternoon_ends_at) {
                        $new_request["ends_at"] = $afternoon_ends_at;
                    } else if ($datetime_end > $afternoon_starts_at) {
                        $new_request["ends_at"] = $datetime_end;
                    }

                    if (isset($new_request['starts_at']) && isset($new_request['ends_at'])) {
                        $new_request['user_id'] =  $arrayRequest['user_id'];
                        $new_request['reason'] =  $arrayRequest['reason'];

                        $item = Unavailability::create($new_request);
                        array_push($items, $item);
                        array_push($itemIds, $item->id);
                    }
                }
            }
        }

        if (empty($items)) {
            throw new ApiException("Indisponibilité saisie hors des horaires de travail.");
        }

        $this->addOrUpdateOvertimes($items);

        return Unavailability::whereIn('id', $itemIds)->get();
    }

    private function addOrUpdateOvertimes($unavailabilities)
    {

        foreach ($unavailabilities as $unavailability) {

            $timeToAdd = Carbon::parse($unavailability->ends_at)->floatDiffInHours(Carbon::parse($unavailability->starts_at));
            $weekOvertimes = DealingHours::where('user_id', $unavailability->user_id)->where('date', Carbon::parse($unavailability->starts_at)->startOfWeek()->format('Y-m-d'))->first();

            if (!$weekOvertimes) {
                $weekOvertimes = DealingHours::create(['user_id' => $unavailability->user_id, 'date' => Carbon::parse($unavailability->starts_at)->startOfWeek()->format('Y-m-d')]);
                $workWeekHours = WorkHours::where('user_id', $unavailability->user_id)->where('is_active', 1)->get()->map(function ($day) {
                    $morning = CarbonInterval::createFromFormat('H:i:s', $day->morning_ends_at)->subtract(CarbonInterval::createFromFormat('H:i:s', $day->morning_starts_at));
                    $afternoon = CarbonInterval::createFromFormat('H:i:s', $day->afternoon_ends_at)->subtract(CarbonInterval::createFromFormat('H:i:s', $day->afternoon_starts_at));
                    return $morning->add($afternoon)->totalHours;
                })->sum();

                $weekOvertimes->overtimes=-$workWeekHours;
            }
            $weekOvertimes->overtimes += $timeToAdd;
            $weekOvertimes->update();
        }
    }

    private function delOvertimes($unavailabilities)
    {

        foreach ($unavailabilities as $unavailability) {

            $timeToDel = Carbon::parse($unavailability->ends_at)->floatDiffInHours(Carbon::parse($unavailability->starts_at));
            $weekOvertimes = DealingHours::where('user_id', $unavailability->user_id)->where('date', Carbon::parse($unavailability->starts_at)->startOfWeek()->format('Y-m-d'))->first();

            if (!$weekOvertimes) {
                $weekOvertimes = DealingHours::create(['user_id' => $unavailability->user_id, 'date' => Carbon::parse($unavailability->starts_at)->startOfWeek()->format('Y-m-d')]);
            }
            $weekOvertimes->overtimes -= $timeToDel;
            $weekOvertimes->update();
        }
    }
}
