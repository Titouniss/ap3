<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TasksBundle extends BaseModel
{
    use SoftDeletes, HasCompany;

    protected $fillable = ['id', 'company_id', 'project_id'];
}
