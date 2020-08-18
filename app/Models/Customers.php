<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customers extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'lastname', 'siret', 'professional', 'company_id'];

    public function company()
    {
        return $this->belongsTo('App\Models\Company', 'company_id', 'id');
    }

    public function restoreCascade()
    {
        $projects = Project::withTrashed()->where('customer_id', $this->id)->get();
        foreach ($projects as $project) {
            $project->restoreCascade();
        }
        return $this->restore();
    }

    public function deleteCascade()
    {
        $projects = Project::where('customer_id', $this->id)->get();
        foreach ($projects as $project) {
            $project->deleteCascade();
        }
        return $this->delete();
    }

    public function forceDeleteCascade()
    {
        $projects = Project::withTrashed()->where('customer_id', $this->id)->get();
        foreach ($projects as $project) {
            $project->forceDeleteCascade();
        }
        return $this->forceDelete();
    }
}
