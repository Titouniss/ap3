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

        $items = $items->with('project', 'user')->orderBy('date')->get();

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
            'date' => 'required',
            'duration' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        
        // Parse duration from time to double
        $parts = explode(':', $arrayRequest['duration']);
        $parseDuration = $parts[0] + $parts[1]/60*100 / 100;

        if($parseDuration === 0) {
            return response()->json(['error' => "Veuillez inserer une durée"]); 
        }

        // je verifie qu'il y est une tuple dans dealing_hours en lien avec cette date
        $findDealingHour = DealingHours::where('date', $arrayRequest['date'])->first();

        if(empty($findDealingHour) === false) {
            // J'ajoute la durée de cette heure de travail dans la table dealing_hour pour la colomn overtime
            $item = Hours::create($arrayRequest);
            DealingHours::where('date', $arrayRequest['date'])->increment('overtimes', $parseDuration);
        } 
        // Sinon je créer une nouvelle tuple avec cette horraire la
        else {
            // Création du tuple dans table dealing_hours avec le user_id et la date
            $item = Hours::create($arrayRequest);
            $deallingHourItem = DealingHours::create(
                ['user_id' => $arrayRequest['user_id'], 'date' => $arrayRequest['date']]                
            );
            // Ajout de la durée de travail à cette même tuple pour la colomn overtime
            DealingHours::where('date', $arrayRequest['date'])->increment('overtimes', $parseDuration);
        }

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
            'project_id' => 'required',
            'date' => 'required',
            'duration' => 'required'
        ]);

        $update = Hours::where('id', $id)
            ->update([
                'user_id' => $arrayRequest['user_id'],
                'project_id' => $arrayRequest['project_id'],
                'date' => $arrayRequest['date'],
                'duration' => $arrayRequest['duration'],
                'description' => $arrayRequest['description'],
            ]);

        if ($update) {
            $item = Hours::find($id);
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
        $item->delete();
        return '';
    }
}
