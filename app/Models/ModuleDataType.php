<?php

namespace App\Models;

class ModuleDataType extends BaseModel
{
    protected $fillable = ['source', 'data_type_id', 'module_id'];
    protected $hidden = ['created_at', 'updated_at'];

    public function module()
    {
        return $this->belongsTo(BaseModule::class, 'module_id', 'id');
    }

    public function dataType()
    {
        return $this->belongsTo(DataType::class, 'data_type_id', 'id');
    }

    public function moduleDataRows()
    {
        return $this->hasMany(ModuleDataRow::class, 'module_data_type_id', 'id');
    }
}
