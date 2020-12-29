<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasPermissions;

class SubscriptionComponent extends Model
{
    use HasPermissions;

    protected $fillable = ['name', 'display_name', 'guard_name'];
}
