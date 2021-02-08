<?php

namespace App\Http\Controllers\API;

use App\Models\Workarea;
use App\Traits\StoresDocuments;

class WorkareaController extends BaseApiController
{
    use StoresDocuments;

    protected static $index_load = ['company:id,name', 'skills:id,name,company_id', 'documents'];
    protected static $index_append = null;
    protected static $show_load = ['company:id,name', 'skills:id,name,company_id', 'documents'];
    protected static $show_append = null;

    protected static $store_validation_array = [
        'name' => 'required',
        'company_id' => 'required',
        'max_users' => 'required',
        'skills' => 'required|array',
        'token' => 'nullable'
    ];

    protected static $update_validation_array = [
        'name' => 'required',
        'company_id' => 'required',
        'max_users' => 'nullable',
        'skills' => 'required|array',
        'token' => 'nullable',
        'documents' => 'nullable|array'
    ];

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        parent::__construct(Workarea::class);
    }

    protected function storeItem(array $arrayRequest)
    {
        $item = Workarea::create([
            'name' => $arrayRequest['name'],
            'company_id' => $arrayRequest['company_id'],
            'max_users' => $arrayRequest['max_users'],
        ]);

        $item->skills()->sync($arrayRequest['skills']);

        if (isset($arrayRequest['token'])) {
            $this->storeDocumentsByToken($item, $arrayRequest['token'], $item->company);
        }

        return $item;
    }

    protected function updateItem($item, array $arrayRequest)
    {
        $item->update([
            'name' => $arrayRequest['name'],
            'company_id' => $arrayRequest['company_id'],
            'max_users' => $arrayRequest['max_users']
        ]);

        $item->skills()->sync($arrayRequest['skills']);

        if (isset($arrayRequest['token'])) {
            $this->storeDocumentsByToken($item, $arrayRequest['token'], $item->company);
        }

        if (isset($arrayRequest['documents'])) {
            $this->deleteUnusedDocuments($item, $arrayRequest['documents']);
        }

        return $item;
    }
}
