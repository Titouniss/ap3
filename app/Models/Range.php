<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Range extends Model
{
    use SoftDeletes;

    protected $fillable = [ 'name', 'description', 'company_id'];

    public function company()
    {
        return $this->belongsTo('App\Models\Company', 'company_id', 'id');
    }
    
    public function repetitive_tasks()
    {
        return $this->hasMany('App\Models\RepetitiveTask', 'range_id')->orderBy('order')->with('skills');
    }
}
