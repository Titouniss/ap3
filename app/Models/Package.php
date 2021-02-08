<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasPermissions;

class Package extends Model
{
    use HasPermissions;

    protected $fillable = ['name', 'display_name', 'guard_name'];
    protected $hidden = ['guard_name'];

    public function subscriptions()
    {
        return $this->belongsToMany(Subscription::class);
    }
}
