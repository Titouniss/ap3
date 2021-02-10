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

    /**
     * Delete associated files if unused
     */
    // public function forceDelete()
    // {
    //     foreach ($this->documents as $doc) {
    //         if ($doc->models()->count() == 1) {
    //             $doc->deleteFile();
    //         }
    //     }
    //     return parent::forceDelete();
    // }
}
