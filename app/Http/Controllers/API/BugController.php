<?php

namespace App\Http\Controllers\API;

use App\Traits\StoresDocuments;
use App\Exceptions\ApiException;
use App\Models\Bug;
use App\Models\Role;
use App\Models\Company;
use App\Models\User;

class BugController extends BaseApiController
{
    use StoresDocuments;

    protected static $index_load = ['role', 'company', 'created_by', 'documents'];
    protected static $index_append = null;
    protected static $show_load = ['role', 'company', 'created_by', 'documents'];
    protected static $show_append = null;

    protected static $store_validation_array = [
        'module' => 'required',
        'type' => 'required',
        'description' => 'required',
        'status' => 'required',
        'documents' => 'nullable|array',
        'created_by' => 'required',
    ];

    protected static $update_validation_array = [
        'module' => 'required',
        'type' => 'required',
        'description' => 'required',
        'status' => 'required',
        'documents' => 'nullable|array',
        'created_by' => 'required',
    ];

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        parent::__construct(Bug::class);
    }

    protected function storeItem(array $arrayRequest)
    {
        $item = Bug::create([
            'module' => $arrayRequest['module'],
            'type' => $arrayRequest['type'],
            'description' => $arrayRequest['description'],
            'status' => $arrayRequest['status'],
            'created_by' => $arrayRequest['created_by'],
            'company_id' => $arrayRequest['company_id'],
            'role_id' => $arrayRequest['role_id']
        ]);

        if (isset($arrayRequest['token'])) {
            $this->storeDocumentsByToken($item, $arrayRequest['token'], $item->company);
        }

        $item->save();

        return $item;
    }

    protected function updateItem($item, array $arrayRequest)
    {

        $item->update([
            'module' => $arrayRequest['module'],
            'type' => $arrayRequest['type'],
            'description' => $arrayRequest['description'],
            'status' => $arrayRequest['status'],
        ]);

        if (isset($arrayRequest['token'])) {
            $this->storeDocumentsByToken($item, $arrayRequest['token'], $item->company);
        }

        if (isset($arrayRequest['documents'])) {
            $this->deleteUnusedDocuments($item, $arrayRequest['documents']);
        }

        return $item;
    }

    protected function destroyItem($item)
    {
        if($item->documents){

            $ids = [];
            foreach($item->documents as $document){
                $ids[] = ['id'=>$document->id];
            }
            $this->deleteDocuments($item, $ids);
        }
        
        return $item->delete();
    }
}
