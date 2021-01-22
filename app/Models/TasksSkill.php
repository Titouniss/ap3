<?php

namespace App\Models;

class TasksSkill extends BaseModel
{
    public $timestamps = false;
    protected $fillable = ['task_id', 'skill_id'];
}
