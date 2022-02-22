<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TasksBundle extends Model
{
    use SoftDeletes, HasCompany;

    protected $fillable = ['id', 'company_id', 'project_id'];
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }
}
