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
    protected static $index_load = ['project:id,name', 'user:id,firstname,lastname,email'];
    protected static $index_append = null;
    protected static $show_load = ['project:id,name', 'user:id,firstname,lastname,email'];
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
                        $paidHolidays->whereBetween('starts_at', [$date->startOfWeek(), $date->endOfWeek()]);
                        break;
                    case 'month':
                        $paidHolidays->whereBetween('starts_at', [$date->startOfMonth(), $date->endOfMonth()]);
                        break;
                    case 'year':
                        $paidHolidays->whereBetween('starts_at', [$date->startOfYear(), $date->endOfYear()]);
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

        if (!$items->isEmpty()) {
            foreach ($items as $item) {
                $stats['total']->add(CarbonInterval::createFromFormat('H:i:s', $item->duration));
            }

            $stats['total'] = $stats['total']->totalHours;

            if ($request->date && $userId = $user->is_admin ? $request->user_id : $user->id) {
                $nbWorkDays = WorkHours::where('user_id', $userId)->where('is_active', 1)->count() || 1;
                $workWeekHours = WorkHours::where('user_id', $userId)->where('is_active', 1)->get()->map(function ($day) {
                    $morning = CarbonInterval::createFromFormat('H:i:s', $day->morning_ends_at)->subtract(CarbonInterval::createFromFormat('H:i:s', $day->morning_starts_at));
                    $afternoon = CarbonInterval::createFromFormat('H:i:s', $day->afternoon_ends_at)->subtract(CarbonInterval::createFromFormat('H:i:s', $day->afternoon_starts_at));
                    return $morning->add($afternoon)->totalHours;
                })->sum();
                $workDayHours = HoursController::getTargetWorkHours($userId, Carbon::createFromFormat('d-m-Y', $request->date)->locale('fr_FR')->dayName);

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

        return collect(['stats' => $stats]);
    }

    protected function filterIndexQuery(Request $request, $query)
    {
        $user = Auth::user();
        if ($user->is_manager) {
            $query->join('users', function ($join) use ($user) {
                $join->on('hours.user_id', '=', 'users.id')
                    ->where('users.company_id', $user->company_id);
            });
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
                        $query->whereBetween('start_at', [$date->startOfWeek(), $date->endOfWeek()]);
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

        // How many hour user worked this day
        $nb_worked_hours = HoursController::getNbWorkedHours($arrayRequest['user_id'], $parseDuration, $arrayRequest['date']);

        // Expected hours for this day
        $target_day = $start_at->locale('fr_FR')->dayName;
        $target_work_hours = HoursController::getTargetWorkHours($arrayRequest['user_id'], $target_day);
        if ($target_work_hours === 0) {
            throw new ApiException("Vérifier que l'utilisateur ai bien renseigné des horraires de travail pour le " . $target_day . ".");
        }

        // Check if user have already hour on this new entry period
        if (!$this->checkIfNewEntryLayeringOtherHours($arrayRequest['user_id'], $start_at, $end_at)) {
            throw new ApiException("Attention, vous ne pouvez pas superposer deux horaires.");
        }

        // Check if value in dealing_hours for this date
        $findDealingHour = DealingHours::where('user_id', $arrayRequest['user_id'])->where('date', $arrayRequest['date'])->first();

        $item = Hours::create([
            'user_id' => $arrayRequest['user_id'],
            'project_id' => $arrayRequest['project_id'],
            'start_at' => $arrayRequest['start_at'],
            'end_at' => $arrayRequest['end_at'],
            'date' => $arrayRequest['date'],
        ]);
        if (isset($arrayRequest['description'])) {
            $item->update(['description' => $arrayRequest['description']]);
        }
        if (empty($findDealingHour) === false) {
            // Update dealing_hour with difference between nb_worked_hours and $target_work_hours for overtime column
            $findDealingHour->update(['overtimes' => ($nb_worked_hours - $target_work_hours)]);
        }
        // Else add new tuple in dealing_hours for this date
        else {
            //Create new tuple in dealing_hours with user_id, date and overtimes
            $deallingHourItem = DealingHours::create(
                ['user_id' => $arrayRequest['user_id'], 'date' => $arrayRequest['date'], 'overtimes' => ($nb_worked_hours - $target_work_hours)]
            );
        }

        return $item;
    }

    protected function updateItem($item, array $arrayRequest)
    {
        $start_at = Carbon::parse($arrayRequest['start_at']);
        $end_at = Carbon::parse($arrayRequest['end_at']);

        // Update user hour have worke_hour for this day ?
        $target_day = $start_at->locale('fr_FR')->dayName;
        $target_work_hours = HoursController::getTargetWorkHours($arrayRequest['user_id'], $target_day);
        if ($target_work_hours === 0) {
            throw new ApiException("Vérifier que l'utilisateur ai bien renseigné des horraires de travail pour le " . $target_day . ".");
        }

        // Check if user have already hour on this new entry period
        if (!$this->checkIfNewEntryLayeringOtherHours($arrayRequest['user_id'], $start_at, $end_at, $arrayRequest['id'])) {
            throw new ApiException("Attention, vous ne pouvez pas superposer deux horaires.");
        }

        // get old hour data
        $old_hours = $item->replicate();
        $old_date = Carbon::parse($old_hours['start_at']);
        $old_hours['date'] = $old_date->format('Y-m-d');

        $item->update([
            'user_id' => $arrayRequest['user_id'],
            'project_id' => $arrayRequest['project_id'],
            'start_at' => $arrayRequest['start_at'],
            'end_at' => $arrayRequest['end_at'],
        ]);

        if (isset($arrayRequest['description'])) {
            $item->update(['description' => $arrayRequest['description']]);
        }

        //reset old overtimes compteur
        // How many old hour worked this day
        $nb_worked_hours = HoursController::getNbWorkedHours($old_hours['user_id'], 0, $old_hours['date']);

        // Expected hours for old day
        $target_work_hours = HoursController::getTargetWorkHours($old_hours['user_id'], $old_date->locale('fr_FR')->dayName);

        // Check if value in dealing_hours for old date
        $findDealingHour = DealingHours::where([['date', $old_hours['date']], ['user_id', $old_hours['user_id']]])->first();

        if ($nb_worked_hours > $target_work_hours) {
            // Update dealing_hour with difference between nb_worked_hours and $target_work_hours for overtime column
            DealingHours::where('date', $old_hours['date'])->update(['overtimes' => ($nb_worked_hours - $target_work_hours)]);
        } else if ($findDealingHour) {
            // Not empty and no used_hour ? Delete dealing_hour tuple
            if ($findDealingHour['used_hours'] === 0) {
                $item = DealingHours::findOrFail($findDealingHour['id']);
                $item->delete();
            } else {
                DealingHours::where([['date', $old_hours['date']], ['user_id', $old_hours['user_id']]])->update(['overtimes' => 0]);
            }
        }

        //reset new overtimes compteur
        // How many new hour worked this day
        $nb_worked_hours = HoursController::getNbWorkedHours($arrayRequest['user_id'], 0, $arrayRequest['date']);

        // Expected hours for this day
        $target_work_hours = HoursController::getTargetWorkHours($arrayRequest['user_id'], $target_day);

        // Check if value in dealing_hours for this date
        $findDealingHour = DealingHours::where([['date', $arrayRequest['date']], ['user_id', $arrayRequest['user_id']]])->first();

        if ($nb_worked_hours > $target_work_hours) {
            if (!empty($findDealingHour)) {
                // Update dealing_hour with difference between nb_worked_hours and $target_work_hours for overtime column
                $findDealingHour->update(['overtimes' => ($nb_worked_hours - $target_work_hours)]);
            } else {
                //Create new tuple in dealing_hours with user_id, date and overtimes
                $deallingHourItem = DealingHours::create(
                    ['user_id' => $arrayRequest['user_id'], 'date' => $arrayRequest['date'], 'overtimes' => ($nb_worked_hours - $target_work_hours)]
                );
            }
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

        if (!$worked_hours->isEmpty()) {
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

        // update overtimes compteur for this user and date
        // How many hour worked this day
        $nb_worked_hours = HoursController::getNbWorkedHours($item->user_id, 0, $date->format('Y-m-d'));

        // Expected hours for this day
        $target_work_hours = HoursController::getTargetWorkHours($item->user_id, Carbon::parse($item->date)->locale('fr_FR')->dayName);

        // Check if value in dealing_hours for old date
        $findDealingHour = DealingHours::where([['date', $date->format('Y-m-d')], ['user_id', $item->user_id]])->first();

        if (empty($findDealingHour) === false && ($nb_worked_hours - $target_work_hours) >= 0) {
            // Delete dealing_hours
            $findDealingHour->delete();
        } else {
            // Update dealing_hour with difference between nb_worked_hours and $target_work_hours for overtime column
            DealingHours::where([['date', $date->format('Y-m-d')], ['user_id', $item->user_id]])->update(['overtimes' => ($nb_worked_hours - $target_work_hours)]);
        }

        return parent::destroyItem($item);
    }
}
