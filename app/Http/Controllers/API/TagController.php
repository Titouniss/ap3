<?php

namespace App\Http\Controllers\API;
use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use App\Traits\StoresDocuments;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Tag;
use App\Models\User;

class TagController extends BaseApiController
{
   use StoresDocuments;
   protected static $index_load = ['user','todos'];
   protected static $index_append = null;
   protected static $show_load = ['user','todos'];
   protected static $show_append = null;

   protected static $store_validation_array = [
       'title' => 'required',
       'color' => 'nullable',
       'user_id' => 'nullable',
       
   ];

   protected static $update_validation_array = [
    'title' => 'required',
    'color' => 'nullable',
    'user_id' => 'nullable',
   ];
   public function __construct()
   {
       parent::__construct(Tag::class);
   }

   protected function filterIndexQuery(Request $request, $query)
   {

   }

   protected function storeItem(array $arrayRequest)
   {
    $item = Tag::create([
        'title' => $arrayRequest['title'],
        'color' => $arrayRequest['color'] ?? null,
        'user_id' => Auth::id(),
    ]);
    $item->save();
    return $item;
   }
    protected function updateItem($item, array $arrayRequest)
    {

    }
   //Ajout d'un tag
   public function addTag(Request $request, int $id)
   {
       $item = Tag::find($id);
       if ($error = $this->itemErrors($item, 'edit')) {
           return $error;
       }

       $arrayRequest = $request->all();
       $validator = Validator::make($arrayRequest, ['tag' => 'required']);
       if ($validator->fails()) {
           return $this->errorResponse($validator->errors());
       }

       $this->storeTag($item->id, $request->tag);

       if (static::$show_load) {
           $item->load(static::$show_load);
       }

       if (static::$show_append) {
           $item->append(static::$show_append);
       }

       return $this->successResponse($item, 'Tag ajouté avec succès');
   }

}
