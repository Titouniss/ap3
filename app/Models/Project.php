<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Task;
use Illuminate\Database\Eloquent\SoftDeletes;


class Project extends Model
{
    use SoftDeletes;


    protected $fillable = [ 'name', 'date', 'status','company_id'];

    protected $appends = ['tasks'];

    public function company()
    {
        return $this->belongsTo('App\Models\Company', 'company_id', 'id');
    }

    public function tasksBundles()
    {
        return $this->hasMany('App\Models\TasksBundle');
    }

    public function getTasksAttribute()
    {
        $tasksBundles = $this->tasksBundles;
        $tasks = [];
        foreach ($tasksBundles as $t) {
            $tasks = Task::where('tasks_bundle_id', $t->id)->get();
        }
        return $tasks;
    }

}
