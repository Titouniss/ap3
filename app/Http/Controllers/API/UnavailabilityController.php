<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Unavailability;
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
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
        $arrayRequest_starts = Carbon::createFromFormat('Y-m-d H:i', $arrayRequest['starts_at']);
        $arrayRequest_ends = Carbon::createFromFormat('Y-m-d H:i', $arrayRequest['ends_at']);
        $duration = ( floatval(explode(':', $arrayRequest_ends->format('H:i'))[0]) ) - ( floatval(explode(':', $arrayRequest_starts->format('H:i'))[0]) );

        // Overtimes verification 
        $Overtimes = DealingHoursController::getOvertimes(true);
        $OvertimesToUse = $Overtimes['overtimes'] - $Overtimes['usedOvertimes'];

        if ($arrayRequest['reason'] == 'Utilisation heures suplémentaires') {
            if ( $OvertimesToUse < $duration ) {
                return response()->json(['error' => "Vous ne disposer pas asser d'heures supplémentaires"], 401);
            }
            // Expected hours for this day
            $workDuration = HoursController::getTargetWorkHours($arrayRequest['user_id'], $arrayRequest['starts_at']);
            if ($workDuration === 0) {
                setlocale(LC_TIME, "fr_FR", "French");
                $target_day = strftime("%A", strtotime($arrayRequest['starts_at']));
                return response()->json(['error' => "Vérifier que l'utilisateur ai bien renseigné des horraires de travail pour le " + $target_day], 401);
            } else if ($workDuration < $duration) {
                return response()->json(['error' => "Vous ne pouvez pas utiliser plus d'heures supplémentaires que d'heures de travail prévues"], 401);
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
            return response()->json(['success' => Unavailability::create($arrayRequest)], $this->successStatus);
        } else {
            // Ajouter l'indisponibilité
            return response()->json(['success' => Unavailability::create($arrayRequest)], $this->successStatus);
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
            } else {
                // Ajouter l'indisponibilité
                Unavailability::where('id', $id)->update(['starts_at' => $arrayRequest['starts_at'], 'ends_at' => $arrayRequest['ends_at'], 'reason' => $arrayRequest['reason']]);
                return response()->json(['success' => Unavailability::find($id)], $this->successStatus);
            }
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
        $item = Unavailability::findOrFail($id);
        $item->delete();
        return response()->json(['success' => true], $this->successStatus);
    }
}
