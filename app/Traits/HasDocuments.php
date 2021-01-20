<?php

namespace App\Traits;

use App\Models\Document;

trait HasDocuments
{
    /**
     * All associated documents
     */
    public function documents()
    {
        return $this->belongsToMany(Document::class, 'model_has_documents', 'model_id', 'document_id')->wherePivot('model', static::class);
    }
}
