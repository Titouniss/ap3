<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataRow extends Model
{
    protected $fillable = ['name', 'field', 'required', 'type', 'details'];
    public $timestamps = false;
}
