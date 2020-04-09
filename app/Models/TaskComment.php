<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\User;

class TaskComment extends Model
{

    protected $fillable = ['description', 'confirmed', 'task_id', 'created_by'];

    protected $appends = ['creator'];

    public function getCreatorAttribute()
    {
        return User::find($this->created_by);
    }
}
