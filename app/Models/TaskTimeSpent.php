<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class TaskTimeSpent extends Model
{
    protected $table = "task_time_spent";
    protected $fillable = ['date', 'duration', 'user_id', 'task_id'];
    protected $casts = ['date' => 'date'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id', 'id');
    }
}
