<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Range extends Model
{
    use SoftDeletes, HasCompany;

    protected $fillable = ['name', 'description', 'company_id'];

    public function repetitive_tasks()
    {
        return $this->hasMany('App\Models\RepetitiveTask', 'range_id')->orderBy('order')->with('workarea:workareas.id,name,max_users', 'skills:skills.id,name', 'documents');
    }
}
