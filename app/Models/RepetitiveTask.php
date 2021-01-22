<?php

namespace App\Models;

use App\Traits\HasDocuments;
use Illuminate\Database\Eloquent\SoftDeletes;

class RepetitiveTask extends BaseModel
{
    use HasDocuments, SoftDeletes;
    protected $fillable = ['name', 'order', 'description', 'estimated_time', 'range_id', 'workarea_id'];

    public function workarea()
    {
        return $this->belongsTo('App\Models\Workarea', 'workarea_id', 'id');
    }

    public function skills()
    {
        return $this->belongsToMany('App\Models\Skill', 'repetitive_tasks_skills', 'repetitive_task_id');
    }
}
