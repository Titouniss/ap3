<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ApiException;
use App\User;
use App\Models\Unavailability;
use App\Models\WorkHours;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

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

    protected function extraIndexData(Request $request, $items)
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
        }
        else {
            $query->where('user_id', Auth::id());
        }
        if (!$request->has('order_by')) {
            $query->orderBy('starts_at');
        }
    }

    protected function storeItem(array $arrayRequest)
    {       
        if($arrayRequest['user_id'] == null){
            $arrayRequest['user_id'] = Auth::id();            
        }

        $unavailabilities = Unavailability::where('user_id', $arrayRequest['user_id'])->orderby('starts_at', 'asc')->get();

        
        $arrayRequest_starts = Carbon::createFromFormat('Y-m-d H:i', $arrayRequest['starts_at'])->locale('fr_FR');
        $arrayRequest_ends = Carbon::createFromFormat('Y-m-d H:i', $arrayRequest['ends_at'])->locale('fr_FR');
        $duration = (floatval(explode(':', $arrayRequest_ends->format('H:i'))[0])) - (floatval(explode(':', $arrayRequest_starts->format('H:i'))[0]));

        // Overtimes verification
        $Overtimes = DealingHoursController::getOvertimes($arrayRequest['user_id'],true);
        $OvertimesToUse = $Overtimes['overtimes'] - $Overtimes['usedOvertimes'] - $Overtimes['payedOvertimes'];

        if ($arrayRequest['reason'] == 'Utilisation heures suplémentaires' || $arrayRequest['reason'] == 'Heures suplémentaires payées') {
            if ($OvertimesToUse < $duration) {
                throw new ApiException("Vous ne disposez pas assez d'heures supplémentaires.");
            }
            if($arrayRequest_starts->format("Y-m-d") != $arrayRequest_ends->format("Y-m-d")){
                throw new ApiException("Vous ne pouvez pas prendre d'heures supplémentaires sur plusieurs jours.");
            }
            // Expected hours for this day
            $day=$arrayRequest_starts->dayName;
            $workDuration = HoursController::getTargetWorkHours($arrayRequest['user_id'], $day);
            if ($workDuration === 0) {
                throw new ApiException("Il n'y a pas d'heures de travail prévues pour ". $day . ".");
            } else if ($workDuration < $duration) {
                throw new ApiException("Vous ne pouvez pas utiliser plus d'heures supplémentaires que d'heures de travail prévues.");
            }
        }

        // Unavailability already existe or layering
        if (!$unavailabilities->isEmpty()) {
            foreach ($unavailabilities as $unavailability) {
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
        if (in_array($arrayRequest['reason'], ['Congés payés', 'Jours fériés'])) {
            return $this->setPaidHolidays($arrayRequest, $arrayRequest_starts, $arrayRequest_ends);
        } else {
            return Unavailability::create([
                'starts_at' => $arrayRequest['starts_at'],
                'ends_at' => $arrayRequest['ends_at'],
                'reason' => $arrayRequest['reason'],
                'user_id' => $arrayRequest['user_id'],
            ]);
        }
    }

    protected function updateItem($item, array $arrayRequest)
    {       
        if($arrayRequest['user_id'] == null){
            $arrayRequest['user_id'] = Auth::id();            
        }        
        $arrayRequest_starts = Carbon::createFromFormat('Y-m-d H:i', $arrayRequest['starts_at']);
        $arrayRequest_ends = Carbon::createFromFormat('Y-m-d H:i', $arrayRequest['ends_at']);

        $unavailabilities = Unavailability::where('user_id', $arrayRequest['user_id'])->orderby('starts_at', 'asc')->get();

        $passage = 0;

        foreach ($unavailabilities as $unavailability) {
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

        // Ajouter l'indisponibilité
        $item->update([
            'starts_at' => $arrayRequest['starts_at'],
            'ends_at' => $arrayRequest['ends_at'],
            'reason' => $arrayRequest['reason']
        ]);
        return $item;
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

        //pour chaque jours je note les noms de jours dans un tableau
        $dayDuration = $arrayRequest_starts->diffInDays($arrayRequest_ends);
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
        // return response()->json(['error' => [$days, $userWorkingDays, $userWorkingHours]], 400);
        foreach ($days  as $key => $d) {
            //d est il un jour ou je travaille ?
            $dayTemp = Carbon::create($d)->locale('fr_FR')->dayName;
            if (in_array($dayTemp, $userWorkingDays)) {
                $index = array_search($dayTemp, array_column($userWorkingHours, 'day'));
                $dayWorkingHours = $userWorkingHours[$index];

                // si travail le matin on ajoute une indispo égale au temps de travail et au même heures
                if ($dayWorkingHours->morning_starts_at !== null && $dayWorkingHours->morning_ends_at != null) {
                    $arrayRequest["starts_at"] = $d . " " . $dayWorkingHours->morning_starts_at;
                    $arrayRequest["ends_at"] = $d . " " . $dayWorkingHours->morning_ends_at;

                    array_push($itemIds, Unavailability::create($arrayRequest)->id);
                }

                // si travail l'après midi on ajoute une indispo égale au temps de travail et au même heures
                if ($dayWorkingHours->afternoon_starts_at !== null && $dayWorkingHours->afternoon_ends_at != null) {
                    $arrayRequest["starts_at"] = $d . " " . $dayWorkingHours->afternoon_starts_at;
                    $arrayRequest["ends_at"] = $d . " " . $dayWorkingHours->afternoon_ends_at;

                    array_push($itemIds, Unavailability::create($arrayRequest)->id);
                }
            }
        }

        return Unavailability::whereIn('id', $itemIds)->get();
    }
}
