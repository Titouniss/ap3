<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Task;
use Illuminate\Database\Eloquent\SoftDeletes;


class Project extends Model
{
    use SoftDeletes;


    protected $fillable = ['name', 'date', 'status', 'company_id', 'color', 'customer_id'];

    protected $appends = ['tasks'];

    public function company()
    {
        return $this->belongsTo('App\Models\Company', 'company_id', 'id')->withTrashed();
    }

    public function customer()
    {
        return $this->belongsTo('App\Models\Customers', 'customer_id', 'id');
    }

    public function tasksBundles()
    {
        return $this->hasMany('App\Models\TasksBundle');
    }

    public function documents()
    {
        return $this->belongsToMany(Document::class, ModelHasDocuments::class, 'model_id', 'document_id')->where('model', Project::class);
    }

    public function getTasksAttribute()
    {
        $tasksBundles = $this->tasksBundles;
        $tasks = [];
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
        foreach ($this->documents as $doc) {
            if ($doc->models()->count() == 1) {
                $doc->deleteFile();
            }
        }
        return $this->delete();
    }
}
