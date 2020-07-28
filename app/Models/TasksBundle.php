<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TasksBundle extends Model
{

    protected $fillable = ['id', 'company_id', 'project_id'];
}
