<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsersSkill extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'skill_id'];

    protected $casts = [];

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id');
    }
    public function skill()
    {
        return $this->belongsTo('App\Models\Skill', 'skill_id');
    }
}
