<?php

namespace App\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

abstract class BasePolicy
{
    use HandlesAuthorization;

    protected $model;
    protected $companyIdField;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct($modelName, $companyIdFieldName = null)
    {
        $this->model = $modelName;
        $this->companyIdField = $companyIdFieldName;
    }

    /**
     * Grants admins full access.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function before(User $user)
    {
        if ($user->is_admin) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the items.
     *
     * @param  \App\User  $currentUser
     * @return boolean
     */
    public function read(User $user)
    {

        return $user->can("read {$this->model}");
    }

    /**
     * Determine whether the user can view the item.
     *
     * @param  \App\User  $user
     * @param  mixed  $item
     * @return boolean
     */
    public function canShow(User $user, $item)
    {
        $hasPermission = $user->can("read {$this->model}");

        if ($item->exists) {
            $hasPermission = $hasPermission && (!$this->companyIdField || $user->company_id == $item->{$this->companyIdField});
        }

        return $hasPermission && $this->otherRequirements($user, $item->exists ? $item : null);
    }

    /**
     * Determine whether the user can publish an item.
     *
     * @param  \App\User  $user
     * @return boolean
     */
    public function canPublish(User $user)
    {
        return $user->can("publish {$this->model}") && $this->otherRequirements($user);
    }

    /**
     * Determine whether the user can edit the item.
     *
     * @param  \App\User  $user
     * @param  mixed  $item
     * @return boolean
     */
    public function canEdit(User $user, $item)
    {
        $hasPermission = $user->can("edit {$this->model}");

        if ($item->exists) {
            $hasPermission = $hasPermission && (!$this->companyIdField || $user->company_id == $item->{$this->companyIdField});
        }

        return $hasPermission && $this->otherRequirements($user, $item->exists ? $item : null);
    }

    /**
     * Determine whether the user can delete the item.
     *
     * @param  \App\User  $user
     * @param  mixed  $item
     * @return boolean
     */
    public function canDelete(User $user, $item)
    {
        $hasPermission = $user->can("delete {$this->model}");

        if ($item->exists) {
            $hasPermission = $hasPermission && (!$this->companyIdField || $user->company_id == $item->{$this->companyIdField});
        }

        return $hasPermission && $this->otherRequirements($user, $item->exists ? $item : null);
    }

    /**
     * Additionnal requirements needed to have access.
     *
     * @param  \App\User  $user
     * @param  mixed  $item
     * @return boolean
     */
    protected function otherRequirements(User $user, $item = null)
    {
        return true;
    }
}
