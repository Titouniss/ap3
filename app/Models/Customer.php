<?php

namespace App\Models;

use App\Traits\HasCompany;
use App\Traits\HasCompanyDetails;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes, HasCompany, HasCompanyDetails;

    protected $fillable = ['name', 'company_id'];

    public function projects()
    {
        return $this->hasMany('App\Models\Project', 'customer_id');
    }
}
