<?php

namespace App;

use App\Models\BaseModule;
use App\Models\ModelHasOldId;
use App\Models\Role;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

use Laravel\Passport\HasApiTokens;

use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasRoles, HasApiTokens, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'firstname', 'lastname', 'login', 'email', 'password', 'is_password_change', 'isTermsConditionAccepted', 'company_id', 'register_token'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'register_token'
    ];

    /**
     * The attibutes to append to the model's array form.
     *
     * @var array
     */
    // protected $appends = ['related_users'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo('App\Models\Company', 'company_id')->withTrashed();
    }

    public function workHours()
    {
        return $this->hasMany('App\Models\WorkHours');
    }

    public function unavailabilities()
    {
        return $this->hasMany('App\Models\Unavailability');
    }

    public function skills()
    {
        return $this->belongsToMany('App\Models\Skill', 'users_skills', 'user_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function getRelatedUsersAttribute()
    {
        if (
            $this->company
            && $this->company->module
            && $this->company->module->hasModuleDataTypeForSlug('users')
            && !ModelHasOldId::where('model', User::class)->where('new_id', $this->id)->exists()
        ) {
            $relatedUsers = User::whereIn('id', ModelHasOldId::where('model', User::class)
                ->pluck('new_id')
                ->filter(function ($id) {
                    return $id !== $this->id;
                }))->get(['id', 'firstname', 'lastname', 'login']);
            return $relatedUsers->filter(function ($user) {
                similar_text($this->firstname, $user->firstname, $firstnamePercentage);
                similar_text($this->lastname, $user->lastname, $lastnamePercentage);
                return $firstnamePercentage + $lastnamePercentage >= 100;
            });
        }
        return [];
    }

    /**
     * Override the mail body for reset password notification mail.
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new \App\Notifications\MailResetPasswordNotification($token));
    }
    /**
     * Override the mail body for verify email notification mail.
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new \App\Notifications\MailVerifyEmailNotification());
    }
    /**
     * Override the mail body for add user email notification mail.
     */
    public function sendEmailAdUserNotification($id, $register_token)
    {
        $this->notify(new \App\Notifications\MailAddUserNotification($id, $register_token));
    }
}
