<?php

namespace App\Observers;

use App\User;
use App\Models\DealingHours;

class UserObserver
{
    /**
     * Handle the project "created" event.
     *
     * @param  \App\User $user
     * @return void
     */
    public function created(User $user)
    {
        //
    }

    /**
     * Handle the project "updated" event.
     *
     * @param  \App\User $user
     * @return void
     */
    public function updated(User $user)
    {
        
    }

    /**
     * Handle the project "deleted" event.
     *
     * @param  \App\User $user
     * @return void
     */
    public function deleting(User $user)
    {
        if($user->isForceDeleting()){
            $findDealingHoursHeuresSupp = DealingHours::where('user_id', $user->id)->delete();
        }        
    }

    /**
     * Handle the project "restored" event.
     *
     * @param  \App\User $user
     * @return void
     */
    public function restored(User $user)
    {
        
    }

    /**
     * Handle the project "force deleted" event.
     *
     * @param  \App\User $user
     * @return void
     */
    public function forceDeleting(User $user)
    {              
        
        
    }
}
