<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkHours extends Model
{
    protected $table = 'user_work_hours';
    protected $fillable = ['day', 'morning_starts_at', 'morning_ends_at', 'afternoon_starts_at', 'afternoon_ends_at', 'is_active', 'user_id'];

    public static $days = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id');
    }
}
