<?php

namespace App\Models;

class ModelHasDocuments extends BaseModel
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
