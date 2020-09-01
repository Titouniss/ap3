<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BaseModule extends Model
{
    protected $fillable = ['name', 'modulable_id', 'modulable_type', 'last_synced_at', 'company_id'];
    protected $appends = ['type'];

    public function getTypeAttribute()
    {
        return $this->modulable_type === SqlModule::class ? "sql" : "api";
    }

    public function modulable()
    {
        return $this->morphTo();
    }

    public function company()
    {
        return $this->belongsTo('App\Models\Company', 'company_id', 'id')->withTrashed();
    }

    public function moduleDataTypes()
    {
        return $this->hasMany(ModuleDataType::class, 'module_id', 'id');
    }
}
