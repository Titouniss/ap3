<?php

namespace App\Http\Controllers\API;

use App\Models\Customer;

class CustomerController extends BaseApiController
{
    protected static $index_load = ['company:id,name'];
    protected static $index_append = null;
    protected static $show_load = ['company:id,name'];
    protected static $show_append = null;

    protected static $store_validation_array = [
        'name' => 'nullable',
        'lastname' => 'nullable',
        'siret' => 'nullable',
        'professional' => 'required',
        'company_id' => 'required'
    ];

    protected static $update_validation_array = [
        'name' => 'required',
        'lastname' => 'required',
        'siret' => 'required',
        'professional' => 'required',
        'company' => 'required'
    ];

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        parent::__construct(Customer::class);
    }

    protected function storeItem(array $arrayRequest)
    {
        return Customer::create([
            'name' => $arrayRequest['name'],
            'lastname' => $arrayRequest['lastname'],
            'siret' => $arrayRequest['siret'],
            'professional' => $arrayRequest['professional'],
            'company_id' => $arrayRequest['company_id']
        ]);
    }

    protected function updateItem($item, array $arrayRequest)
    {
        $item->update([
            'name' => $arrayRequest['name'],
            'lastname' => $arrayRequest['lastname'],
            'siret' => $arrayRequest['siret'],
            'professional' => $arrayRequest['professional'],
            'company_id' => $arrayRequest['company_id']
        ]);
        return $item;
    }
}
