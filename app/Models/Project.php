<?php

namespace App\Models;

use App\Models\Task;
use App\Traits\HasCompany;
use App\Traits\HasDocuments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasDocuments, SoftDeletes, HasCompany;

    protected $fillable = ['name', 'start_date', 'date', 'status', 'company_id', 'color', 'is_hidden', 'customer_id'];

    protected $appends = ['progress'];

    public function getProgressAttribute()
    {
        $progress = [];
        $tasks = $this->hasManyThrough(Task::class, TasksBundle::class, 'project_id', 'tasks_bundle_id')->get();
        $tasks->load('taskTimeSpent');

        $progress['nb_task'] = $tasks->count();
        $progress['nb_task_done'] = $tasks->isNotEmpty() ? $tasks->filter(function ($task) { return $task->status === "done"; })->count() : 0;
        $progress['task_percent'] = $progress['nb_task'] ? floor(100 * $progress['nb_task_done'] / $tasks->count()) : 0;

        $progress['nb_task_time'] = 0;
        $progress['nb_task_time_done'] = 0;
        foreach($tasks as $task){
            $progress['nb_task_time'] += $task->estimated_time;
            $progress['nb_task_time_done'] += $task->time_spent;
        }
        $progress['task_time_percent'] = $progress['nb_task_time'] ? floor(100 * $progress['nb_task_time_done'] / $progress['nb_task_time']) : 0;

        return $progress;
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function tasksBundles()
    {
        return $this->hasMany(TasksBundle::class);
    }

    public function tasks()
    {
        return $this->hasManyThrough(Task::class, TasksBundle::class, 'project_id', 'tasks_bundle_id')->with('skills', 'previousTasks');
    }
}
