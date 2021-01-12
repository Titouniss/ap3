<?php

namespace App\Policies;

use App\Models\DealingHours;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DealingHoursPolicy extends BasePolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct('dealingHours');
    }

    /**
     * Determine whether the user can view the item.
     *
     * @param  \App\User  $user
     * @param  \App\Models\DealingHours  $item
     * @return boolean
     */
    public function show(User $user, DealingHours $item)
    {
        return $this->canShow($user, $item);
    }

    /**
     * Determine whether the user can publish an item.
     *
     * @param  \App\User  $user
     * @return boolean
     */
    public function publish(User $user)
    {
        return $this->canPublish($user);
    }

    /**
     * Determine whether the user can edit the item.
     *
     * @param  \App\User  $user
     * @param  \App\Models\DealingHours  $item
     * @return boolean
     */
    public function edit(User $user, DealingHours $item)
    {
        return $this->canEdit($user, $item);
    }

    /**
     * Determine whether the user can delete the item.
     *
     * @param  \App\User  $user
     * @param  \App\Models\DealingHours  $item
     * @return boolean
     */
    public function delete(User $user, DealingHours $item)
    {
        return $this->canDelete($user, $item);
    }

    protected function otherRequirements(User $user, $item = null)
    {
        return !$item || $item->user->company_id == $user->company_id;
    }
}
