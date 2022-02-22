<?php

namespace App\Models;

use App\Traits\HasCompany;
use App\Traits\HasDocuments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workarea extends Model
{
    use SoftDeletes, HasCompany, HasDocuments;

    protected $fillable = ['name', 'max_users', 'company_id'];

    public function skills()
    {
        return $this->belongsToMany('App\Models\Skill', 'workareas_skills', 'workarea_id');
    }
}
