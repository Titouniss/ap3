<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RepetitiveTask extends Model
{
    use  SoftDeletes;
    protected $fillable = ['name', 'order', 'description', 'estimated_time', 'range_id', 'workarea_id'];

    public function workarea()
    {
        return $this->belongsTo('App\Models\Workarea', 'workarea_id', 'id');
    }

    public function skills()
    {
        return $this->belongsToMany('App\Models\Skill', 'repetitive_tasks_skills', 'repetitive_task_id');
    }

    public function documents()
    {
        return $this->belongsToMany(Document::class, ModelHasDocuments::class, 'model_id', 'document_id')->where('model', RepetitiveTask::class);
    }
}
