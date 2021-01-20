<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class Document extends Model
{
    protected $fillable = ['id', 'name', 'path', 'token', 'is_file'];
    protected $appends = ['url'];
    protected $hidden = ['has_models', 'created_at', 'updated_at', 'pivot'];

    public function getUrlAttribute()
    {
        $pathArray = explode('/', $this->path);
        return $this->is_file ? URL::to('/api/document-management/get-file') . '/' . array_pop($pathArray) : $this->path;
    }

    public function getHasModelsAttribute()
    {
        return DB::table('model_has_documents')->where('document_id', $this->id)->exists();
    }

    public function moveFile($subFolder = "")
    {
        if ($this->is_file) {
            $newPath = str_replace('documents/tmp/', 'documents/' . $subFolder . '/', $this->path);
            Storage::move($this->path, $newPath);
            $this->path = $newPath;
            $this->save();
        }
    }

    public function deleteFile()
    {
        ModelHasDocuments::where('document_id', $this->id)->delete();
        if ($this->is_file) {
            Storage::delete($this->path);
        }
        $this->delete();
    }
}
