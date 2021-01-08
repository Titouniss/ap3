<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'siret',
        'code',
        'type',
        'contact_firstname',
        'contact_lastname',
        'contact_function',
        'contact_email',
        'contact_tel1',
        'contact_tel2',
        'street_number',
        'street_name',
        'postal_code',
        'city',
        'country',
    ];
    protected $appends = ['user_count', 'has_active_subscription', 'active_permissions'];

    public function getUserCountAttribute()
    {
        return User::where('company_id', $this->id)->count();
    }

    public function getActiveSubscriptionsAttribute()
    {
        return Subscription::where('company_id', $this->id)->where('state', 'active')->with('packages')->get();
    }

    public function getHasActiveSubscriptionAttribute()
    {
        return $this->active_subscriptions->count() > 0;
    }

    public function getActivePermissionsAttribute()
    {
        $permissions = collect([]);
        $subscriptions = $this->active_subscriptions;
        foreach ($subscriptions as $subscription) {
            $permissions = $permissions->merge($subscription->permissions);
        }
        return $permissions;
    }

    public function module()
    {
        return $this->hasOne(BaseModule::class, 'company_id', 'id');
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

    public function restoreCascade()
    {
        $this->restore();
        Skill::withTrashed()->where('company_id', $this->id)->restore();
        Workarea::withTrashed()->where('company_id', $this->id)->restore();

        foreach (Customers::withTrashed()->where('company_id', $this->id)->get() as $customer) {
            $customer->restoreCascade();
        }
        foreach (Range::withTrashed()->where('company_id', $this->id)->get() as $range) {
            $range->restoreCascade();
        }
        foreach (Project::withTrashed()->where('company_id', $this->id)->get() as $project) {
            $project->restoreCascade();
        }

        Subscription::withTrashed()->where('company_id', $this->id)->restore();
        User::withTrashed()->where('company_id', $this->id)->restore();
        Role::withTrashed()->where('company_id', $this->id)->restore();
        return true;
    }

    public function deleteCascade()
    {
        Role::where('company_id', $this->id)->delete();
        User::where('company_id', $this->id)->delete();
        Subscription::where('company_id', $this->id)->delete();

        foreach (Project::where('company_id', $this->id)->get() as $project) {
            $project->deleteCascade();
        }
        foreach (Range::where('company_id', $this->id)->get() as $range) {
            $range->deleteCascade();
        }
        foreach (Customers::where('company_id', $this->id)->get() as $customer) {
            $customer->deleteCascade();
        }

        Workarea::where('company_id', $this->id)->delete();
        Skill::where('company_id', $this->id)->delete();
        return $this->delete();
    }
}
