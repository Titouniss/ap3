<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModelHasOldId extends Model
{
    protected $fillable = ['old_id', 'new_id', 'model', 'company_id'];
    protected $table = "model_has_old_id";
    public $timestamps = false;
}
