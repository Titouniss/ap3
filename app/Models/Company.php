<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'siret', 'is_trial', 'expires_at'];

    public function skills()
    {
        return $this->hasMany('App\Models\Skill', 'company_id');
    }
}
