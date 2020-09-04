<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataType extends Model
{
    protected $fillable = ['slug', 'display_name_singular', 'display_name_plurial', 'model', 'order'];
    public $timestamps = false;

    public function getModelAttribute($value)
    {
        return app($value);
    }

    public function dataRows()
    {
        return $this->hasMany(DataRow::class);
    }
}
