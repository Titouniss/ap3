<?php

namespace App\Providers;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Project;
use App\Models\Range;
use App\User;
use App\Observers\CompanyObserver;
use App\Observers\CustomerObserver;
use App\Observers\ProjectObserver;
use App\Observers\RangeObserver;
use App\Observers\UserObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Company::observe(CompanyObserver::class);
        Customer::observe(CustomerObserver::class);
        Project::observe(ProjectObserver::class);
        Range::observe(RangeObserver::class);
        User::observe(UserObserver::class);
    }
}
