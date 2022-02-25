<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasDocuments;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\User;
class Todo extends Model
{
    use SoftDeletes, HasDocuments;
    protected $fillable = ['title','description','due_date', 'is_completed','is_important','user_id','created_by'];
    protected $appends = ['creator']; 
    
    public function getCreatorAttribute()
    {
        return User::find($this->created_by);
    }
    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }
   
    public function tags()
    {
        return $this->belongsToMany('App\Models\Tag', 'todo_tags', 'todo_id');
    }
    
}
