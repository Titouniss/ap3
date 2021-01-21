<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use SoftDeletes;

    protected $fillable = ['name', 'code', 'guard_name', 'description', 'company_id', 'is_public'];
    protected $hidden = ['guard_name'];

    public function getIsAdminAttribute()
    {
        return $this->code === "super_admin";
    }

    public function getIsManagerAttribute()
    {
        return $this->code === "admin";
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
