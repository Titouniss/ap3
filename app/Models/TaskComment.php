<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use App\Models\Task;

class TaskComment extends Model
{
    protected $fillable = ['description', 'confirmed', 'task_id', 'created_by', 'created_at'];
    protected $appends = ['creator'];

    public function getCreatorAttribute()
    {
        return User::find($this->created_by);
    }
    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id', 'id');
    }
}
