<?php

namespace App\Policies;

use App\Models\Workarea;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class WorkareaPolicy extends BasePolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct('workareas', 'company_id');
    }

    /**
     * Determine whether the user can view the item.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Workarea  $item
     * @return boolean
     */
    public function show(User $user, Workarea $item)
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
     * @param  \App\Models\Workarea  $item
     * @return boolean
     */
    public function edit(User $user, Workarea $item)
    {
        return $this->canEdit($user, $item);
    }

    /**
     * Determine whether the user can delete the item.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Workarea  $item
     * @return boolean
     */
    public function delete(User $user, Workarea $item)
    {
        return $this->canDelete($user, $item);
    }
}
