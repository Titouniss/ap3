<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Supply extends Model
{
    use HasCompany, SoftDeletes;

    protected $fillable = ['id','name', 'company_id'];
    public function tasks()
    {
        return $this->belongsToMany('App\Models\Task', 'tasks_supplies', 'supply_id');
    }
}
