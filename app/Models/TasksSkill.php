<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TasksSkill extends Model
{
    public $timestamps = false;
    
    protected $fillable = ['task_id', 'skill_id'];
}
