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
        $controllerLog = new Logger('dealing');
        $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
        $controllerLog->info('items', ['je passe dedans', $year, $user_id]);

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

        $controllerLog = new Logger('dealing');
        $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
        $controllerLog->info('items', [$items]);

        // calcul for year overtime
        $missHours = 0;
        $overtimes = 0;
        $usedHours = 0;
        $result = [];
        if($items !== []) {
            foreach ($items as $key => $day) {
                if ($day->overtimes > 0) {
                    $overtimes += $day->overtimes;
                } else {
                    $missHours += $day->overtimes;
                }
                if ($day->used_hours > 0) {
                    $usedHours += $day->used_hours;
                }
            }
            if($overtimes > abs($missHours)){
                $overtimes = $overtimes - abs($missHours);
                $missHours = 0;
            }
        }
            $overtimes = $overtimes - $usedHours + $missHours;
            $result= Array (
                "missHours" => $missHours,
                "overtimes" => $overtimes,
                "usedHours" => $usedHours);
            
        $controllerLog = new Logger('dealing');
        $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
        $controllerLog->info('result, overtimes, usedHours, missHours', [$result, $overtimes, $usedHours, $missHours]);

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
