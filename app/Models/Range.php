<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Range extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'description', 'company_id'];

    public function company()
    {
        return $this->belongsTo('App\Models\Company', 'company_id', 'id')->withTrashed();
    }

    public function repetitive_tasks()
    {
        return $this->hasMany('App\Models\RepetitiveTask', 'range_id')->orderBy('order')->with('skills', 'documents');
    }

    public function restoreCascade()
    {
        RepetitiveTask::withTrashed()->where('range_id', $this->id)->restore();
        return $this->restore();
    }

    public function deleteCascade()
    {
        RepetitiveTask::where('range_id', $this->id)->delete();
        return $this->delete();
    }
}
