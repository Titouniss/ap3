<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasDocuments;
class TodoTags extends Model
{
    use HasDocuments;
    protected $fillable = ['todo_id','tag_id'];
}
