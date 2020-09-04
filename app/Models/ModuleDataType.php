<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModuleDataType extends Model
{
    protected $fillable = ['source', 'data_type_id', 'module_id'];

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
