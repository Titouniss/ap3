<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Supply;
use Illuminate\Support\Facades\DB;

class TasksSupply extends Model
{
    protected $fillable = ['task_id', 'supply_id','date', 'received', 'status'];
    protected $appends = ['supply'];

    public function getSupplyAttribute()
    {
        return Supply::find($this->supply_id);
    } 
}
