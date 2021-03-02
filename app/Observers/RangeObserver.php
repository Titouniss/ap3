<?php

namespace App\Observers;

use App\Models\Range;
use App\Models\RepetitiveTask;

class RangeObserver
{
    /**
     * Handle the range "created" event.
     *
     * @param  \App\Models\Range  $range
     * @return void
     */
    public function created(Range $range)
    {
        //
    }

    /**
     * Handle the range "updated" event.
     *
     * @param  \App\Models\Range  $range
     * @return void
     */
    public function updated(Range $range)
    {
        //
    }

    /**
     * Handle the range "deleted" event.
     *
     * @param  \App\Models\Range  $range
     * @return void
     */
    public function deleted(Range $range)
    {
        $tasks = RepetitiveTask::where('range_id', $range->id)->get();
        foreach ($tasks as $task) {
            $task->delete();
        }
    }

    /**
     * Handle the range "restored" event.
     *
     * @param  \App\Models\Range  $range
     * @return void
     */
    public function restored(Range $range)
    {
        $tasks = RepetitiveTask::withTrashed()->where('range_id', $range->id)->get();
        foreach ($tasks as $task) {
            $task->restore();
        }
    }

    /**
     * Handle the range "force deleted" event.
     *
     * @param  \App\Models\Range  $range
     * @return void
     */
    public function forceDeleted(Range $range)
    {
        //
    }
}
