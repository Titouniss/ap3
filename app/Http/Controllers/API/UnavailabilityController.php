<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Unavailability;
use App\Models\WorkHours;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Carbon\CarbonPeriod;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// $controllerLog = new Logger('unavailability');
// $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
// $controllerLog->info('workDuration', [$workDuration]);

class UnavailabilityController extends Controller
{
    public $successStatus = 200;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(['success' => Unavailability::where('user_id', Auth::user()->id)->orderby('starts_at', 'asc')->get()], $this->successStatus);
    }

    /**
     * Show the specified resource.
     *
     * @param  \App\Models\Unavailability  $unavailability
     * @return \Illuminate\Http\Response
     */
    public function show(Unavailability $unavailability)
    {
        return response()->json(['success' => $unavailability], $this->successStatus);
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
            'starts_at' => 'required|date',
            'ends_at' => 'required|date',
            'reason' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        $unavailabilities = Unavailability::where('user_id', Auth::user()->id)->orderby('starts_at', 'asc')->get();

        $arrayRequest['user_id'] = Auth::user()->id;
        $arrayRequest_starts = Carbon::createFromFormat('Y-m-d H:i', $arrayRequest['starts_at'])->locale('fr_FR');
        $arrayRequest_ends = Carbon::createFromFormat('Y-m-d H:i', $arrayRequest['ends_at']);
        $duration = (floatval(explode(':', $arrayRequest_ends->format('H:i'))[0])) - (floatval(explode(':', $arrayRequest_starts->format('H:i'))[0]));

        // Overtimes verification
        $Overtimes = DealingHoursController::getOvertimes(true);
        $OvertimesToUse = $Overtimes['overtimes'] - $Overtimes['usedOvertimes'];

        if ($arrayRequest['reason'] == 'Utilisation heures suplémentaires') {
            if ($OvertimesToUse < $duration) {
                return response()->json(['error' => "Vous ne disposez pas assez d'heures supplémentaires"], 401);
            }
            // Expected hours for this day
            $workDuration = HoursController::getTargetWorkHours($arrayRequest['user_id'], $arrayRequest['starts_at']);
            if ($workDuration === 0) {
                return response()->json(['error' => "Vérifier que l'utilisateur ai bien renseigné des horraires de travail pour le " . $arrayRequest_starts->dayName], 400);
            } else if ($workDuration < $duration) {
                return response()->json(['error' => "Vous ne pouvez pas utiliser plus d'heures supplémentaires que d'heures de travail prévues"], 400);
            }
        }

        // Unavailability already existe or layering
        if (!$unavailabilities->isEmpty()) {
            foreach ($unavailabilities as $unavailability) {
                $unavailability_starts = Carbon::createFromFormat('Y-m-d H:i:s', $unavailability->starts_at);
                $unavailability_ends = Carbon::createFromFormat('Y-m-d H:i:s', $unavailability->ends_at);

                // Vérifier qu'elle n'est pas englobé, dedans ou déjà présente
                if (($arrayRequest_starts->between($unavailability_starts, $unavailability_ends) && $arrayRequest_ends->between($unavailability_starts, $unavailability_ends)) || ($unavailability_starts->between($arrayRequest_starts, $arrayRequest_ends) && $unavailability_ends->between($arrayRequest_starts, $arrayRequest_ends)) || ($arrayRequest_starts == $unavailability_starts->format('Y-m-d H:i') && $arrayRequest_ends == $unavailability_ends->format('Y-m-d H:i'))) {
                    return response()->json(['error' => "Une indisponibilité existe déjà sur cette période"], 401);
                } else if ($arrayRequest_ends->gt($unavailability_ends->format('Y-m-d H:i')) && ($arrayRequest_starts->between($unavailability_starts, $unavailability_ends) && $arrayRequest_starts->ne($unavailability_ends))) {
                    // Vérifier que ça ne mort pas par le starts_at
                    return response()->json(['error' => "Le début de l'indisponibilité dépasse sur une autre"], 401);
                } else if ($arrayRequest_starts->lt($unavailability_starts->format('Y-m-d H:i')) && ($arrayRequest_ends->between($unavailability_starts, $unavailability_ends) && $arrayRequest_ends->ne($unavailability_starts))) {
                    // Verifier que ça ne mort pas par le ends_at
                    return response()->json(['error' => "La fin de l'indisponibilité dépasse sur une autre"], 401);
                }
            }
            // Ajouter l'indisponibilité
            if ($arrayRequest['reason'] == 'Congés payés') {
                return response()->json(['success' => UnavailabilityController::setPaidHolidays($arrayRequest, $arrayRequest_starts, $arrayRequest_ends)], $this->successStatus);
            } else {
                return response()->json(['success' => Unavailability::create($arrayRequest)], $this->successStatus);
            }
        } else {
            // Ajouter l'indisponibilité
            if ($arrayRequest['reason'] == 'Congés payés') {
                return response()->json(['success' => UnavailabilityController::setPaidHolidays($arrayRequest, $arrayRequest_starts, $arrayRequest_ends)], $this->successStatus);
            } else {
                return response()->json(['success' => Unavailability::create($arrayRequest)], $this->successStatus);
            }
        }
    }

    /**
     * Set paid holidays unavailabilities.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function setPaidHolidays($arrayRequest, $arrayRequest_starts, $arrayRequest_ends)
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
        if (count($userWorkingDays) > 0) {
            // Pour chaques $days je regarde s'il est dans $userWorkingDays

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

                        Unavailability::create($arrayRequest);
                    }

                    // si travail l'après midi on ajoute une indispo égale au temps de travail et au même heures
                    if ($dayWorkingHours->afternoon_starts_at !== null && $dayWorkingHours->afternoon_ends_at != null) {
                        $arrayRequest["starts_at"] = $d . " " . $dayWorkingHours->afternoon_starts_at;
                        $arrayRequest["ends_at"] = $d . " " . $dayWorkingHours->afternoon_ends_at;

                        Unavailability::create($arrayRequest);
                    }
                }
            }
            return response()->json(['success' => $arrayRequest], $this->successStatus);
        } else {
            return response()->json(['error' => "vous ne travaillez pas sur cette période"], 400);
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
     * @param  \App\Models\Unavailability  $unavailability
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Unavailability $unavailability)
    {
        $arrayRequest = $request->all();
        $validator = Validator::make($arrayRequest, [
            'starts_at' => 'required|date',
            'ends_at' => 'required|date',
            'reason' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        $arrayRequest['user_id'] = Auth::user()->id;
        $arrayRequest_starts = Carbon::createFromFormat('Y-m-d H:i', $arrayRequest['starts_at']);
        $arrayRequest_ends = Carbon::createFromFormat('Y-m-d H:i', $arrayRequest['ends_at']);

        $unavailabilities = Unavailability::where('user_id', Auth::user()->id)->orderby('starts_at', 'asc')->get();

        $passage = 0;

        foreach ($unavailabilities as $item) {
            $unavailability_starts = Carbon::createFromFormat('Y-m-d H:i:s', $item->starts_at);
            $unavailability_ends = Carbon::createFromFormat('Y-m-d H:i:s', $item->ends_at);

            $passage++;

            $controllerLog = new Logger('unavailability');
            $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
            $controllerLog->info('passage, unavailability_id, id', [$passage, $item->id, intval($unavailability->id)]);

            // Vérifier que ce n'est pas la même qui bloque et qu'elle n'est pas englobé, dedans ou déjà présente
            if (($unavailability->id != intval($item->id)) && (($arrayRequest_starts->between($unavailability_starts, $unavailability_ends) && $arrayRequest_ends->between($unavailability_starts, $unavailability_ends)) || ($unavailability_starts->between($arrayRequest_starts, $arrayRequest_ends) && $unavailability_ends->between($arrayRequest_starts, $arrayRequest_ends)) || ($arrayRequest_starts == $unavailability_starts->format('Y-m-d H:i') && $arrayRequest_ends == $unavailability_ends->format('Y-m-d H:i')))) {
                return response()->json(['error' => "Une indisponibilité existe déjà sur cette période"], 401);
            } else if (($unavailability->id != intval($item->id)) && ($arrayRequest_ends->gt($unavailability_ends->format('Y-m-d H:i')) && ($arrayRequest_starts->between($unavailability_starts, $unavailability_ends) && $arrayRequest_starts->ne($unavailability_ends)))) {
                // Vérifier que ce n'est pas la même qui bloque et que ça ne mort pas par le starts_at
                return response()->json(['error' => "Le début de l'indisponibilité dépasse sur une autre"], 401);
            } else if (($unavailability->id != intval($item->id)) && ($arrayRequest_starts->lt($unavailability_starts->format('Y-m-d H:i')) && ($arrayRequest_ends->between($unavailability_starts, $unavailability_ends) && $arrayRequest_ends->ne($unavailability_starts)))) {
                // Verifier que ce n'est pas la même qui bloque et que ça ne mort pas par le ends_at
                return response()->json(['error' => "La fin de l'indisponibilité dépasse sur une autre"], 401);
            } else {
                // Ajouter l'indisponibilité
                $unavailability->update(['starts_at' => $arrayRequest['starts_at'], 'ends_at' => $arrayRequest['ends_at'], 'reason' => $arrayRequest['reason']]);
                return response()->json(['success' => Unavailability::find($id)], $this->successStatus);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Unavailability  $unavailability
     * @return \Illuminate\Http\Response
     */
    public function destroy(Unavailability $unavailability)
    {
        return response()->json(['success' => $unavailability->delete()], $this->successStatus);
    }
}
