<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TasksBundle extends Model
{
    use SoftDeletes;

    protected $fillable = ['id', 'company_id', 'project_id'];
}
