<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends BaseModel
{
    use SoftDeletes, HasCompany;

    protected $fillable = ['company_id', 'starts_at', 'ends_at', 'state', 'is_trial'];
    protected $dates = ['starts_at', 'ends_at'];
    protected $hidden = ['permissions'];

    public function getPermissionsAttribute()
    {
        $permissions = collect([]);
        foreach ($this->packages as $package) {
            $permissions = $permissions->merge($package->permissions);
        }
        return $permissions->unique();
    }

    public function packages()
    {
        return $this->belongsToMany(Package::class, 'subscription_has_packages', 'subscription_id', 'package_id');
    }
}
