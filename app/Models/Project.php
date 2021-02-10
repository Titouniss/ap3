<?php

namespace App\Models;

use App\Models\Task;
use App\Traits\DeleteCascades;
use App\Traits\HasCompany;
use App\Traits\HasDocuments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasDocuments, SoftDeletes, HasCompany, DeleteCascades;

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

    public function restoreCascade()
    {
        $this->restore();
        TasksBundle::withTrashed()->where('project_id', $this->id)->restore();
        foreach ($this->tasksBundles as $t) {
            Task::where('tasks_bundle_id', $t->id)->restore();
        }
        return true;
    }

    public function deleteCascade()
    {
        foreach ($this->tasksBundles as $t) {
            Task::where('tasks_bundle_id', $t->id)->delete();
        }
        TasksBundle::where('project_id', $this->id)->delete();
        return $this->delete();
    }
}
