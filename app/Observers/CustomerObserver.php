<?php

namespace App\Observers;

use App\Models\CompanyDetails;
use App\Models\Customer;
use App\Models\Project;

class CustomerObserver
{
    /**
     * Handle the customer "created" event.
     *
     * @param  \App\Models\Customer  $customer
     * @return void
     */
    public function created(Customer $customer)
    {
        //
    }

    /**
     * Handle the customer "updated" event.
     *
     * @param  \App\Models\Customer  $customer
     * @return void
     */
    public function updated(Customer $customer)
    {
        //
    }

    /**
     * Handle the customer "deleted" event.
     *
     * @param  \App\Models\Customer  $customer
     * @return void
     */
    public function deleted(Customer $customer)
    {
        $projects = Project::where('customer_id', $customer->id)->get();
        foreach ($projects as $project) {
            $project->delete();
        }
    }

    /**
     * Handle the customer "restored" event.
     *
     * @param  \App\Models\Customer  $customer
     * @return void
     */
    public function restored(Customer $customer)
    {
        $projects = Project::withTrashed()->where('customer_id', $customer->id)->get();
        foreach ($projects as $project) {
            $project->restore();
        }
    }

    /**
     * Handle the customer "force deleted" event.
     *
     * @param  \App\Models\Customer  $customer
     * @return void
     */
    public function forceDeleted(Customer $customer)
    {
        if ($customer->details) {
            $customer->details->delete();
        }
    }
}
