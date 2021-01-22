<?php

namespace App\Models;

class PreviousTask extends BaseModel
{
    public $timestamps = false;

    protected $fillable = ['task_id', 'previous_task_id'];

    public function previousTask()
    {
        return $this->belongsTo('App\Models\Task', 'previous_task_id');
    }
}
