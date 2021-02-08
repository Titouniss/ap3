<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModelHasDocuments extends Model
{
    protected $fillable = ['document_id', 'model_id', 'model'];
    protected $table = "model_has_documents";
    public $timestamps = false;

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function model()
    {
        return $this->belongsTo($this->model, 'model_id');
    }
}
