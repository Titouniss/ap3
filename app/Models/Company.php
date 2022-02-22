<?php

namespace App\Models;

use App\Traits\HasCompanyDetails;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use SoftDeletes, HasCompanyDetails;

    protected $fillable = ['name'];
    protected $appends = ['user_count', 'has_active_subscription'];

    public function getUserCountAttribute()
    {
        return User::where('company_id', $this->id)->count();
    }

    public function getActiveSubscriptionAttribute()
    {
        return Subscription::where('company_id', $this->id)->where('state', 'active')->with('packages:packages.id,display_name')->first();
    }

    public function getHasActiveSubscriptionAttribute()
    {
        return $this->active_subscription !== null;
    }

    public function module()
    {
        return $this->hasOne(BaseModule::class, 'company_id', 'id')->where('is_active', true);
    }

    public function skills()
    {
        return $this->hasMany('App\Models\Skill', 'company_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'company_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'company_id')->orderBy('state');
    }
}
