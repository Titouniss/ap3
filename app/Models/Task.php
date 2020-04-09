<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{

    protected $fillable = ['name', 'date', 'estimated_time', 'time_spent', 'tasks_bundle_id', 'workarea_id', 'created_by', 'status'];

    public function workarea()
    {
        return $this->belongsTo('App\Models\Workarea', 'workarea_id', 'id');
    }

    public function skills()
    {
        return $this->belongsToMany('App\Models\Skill', 'tasks_skills', 'task_id');
    }

    public function comments()
    {
        return $this->hasMany('App\Models\TaskComment')->orderBy('created_at', 'DESC');
    }
}