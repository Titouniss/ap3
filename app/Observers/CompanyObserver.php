<?php

namespace App\Observers;

use App\Models\Company;
use App\Models\CompanyDetails;
use App\Models\Customer;
use App\Models\Project;
use App\Models\Range;
use App\Models\Role;
use App\Models\Skill;
use App\Models\Subscription;
use App\Models\Workarea;
use App\User;

class CompanyObserver
{
    /**
     * Handle the company "created" event.
     *
     * @param  \App\Models\Company  $company
     * @return void
     */
    public function created(Company $company)
    {
        //
    }

    /**
     * Handle the company "updated" event.
     *
     * @param  \App\Models\Company  $company
     * @return void
     */
    public function updated(Company $company)
    {
        //
    }

    /**
     * Handle the company "deleted" event.
     *
     * @param  \App\Models\Company  $company
     * @return void
     */
    public function deleted(Company $company)
    {
        Role::where('company_id', $company->id)->delete();
        User::where('company_id', $company->id)->delete();
        Subscription::where('company_id', $company->id)->delete();

        foreach (Project::where('company_id', $company->id)->get() as $project) {
            $project->delete();
        }
        foreach (Range::where('company_id', $company->id)->get() as $range) {
            $range->delete();
        }
        foreach (Customer::where('company_id', $company->id)->get() as $customer) {
            $customer->delete();
        }

        Workarea::where('company_id', $company->id)->delete();
        Skill::where('company_id', $company->id)->delete();
    }

    /**
     * Handle the company "restored" event.
     *
     * @param  \App\Models\Company  $company
     * @return void
     */
    public function restored(Company $company)
    {
        Skill::withTrashed()->where('company_id', $company->id)->restore();
        Workarea::withTrashed()->where('company_id', $company->id)->restore();

        foreach (Customer::withTrashed()->where('company_id', $company->id)->get() as $customer) {
            $customer->restore();
        }
        foreach (Range::withTrashed()->where('company_id', $company->id)->get() as $range) {
            $range->restore();
        }
        foreach (Project::withTrashed()->where('company_id', $company->id)->get() as $project) {
            $project->restore();
        }

        Subscription::withTrashed()->where('company_id', $company->id)->restore();
        User::withTrashed()->where('company_id', $company->id)->restore();
        Role::withTrashed()->where('company_id', $company->id)->restore();
    }

    /**
     * Handle the company "force deleted" event.
     *
     * @param  \App\Models\Company  $company
     * @return void
     */
    public function forceDeleted(Company $company)
    {
        if ($company->details) {
            $company->details->delete();
        }
    }
}
