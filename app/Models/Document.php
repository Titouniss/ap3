<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class Document extends Model
{
    protected $fillable = ['id', 'name', 'path', 'token'];

    protected $appends = ['url'];

    public function getUrlAttribute()
    {
        $pathArray = explode('/', $this->path);
        return URL::to('/api/document-management/get-file') . '/' . array_pop($pathArray);
    }

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
