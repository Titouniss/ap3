<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskPeriod extends Model
{
    protected $fillable = ['start_time', 'end_time', 'task_id'];
    public $timestamps = false;
}
