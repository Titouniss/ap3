<?php

namespace App\Models;

class TaskPeriod extends BaseModel
{
    protected $fillable = ['start_time', 'end_time', 'task_id'];
    public $timestamps = false;
}
