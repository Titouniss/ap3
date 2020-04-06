<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Indisponibility extends Model
{
    protected $table = 'user_indisponibilities';
    protected $fillable = [ 'reason', 'start_at', 'end_at'];

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id');
    }
}
