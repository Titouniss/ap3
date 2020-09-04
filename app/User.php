<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

use Laravel\Passport\HasApiTokens;

use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{

    use  HasRoles, HasApiTokens, Notifiable, SoftDeletes;

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
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo('App\Models\Company')->withTrashed();
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
}
