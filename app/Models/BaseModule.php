<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BaseModule extends Model
{
    protected $fillable = ['name', 'last_synced_at', 'company_id'];

    public function modulable()
    {
        return $this->morphTo();
    }

    public function company()
    {
        $this->belongsTo('App\Models\Company')->withTrashed();
    }
}
