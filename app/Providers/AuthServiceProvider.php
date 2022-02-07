<?php

namespace App\Providers;

use App\Models\BaseModule;
use App\Models\Company;
use App\Models\Customer;
use App\Models\DealingHours;
use App\Models\Hours;
use App\Models\Project;
use App\Models\Range;
use App\Models\Role;
use App\Models\Bug;
use App\Models\Package;
use App\Models\Skill;
use App\Models\Todo;
use App\Models\Subscription;
use App\Models\Tag;
use App\Models\Task;
use App\Models\Unavailability;
use App\Models\Workarea;
use App\Policies\BaseModulePolicy;
use App\Policies\CompanyPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\DealingHoursPolicy;
use App\Policies\HoursPolicy;
use App\Policies\PermissionPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\RangePolicy;
use App\Policies\RolePolicy;
use App\Policies\BugPolicy;
use App\Policies\PackagePolicy;
use App\Policies\SkillPolicy;
use App\Policies\SubscriptionPolicy;
use App\Policies\TagPolicy;
use App\Policies\TaskPolicy;
use App\Policies\TodoPolicy;
use App\Policies\UnavailabilityPolicy;
use App\Policies\UserPolicy;
use App\Policies\WorkareaPolicy;
use App\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Permission;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Company::class => CompanyPolicy::class,
        Customer::class => CustomerPolicy::class,
        DealingHours::class => DealingHoursPolicy::class,
        Hours::class => HoursPolicy::class,
        BaseModule::class => BaseModulePolicy::class,
        Permission::class => PermissionPolicy::class,
        Project::class => ProjectPolicy::class,
        Range::class => RangePolicy::class,
        Role::class => RolePolicy::class,
        Bug::class => BugPolicy::class,
        Skill::class => SkillPolicy::class,
        Subscription::class => SubscriptionPolicy::class,
        Task::class => TaskPolicy::class,
        Unavailability::class => UnavailabilityPolicy::class,
        Workarea::class => WorkareaPolicy::class,
        Package::class => PackagePolicy::class,
        Todo::class =>TodoPolicy::class,
        Tag::class =>TagPolicy::class             

    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
        // Implicitly grant "Super Admin" role all permissions
        // This works in the app by using gate-related functions like auth()->user->can() and @can()
        Gate::before(function ($user, $ability) {
            return $user->is_admin ? true : null;
        });

        Passport::routes();
    }
}
