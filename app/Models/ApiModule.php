<?php

namespace App\Models;

class ApiModule extends BaseModule
{
    protected $fillable = ['url', 'auth_headers'];
    protected $hidden = ['connection'];

    public $timestamps = false;

    public function getConnectionAttribute()
    {
        return [
            'id' => $this->id,
            'url' => $this->url ?? "",
            'auth_headers' => $this->auth_headers ?? "",
        ];
    }

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
