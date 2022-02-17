<?php

namespace App\Policies;

use App\Models\Todo;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TodoPolicy extends BasePolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct('todos');
    }

    /**
     * Determine whether the user can view the item.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Todo  $item
     * @return boolean
     */
    public function show(User $user, Todo $item)
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
     * @param  \App\Models\Todo  $item
     * @return boolean
     */
    public function edit(User $user, Todo $item)
    {
        return $this->canEdit($user, $item);
    }

    /**
     * Determine whether the user can delete the item.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Todo  $item
     * @return boolean
     */
    public function delete(User $user, Todo $item)
    {
        return $this->canDelete($user, $item);
    }
}
