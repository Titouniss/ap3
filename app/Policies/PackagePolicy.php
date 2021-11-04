<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\Package;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PackagePolicy extends BasePolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct('packages');
    }

    /**
     * Determine whether the user can view the items.
     *
     * @param  \App\User  $currentUser
     * @return boolean
     */
    public function read(User $user)
    {
        if ($user->is_admin) return true;

        if ($company = Company::find($user->company_id)) {
            if ($company->has_active_subscription) {
                return $user->can("read subscriptions");
            }
        }

        return false;
    }

    /**
     * Determine whether the user can view the item.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Package  $item
     * @return boolean
     */
    public function show(User $user, Package $item)
    {
        if ($user->is_admin) return true;

        if ($company = Company::find($user->company_id)) {
            if ($company->has_active_subscription) {
                return $company->active_subscription->packages->contains(function ($p) use ($item) {
                    return $p->id == $item->id;
                });
            }
        }

        return false;
    }

    /**
     * Determine whether the user can publish an item.
     *
     * @param  \App\User  $user
     * @return boolean
     */
    public function publish(User $user)
    {
        return false;
    }

    /**
     * Determine whether the user can edit the item.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Package  $item
     * @return boolean
     */
    public function edit(User $user, Package $item)
    {
        return false;
    }

    /**
     * Determine whether the user can delete the item.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Package  $item
     * @return boolean
     */
    public function delete(User $user, Package $item)
    {
        return false;
    }
}
