<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    protected $fillable = ['id', 'name', 'path', 'token'];

    public function moveFile($subFolder = "")
    {
        $newPath = str_replace('documents/tmp/', 'documents/' . $subFolder . '/', $this->path);
        Storage::move($this->path, $newPath);
        $this->path = $newPath;
        $this->save();
    }

    public function deleteFile()
    {
        ModelHasDocuments::where('document_id', $this->id)->delete();
        Storage::delete($this->path);
        $this->delete();
    }
}
