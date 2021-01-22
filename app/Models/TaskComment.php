<?php

namespace App\Models;

use App\User;

class TaskComment extends BaseModel
{
    protected $fillable = ['description', 'confirmed', 'task_id', 'created_by', 'created_at'];
    protected $appends = ['creator'];

    public function getCreatorAttribute()
    {
        return User::find($this->created_by);
    }
}
