<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Skill extends BaseModel
{
    use SoftDeletes, HasCompany;

    public $timestamps = false;

    protected $fillable = ['name', 'company_id'];
}
