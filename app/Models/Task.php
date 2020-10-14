<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'order', 'description', 'date', 'estimated_time', 'time_spent', 'tasks_bundle_id', 'workarea_id', 'created_by', 'status', 'user_id'];

    protected $appends = ['dateEnd'];

    public function getDateEndAttribute()
    {
        //return $this->date;
        return $this->date && $this->estimated_time ? Carbon::parse($this->date)->addHours($this->estimated_time) : null;
    }

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

    public function previousTasks()
    {
        return $this->hasMany('App\Models\PreviousTask', 'task_id');
    }

    public function project()
    {
        return $this->hasOneThrough('App\Models\Project', 'App\Models\TasksBundle', 'id', 'id', 'tasks_bundle_id', 'project_id');
    }

    public function documents()
    {
        return $this->belongsToMany(Document::class, ModelHasDocuments::class, 'model_id', 'document_id')->where('model', Task::class);
    }
}
