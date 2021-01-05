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
        $this->belongsToMany(Package::class, 'subscription_has_packages', 'subscription_id', 'package_id')->each(function ($package) use ($permissions) {
            $permissions->push($package->permissions);
        });
        return $permissions;
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
