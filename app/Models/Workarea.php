<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workarea extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'company_id'];

    public function company()
    {
        return $this->belongsTo('App\Models\Company', 'company_id', 'id')->withTrashed();
    }

    public function skills()
    {
        return $this->belongsToMany('App\Models\Skill', 'workareas_skills', 'workarea_id');
    }
}
