<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Workarea extends Model
{
    protected $fillable = [ 'name', 'company_id'];

    public function company()
    {
        return $this->belongsTo('App\Models\Company', 'company_id', 'id');
    }

    public function skills()
    {
        return $this->belongsToMany('App\Models\Skill', 'workareas_skills', 'workarea_id');
    }

}
