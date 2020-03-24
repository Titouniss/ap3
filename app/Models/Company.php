<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [ 'name', 'siret', 'expire_at'];

    public function skills()
    {
        return $this->hasMany('App\Models\Skill', 'company_id');
    }
}
