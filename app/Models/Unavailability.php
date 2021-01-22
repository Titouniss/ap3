<?php

namespace App\Models;

class Unavailability extends BaseModel
{
    protected $table = 'user_unavailabilities';
    protected $fillable = ['reason', 'starts_at', 'ends_at', 'user_id'];

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id');
    }
}
