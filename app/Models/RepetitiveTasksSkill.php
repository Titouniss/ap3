<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepetitiveTasksSkill extends Model
{
    public $timestamps = false;
    
    protected $fillable = ['repetitive_task_id', 'skill_id'];
}
