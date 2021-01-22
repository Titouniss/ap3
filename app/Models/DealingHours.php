<?php

namespace App\Models;

class DealingHours extends BaseModel
{

    protected $fillable = ['user_id', 'date', 'overtimes', 'used_hours', 'used_type'];

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }
}
