<?php

namespace App\Models;

class DataRow extends BaseModel
{
    protected $fillable = ['name', 'field', 'required', 'type', 'details'];
    public $timestamps = false;
}
