<?php

namespace App\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_id', 'start_date', 'end_date', 'state'];
    protected $dates = ['start_date', 'end_date'];
    protected $appends = ['permissions'];

    public function getPermissionsAttribute()
    {
        $permissions = collect([]);
        $packages = $this->belongsToMany(Package::class, 'subscription_has_packages', 'subscription_id', 'package_id')->get();
        foreach ($packages as $package) {
            $permissions = $permissions->merge($package->permissions);
        }
        return $permissions;
    }

    public function getStatusOrderAttribute()
    {
        switch ($this->state) {
            case 'active':
                return 0;
            case 'pending':
                return 1;
            case 'cancelled':
                return 2;
            case 'inactive':
                return 2;

            default:
                return 3;
        }
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function packages()
    {
        return $this->belongsToMany(Package::class, 'subscription_has_packages', 'subscription_id', 'package_id');
    }
}
