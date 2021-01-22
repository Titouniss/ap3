<?php

namespace App\Models;

class RepetitiveTasksSkill extends BaseModel
{
    public $timestamps = false;

    protected $fillable = ['repetitive_task_id', 'skill_id'];
}
