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
     * Determine whether the item can view the item.
     *
     * @param  \App\User  $user
     * @param  \App\User  $item
     * @return boolean
     */
    public function show(User $user, User $item)
    {
        return $this->canShow($user, $item) || (!$item->exists || $user->id == $item->id);
    }

    /**
     * Determine whether the item can publish an item.
     *
     * @param  \App\User  $user
     * @return boolean
     */
    public function publish(User $user)
    {
        return $this->canPublish($user);
    }

    /**
     * Determine whether the item can edit the item.
     *
     * @param  \App\User  $user
     * @param  \App\User  $item
     * @return boolean
     */
    public function edit(User $user, User $item)
    {
        return $this->canEdit($user, $item) || (!$item->exists || $user->id == $item->id);
    }

    /**
     * Determine whether the item can delete the item.
     *
     * @param  \App\User  $user
     * @param  \App\User  $item
     * @return boolean
     */
    public function delete(User $user, User $item)
    {
        return $this->canDelete($user, $item) || (!$item->exists || $user->id != $item->id);
    }
}
