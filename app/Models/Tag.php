<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasDocuments;
use Illuminate\Database\Eloquent\SoftDeletes;
class Tag extends Model
{
    use SoftDeletes, HasDocuments;
    protected $fillable = ['title','color', 'user_id'];
    public function todos()
    {
        return $this->belongsToMany('App\Models\Todo', 'todo_tags', 'tag_id');
    }
    public function user()
    {
        return $this->belongsTo(Tag::class, 'user_id', 'id');
    }
}
