<?php

namespace App\Traits;

trait DeleteCascades
{
    /**
     * Restore the model and it's dependencies
     */
    public abstract function restoreCascade();

    /**
     * Soft delete the model and it's dependencies
     */
    public abstract function deleteCascade();
}
