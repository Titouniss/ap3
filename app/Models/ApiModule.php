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

    public function getData()
    {
        $data = [];
        try {
            foreach ($this->module->sortedModuleDataTypes() as $mdt) {
                // TODO
            }
        } catch (\Throwable $th) {
            $data = [];
            echo $th->getMessage();
        }
        return $data;
    }
}
