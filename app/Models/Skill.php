<?php

namespace App\Models;

use App\Traits\HasCompany;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Skill extends Model
{
    use SoftDeletes, HasCompany;

    public $timestamps = false;

    protected $fillable = ['name', 'company_id'];

    public function users() {
        return $this->belongsToMany(User::class, 'users_skills', 'skill_id', 'user_id');
    }
}
