<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Planning extends Model
{
    protected $table = 'user_plannings';
    protected $fillable = [ 'day', 'days', 'start_at', 'end_at'];

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id');
    }
}
