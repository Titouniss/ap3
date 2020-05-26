<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hours extends Model
{

    protected $fillable = ['date', 'duration', 'description', 'user_id', 'project_id'];

    public function project()
    {
        return $this->belongsTo('App\Models\Project', 'project_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }
}
