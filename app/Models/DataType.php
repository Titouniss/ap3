<?php

namespace App\Models;

class DataType extends BaseModel
{
    protected $fillable = ['slug', 'display_name_singular', 'display_name_plurial', 'model', 'order'];
    public $timestamps = false;

    public function dataRows()
    {
        return $this->hasMany(DataRow::class);
    }
}
