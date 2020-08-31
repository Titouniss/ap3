<?php

namespace App\Models;

class SqlModule extends BaseModule
{
    protected $fillable = ['sql_type', 'host', 'port', 'database', 'user', 'password'];

    public function module()
    {
        return $this->morphOne(BaseModule::class, 'modulable');
    }
}
