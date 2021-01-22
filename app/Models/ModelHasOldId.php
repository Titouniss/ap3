<?php

namespace App\Models;

class ModelHasOldId extends BaseModel
{
    protected $fillable = ['old_id', 'new_id', 'model', 'company_id'];
    protected $table = "model_has_old_id";
    public $timestamps = false;
}
