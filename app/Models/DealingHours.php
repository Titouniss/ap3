<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DealingHours extends Model
{

    protected $fillable = ['user_id', 'date', 'overtimes', 'used_hours', 'used_type'];

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }
}
