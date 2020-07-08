<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreviousTask extends Model
{
    public $timestamps = false;
    
    protected $fillable = ['task_id', 'previous_task_id'];

    public function previousTask()
    {
        return $this->belongsTo('App\Models\Task', 'previous_task_id');
    }
}
