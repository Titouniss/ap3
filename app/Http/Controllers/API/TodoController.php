<?php

namespace App\Http\Controllers\API;
use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Traits\StoresDocuments;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Todo;
use App\Models\TodoTags;
use App\User;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Exception;

class TodoController extends BaseApiController
{
   use StoresDocuments;
   protected static $index_load = ['user','tags'];
   protected static $index_append = null;
   protected static $show_load = ['user','tags'];
   protected static $show_append = null;

   protected static $store_validation_array = [
       'title' => 'required',
       'description' => 'nullable',
       'due_date' => 'required|date',
       'is_completed' => 'required',
       'is_important'=>'required',
       'user_id' => 'nullable',
       
       
   ];

   protected static $update_validation_array = [
    'title' => 'required',
    'description' => 'nullable',
    'due_date' => 'required|date',
    'is_completed' => 'required',
    'is_important'=>'required',
    'user_id' => 'nullable',
   ];
   public function __construct()
   {
       parent::__construct(Todo::class);
   }
   protected function filterIndexQuery(Request $request, $query)
   {
    $user = Auth::user();
    if (!$user->is_admin) {
        if ($user->is_manager) {
            $query->join('users', function ($join) use ($user) {
                $join->on('todo.user_id', '=', 'users.id')
                    ->where('users.company_id', $user->company_id)->get();
            });
        } else {
            $query->where('user_id', $user->id)->get();
        }
    }

   if ($request->has('tag_id')) {
    if (TodoTags::where('tag_id', $request->tag_id)->doesntExist()) {
        throw new Exception("Paramètre 'tag_id' n'est pas valide.");
    }

    $query->join('todo_tags', function ($join) use ($request) {
        $join->on('todo_tags.todo_id', '=', 'todos.id')
            ->where('todo_tags.tag_id', $request->tag_id);
    });
    }   

    if($request->has('is_completed'))
    {
        $query->where('is_completed',$request->is_completed)->get();
    }

    if($request->has('is_important'))
    {
        if(Todo::where('is_completed',true))
        {
            $query->where('is_completed',false)->get();        
        }
        $query->where('is_important',$request->is_important)->get();
    }
    if($request->has('order_by'))
    {
        $query->orderBy($request->order_by);
    }
 }


   protected function storeItem(array $arrayRequest)
   {
    $item = Todo::create([
        'title' => $arrayRequest['title'],
        'description' => $arrayRequest['description'] ?? null,
        'due_date' => $arrayRequest['due_date'],
        'is_completed' => $arrayRequest['is_completed'],
        'is_important' => $arrayRequest['is_important'],
        'user_id' => Auth::id(),
    ]);
    $this->storeTodoTags($item->id, $arrayRequest['tags'] ?? null);
    $item->save();
    // $controllerLog = new Logger('hours');
    // $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')),Logger::INFO);
    // $controllerLog->info('dernierJour',[$item]);
    // $this->checkIfTodoTagExist($item->id);
    return $item;
   }
    private function storeTodoTags($todo_id, $tags)
   {
    if (count($tags) > 0 && $todo_id) {
        foreach ($tags as $tag_id) {
       TodoTags::create(['todo_id' => $todo_id, 'tag_id' => $tag_id]);
        }
    }
   }
   private function updateTodoTags($todo_id, $tags)
    {
        TodoTags::where('todo_id', $todo_id)->delete();
        if (count($tags) > 0 && $todo_id) {
            foreach ($tags as $tag_id) {
           TodoTags::create(['todo_id' => $todo_id, 'tag_id' => $tag_id]);
            }
        }
    }
   protected function updateItem($item, array $arrayRequest)
   {
    $item->update([
        'title' => $arrayRequest['title'],
        'description' => $arrayRequest['description'] ?? null,
        'due_date' => $arrayRequest['due_date'],
        'is_completed' => $arrayRequest['is_completed'],
        'is_important' => $arrayRequest['is_important'],
        'user_id' => Auth::id(),
    ]);
   if(isset($arrayRequest['tags']))
   {
    $this->updateTodoTags($item->id, $arrayRequest['tags'] ?? null);
    $controllerLog = new Logger('hours');
    $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')),Logger::INFO);
    $controllerLog->info('tags',[$item]);

   }
   $controllerLog = new Logger('hours');
    $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')),Logger::INFO);
    $controllerLog->info('item',[$item]);

    $item->save();


    return $item;
   }
  
   public function addTodo(Request $request, int $id)
   {
       $item = Todo::find($id);
       if ($error = $this->itemErrors($item, 'edit')) {
           return $error;
       }

       $arrayRequest = $request->all();
       $validator = Validator::make($arrayRequest, ['todo' => 'required']);
       if ($validator->fails()) {
           return $this->errorResponse($validator->errors());
       }
       if (static::$show_load) {
           $item->load(static::$show_load);
       }

       if (static::$show_append) {
           $item->append(static::$show_append);
       }

       return $this->successResponse($item, 'Tâche ajouté avec succès');
   }
   private function checkIfTodoTagExist(int $todo)
   {

       $exist = TodoTags::where('todo_id', $todo)->first();
       if (!$exist) {
           $todo = Todo::find($todo);
           if ($todo) {
               return TodoTags::create(['todo_id' => $todo]);
           }
       }
       return $exist;
   }

}
