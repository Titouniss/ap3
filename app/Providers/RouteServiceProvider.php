<?php

namespace App\Providers;

use App\Models\BaseModule;
use App\Models\Company;
use App\Models\Customer;
use App\Models\DealingHours;
use App\Models\Document;
use App\Models\Hours;
use App\Models\Project;
use App\Models\Range;
use App\Models\Role;
use App\Models\Skill;
use App\Models\Subscription;
use App\Models\Task;
use App\Models\TasksBundle;
use App\Models\Unavailability;
use App\Models\Workarea;
use App\User;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        Route::bind('user', function ($id) {
            return User::withTrashed()->find($id);
        });
        Route::bind('role', function ($id) {
            return Role::withTrashed()->find($id);
        });
        Route::bind('permission', function ($id) {
            return Permission::find($id);
        });
        Route::bind('company', function ($id) {
            return Company::withTrashed()->find($id);
        });
        Route::bind('skill', function ($id) {
            return Skill::withTrashed()->find($id);
        });
        Route::bind('workarea', function ($id) {
            return Workarea::withTrashed()->find($id);
        });
        Route::bind('project', function ($id) {
            return Project::withTrashed()->find($id);
        });
        Route::bind('range', function ($id) {
            return Range::withTrashed()->find($id);
        });
        Route::bind('task', function ($id) {
            return Task::withTrashed()->find($id);
        });
        Route::bind('unavailability', function ($id) {
            return Unavailability::find($id);
        });
        Route::bind('hours', function ($id) {
            return Hours::find($id);
        });
        Route::bind('dealing_hours', function ($id) {
            return DealingHours::find($id);
        });
        Route::bind('customer', function ($id) {
            return Customer::withTrashed()->find($id);
        });
        Route::bind('module', function ($id) {
            return BaseModule::find($id);
        });
        Route::bind('subscription', function ($id) {
            return Subscription::withTrashed()->find($id);
        });
        Route::bind('task_bundle', function ($id) {
            return TasksBundle::withTrashed()->find($id);
        });
        Route::bind('document', function ($id) {
            return Document::find($id);
        });
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();

        $this->mapWebRoutes();

        //
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
            ->namespace($this->namespace)
            ->group(base_path('routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->namespace)
            ->group(base_path('routes/api.php'));
    }
}
