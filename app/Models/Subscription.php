<?php

namespace App\Models;

use App\Models\Company;
use App\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_id', 'role_id'];
    protected $dates = ['start_date', 'end_date'];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function packages()
    {
        return $this->belongsToMany(Package::class, 'subscription_packages', 'subscription_id', 'package_id');
    }
}
