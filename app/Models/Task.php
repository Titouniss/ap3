<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{

    protected $fillable = ['name', 'order', 'description', 'date', 'estimated_time', 'time_spent', 'tasks_bundle_id', 'workarea_id', 'created_by', 'status', 'user_id'];

    public function workarea()
    {
        return $this->belongsTo('App\Models\Workarea', 'workarea_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');

    }

    public function skills()
    {
        return $this->belongsToMany('App\Models\Skill', 'tasks_skills', 'task_id');
    }

    public function comments()
    {
        return $this->hasMany('App\Models\TaskComment')->orderBy('created_at', 'DESC');
    }

    // public function previousTasks()
    // {
    //     return $this->belongsToMany('App\Models\Task', 'previous_tasks', 'task_id');
    // }

    public function previousTasks()
    {
        return $this->hasMany('App\Models\PreviousTask', 'task_id');
    }
}
