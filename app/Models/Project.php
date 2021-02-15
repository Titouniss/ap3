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

    protected $fillable = ['name', 'start_date', 'date', 'status', 'company_id', 'color', 'customer_id'];

    protected $appends = ['tasks', 'progress'];

    public function getProgressAttribute()
    {
        return floor(100 * ($this->tasks->isNotEmpty() ? $this->tasks->filter(function ($task) {
            return $task->status === "done";
        })->count() / $this->tasks->count() : 0));
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function tasksBundles()
    {
        return $this->hasMany(TasksBundle::class);
    }

    public function getTasksAttribute()
    {
        $tasksBundles = $this->tasksBundles;
        $tasks = collect();
        foreach ($tasksBundles as $t) {
            $tasks = Task::where('tasks_bundle_id', $t->id)->with('skills', 'previousTasks')->get();
        }
        return $tasks;
    }
}
