<?php

use Illuminate\Database\Seeder;

use App\Models\Hours;
use App\Models\DealingHours;
use App\Models\WorkHours;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Carbon\CarbonPeriod;

class ResetDealingHours extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Supp all dealing_hours
        DB::table('dealing_hours')->truncate();

        // Get users 
        $users = DB::table('users')->select('*')->get();
        if (!$users->isEmpty()) {
            foreach ($users as $key => $user) {
                // Get user hours
                $user_hours = Hours::where('user_id', $user->id)->select('*')->get();
                if (!$user_hours->isEmpty()) {
                    foreach ($user_hours as $key => $hour) {
                        //Set duration
                        $start_at = Carbon::parse($hour->start_at);
                        $end_at = Carbon::parse($hour->end_at);
        
                        $parseDuration = $start_at->floatDiffInHours($end_at);
                            
                        // How many hour user worked this day
                        $nb_worked_hours = ResetDealingHours::getNbWorkedHours($user->id, $parseDuration, $start_at->format('Y-m-d'));
        
                        // Expected hours for this day
                        $target_day = $start_at->locale('fr_FR')->dayName;
                        $target_work_hours = ResetDealingHours::getTargetWorkHours($user->id, $target_day);
                        if ($target_work_hours > 0) {
                                // Check if value in dealing_hours for this date
                            $findDealingHour = DealingHours::where('user_id', $user->id)->where('date', $start_at->format('Y-m-d'))->first();
            
                            if (empty($findDealingHour) === false) {
                                // Update dealing_hour with difference between nb_worked_hours and $target_work_hours for overtime column
                                $findDealingHour->update(['overtimes' => ($nb_worked_hours - $target_work_hours)]);
                            }
                            // Else add new tuple in dealing_hours for this date
                            else {
                                //Create new tuple in dealing_hours with user_id, date and overtimes
                                $deallingHourItem = DealingHours::create(
                                    ['user_id' => $user->id, 'date' => $start_at->format('Y-m-d'), 'overtimes' => ($nb_worked_hours - $target_work_hours)]
                                );
                            }
                        }
                    }
                }
            }
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
        $worked_hours = $date ? Hours::where([['user_id', $user_id], ['start_at', 'LIKE', '%' . $date . '%']])->select('*')->get() : [];

        if (!$worked_hours->isEmpty()) {
            $nb_worked_hours = 0;

            foreach ($worked_hours as $wh) {
                $nb_worked_hours += $wh->getDurationInFloatHourAttribute();
            }
            // Add current insert work hours
            return $nb_worked_hours;
        }
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
}
