<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use App\Models\Task;
use App\Models\TasksBundle;

class TaskComment extends Model
{
    protected $fillable = ['description', 'confirmed', 'task_id', 'created_by', 'created_at'];
    protected $appends = ['creator','project'];

    public function getCreatorAttribute()
    {
        return User::find($this->created_by);
    }
     public function task()
    {
        return $this->belongsTo(Task::class, 'task_id', 'id');
    }
    public function getProjectAttribute()
    {
        return $this->HasMany(Task::class, 'project_id', 'project_id');
    }
 }
