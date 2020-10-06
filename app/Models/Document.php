<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = ['id', 'name', 'path', 'task_id', 'token'];
}
