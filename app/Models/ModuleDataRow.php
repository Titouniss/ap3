<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModuleDataRow extends Model
{
    protected $fillable = ['source', 'default_value', 'details', 'data_row_id', 'module_data_type_id'];

    public function moduleDataType()
    {
        return $this->belongsTo(ModuleDataType::class, 'module_data_type_id', 'id');
    }

    public function dataRow()
    {
        return $this->belongsTo(DataRow::class, 'data_row_id', 'id');
    }
}
