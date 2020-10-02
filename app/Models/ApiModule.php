<?php

namespace App\Models;

class ApiModule extends BaseModule
{
    protected $fillable = ['url', 'auth_headers'];

    public $timestamps = false;

    public function module()
    {
        return $this->morphOne(BaseModule::class, 'modulable');
    }

    public function getRows(ModuleDataType $mdt)
    {
        $rows = [];
        try {
            // TODO
        } catch (\Throwable $th) {
            $rows = [];
            echo $th->getMessage();
        }
        return $rows;
    }
}
