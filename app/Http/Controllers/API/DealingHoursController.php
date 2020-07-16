<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Company;
use App\Models\DealingHours;
use Validator;
use Illuminate\Database\Eloquent\SoftDeletes;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class DealingHoursController extends Controller
{
    use SoftDeletes;
    
    public $successStatus = 200;
    public $workDuration = 7;
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
            $items = DealingHours::all();
        } else {
            $items = DealingHours::where('user_id', $user->id)->get();
        }

        return response()->json(['success' => $items], $this->successStatus);
    }

    /**
     * Display a listing of resource with specific year.
     *
     * @return \Illuminate\Http\Response
     */
    public function getOvertimesByYear( $year, $user_id ) {
        $user = Auth::user();
        $items = DealingHours::where(['user_id', $user->id], ['date', $year]);
        if ($user->hasRole('superAdmin')) {
            $items = DealingHours::whereYear('date', $year)->get();
            if ($user_id !== null) {
                $items = DealingHours::where('user_id', $user_id)
                                    ->whereYear('date', $year)
                                    ->get();
            }
        }

        // calcul overtime missHours usedHours by year
        $overtimes = 0;
        $missHours = 0;
        $usedHours = 0;
        $result = [];
        if($items !== []) {
            // foreach days in selected year
            foreach ($items as $key => $day) {
                // if day have overtime
                if ($day->overtimes >= 0) {
                    $overtimes += $day->overtimes;
                } else {
                    // day is before current day ? 
                    if ($day->date <= date('Y-m-d')) {
                        $missHours += ($day->overtimes * -1);
                    }
                }
                // if day have used_hour
                if ($day->used_hours > 0) {
                    $usedHours += $day->used_hours;
                }
            }
            // Overtimes compensation with missHours and usedHours
            if (($overtimes - $usedHours) >= $missHours) {
                $overtimes -= $missHours;
                $missHours = 0;
            }
            else { 
                $missHours = $missHours - ($overtimes - $usedHours);
                $overtimes = ($overtimes - $usedHours) - $missHours;
            }
        }   
        $result = Array (
            "missHours" => $missHours,
            "overtimes" => $overtimes,
            "usedHours" => $usedHours
        );
        return response()->json(['success' => $result], $this->successStatus);  
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
            'user_id' => 'required',
            'date' => 'required',
            'overtimes' => 'required',
            'used_hours' => 'required',
            'used_type' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }
        $item = DealingHours::create($arrayRequest);
        return response()->json(['success' => $item], $this->successStatus);
    }

    /**
     * Update or Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeOrUpdateUsed(Request $request)
    {   
        $arrayRequest = $request->all();        

        $validator = Validator::make($arrayRequest, [
            'date' => 'required',
            'used_hours' => 'required', 
            'used_type' => 'required',
            'overtimes_to_use' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        // Parse used_hours from time to double
        $parts = explode(':', $arrayRequest['used_hours']);
        $arrayRequest['used_hours'] = $parts[0] + $parts[1]/60*100 / 100;

        // $controllerLog = new Logger('dealing');
        // $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
        // $controllerLog->info('parseUsed_hours', [$arrayRequest['used_hours'], $this->workDuration]);
        
        // Check have nb overtimes
        if ($arrayRequest['used_hours'] <= $arrayRequest['overtimes_to_use']) {
            // Check used_hours
            if ($arrayRequest['used_hours'] > $this->workDuration) {
                return response()->json(['error' => "Vous ne pouvez pas récupérer plus de $this->workDuration heures par jour."]);
            }

            // Check if dealing_hour have value for this date
            $item = DealingHours::where('date', $arrayRequest['date'])->first();
            if ($item !== null) {
                //Yes -> update dealing hour
                //check if already overtime
                if ($item->overtimes < 0) {
                    // check if have nb hours to use
                    if (($item->overtimes + $arrayRequest['used_hours']) <= 0) {
                        // Check if same type
                        if ($item->used_type === $arrayRequest['used_type']) {
                            // Check if after update used_hour < workDuration
                            if (($item->used_hours + $arrayRequest['used_hours'] ) <= $this->workDuration) {
                                $item = DealingHours::where('id', $item->id)->update(['used_hours' => $arrayRequest['used_hours'], 'used_type' => $arrayRequest['used_type']]);
                                return response()->json(['success' => [$item, "update"]], $this->successStatus);
                            } else {
                                return response()->json(['error' => "Vous avez déjà récupéré(e) $item->used_hours heures le $item->date, vous ne pouvez récupérer que 7 heures par jour."]);
                            }
                        } else {
                            return response()->json(['error' => "Vous avez déjà des heures $item->used_type pour le $item->date, veuillez utiliser vos heures supplémentaires de la même manière pour ce jour."]);
                        }
                    } else {
                        $max_hours = $item->overtimes * -1;
                        return response()->json(['error' => "Vous ne pouvez poser plus que $max_hours heures pour cette journée"]);
                    }
                } else {
                    return response()->json(['error' => "Vous avez déjà travaillé(e) pour la journée complète."]);
                }
            } else {
                //No -> create dealing hour
                $validator = Validator::make($arrayRequest, [
                    'user_id' => 'required',
                    'date' => 'required',
                    'overtimes' => 'required',
                    'used_hours' => 'required',
                    'used_type' => 'required'
                ]);
                if ($validator->fails()) {
                    return response()->json(['error' => $validator->errors()], 401);
                }
                $arrayRequest['overtimes'] += $arrayRequest['used_hours'];
                $item = DealingHours::create($arrayRequest);
                return response()->json(['success' => [$item, "create"]], $this->successStatus);
            }
        } else {
            return response()->json(['error' => "Vous ne disposer pas asser d'heures."]);
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
            'date' => 'required',
            'overtimes' => 'required',
            'used_hours' => 'required',
            'used_type' => 'required'
        ]);

        $item = DealingHours::where('id', $id)->update(['user_id' => $arrayRequest['user_id'], 'date' => $arrayRequest['date'], 'overtimes' => $arrayRequest['overtimes'], 'used_hours' => $arrayRequest['used_hours'], 'used_type' => $arrayRequest['used_type']]);
        return response()->json(['success' => $item], $this->successStatus);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $item = DealingHours::findOrFail($id);
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
        $item = DealingHours::findOrFail($id);
        $item->delete();

        $item = DealingHours::withTrashed()->findOrFail($id);
        $item->forceDelete();
        return '';
    }
}
