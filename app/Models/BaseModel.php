<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    public static function usesSoftDelete()
    {
        return in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses(static::class));
    }

    public static function hasCompany()
    {
        return in_array('App\Traits\HasCompany', class_uses(static::class));
    }
}
