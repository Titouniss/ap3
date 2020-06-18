<?php

namespace App\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;
    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\User  $user
     * @param  \App\User  $model
     * @return mixed
     */
    public function read(User $user, User $model)
    {
        return $user->id == $model->id || $user->can('read users');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\User  $user
     * @param  \App\User  $model
     * @return mixed
     */
    public function edit(User $user, User $model)
    {
        return $user->id == $model->id || $user->can('edit users');
    }
}
