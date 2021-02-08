<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\ModelHasDocuments;
use Illuminate\Http\Request;
use App\Models\Workarea;
use App\Models\WorkareasSkill;
use App\Traits\StoresDocuments;
use Illuminate\Support\Facades\Auth;
use Validator;

class WorkareaController extends BaseApiController
{
    use StoresDocuments;

    protected static $index_load = ['company:id,name', 'skills:id,name,company_id', 'documents'];
    protected static $index_append = null;
    protected static $show_load = ['company:id,name', 'skills:id,name,company_id', 'documents'];
    protected static $show_append = null;
    protected static $cascade = false;

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
        'documents' => 'required|array'
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
            $documents = $item->documents()->whereNotIn('id', array_map(function ($doc) {
                return $doc['id'];
            }, $arrayRequest['documents']))->get();

            foreach ($documents as $doc) {
                $doc->deleteFile();
            }
        }

        return $item;
    }
}
