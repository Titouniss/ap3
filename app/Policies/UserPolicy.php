<?php

namespace App\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy extends BasePolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct('users', 'company_id');
    }

    /**
     * Determine whether the user can view the item.
     *
     * @param  \App\User  $currentUser
     * @param  \App\User  $user
     * @return boolean
     */
    public function show(User $currentUser, User $user)
    {
        return $this->canShow($currentUser, $user) || (!$user->exists || $currentUser->id == $user->id);
    }

    /**
     * Determine whether the user can publish an item.
     *
     * @param  \App\User  $currentUser
     * @return boolean
     */
    public function publish(User $currentUser)
    {
        return $this->canPublish($currentUser);
    }

    /**
     * Determine whether the user can edit the item.
     *
     * @param  \App\User  $currentUser
     * @param  \App\User  $user
     * @return boolean
     */
    public function edit(User $currentUser, User $user)
    {
        return $this->canEdit($currentUser, $user) || (!$user->exists || $currentUser->id == $user->id);
    }

    /**
     * Determine whether the user can delete the item.
     *
     * @param  \App\User  $currentUser
     * @param  \App\User  $user
     * @return boolean
     */
    public function delete(User $currentUser, User $user)
    {
        return $this->canDelete($currentUser, $user) || (!$user->exists || $currentUser->id != $user->id);
    }
}
