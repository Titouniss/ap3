<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Range extends Model
{
    protected $fillable = [ 'name', 'description', 'company_id'];

    public function repetitive_tasks()
    {
        return $this->hasMany('App\Models\RepetitiveTask', 'range_id');
    }
}
