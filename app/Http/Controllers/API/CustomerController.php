<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ApiException;
use App\Models\CompanyDetails;
use App\Models\Customer;

class CustomerController extends BaseApiController
{
    protected static $index_load = ['company:companies.id,name'];
    protected static $index_append = [
        'contact_firstname',
        'contact_lastname',
        'contact_email',
        'postal_code',
        'city',
    ];
    protected static $show_load = ['company:companies.id,name'];
    protected static $show_append = [
        'siret',
        'code',
        'type',
        'contact_firstname',
        'contact_lastname',
        'contact_function',
        'contact_tel1',
        'contact_tel2',
        'contact_email',
        'street_number',
        'street_name',
        'postal_code',
        'city',
        'country',
    ];

    protected static $store_validation_array = [
        'name' => 'nullable',
        'siret' => 'required',
        'code' => 'nullable',
        'type' => 'nullable',
        'contact_firstname' => 'required',
        'contact_lastname' => 'required',
        'contact_function' => 'required',
        'contact_email' => 'required|email',
        'contact_tel1' => 'nullable',
        'contact_tel2' => 'nullable',
        'street_number' => 'required',
        'street_name' => 'required',
        'postal_code' => 'required',
        'city' => 'required',
        'country' => 'required',
        'company_id' => 'required'
    ];

    protected static $update_validation_array = [
        'name' => 'required',
        'siret' => 'required',
        'code' => 'nullable',
        'type' => 'nullable',
        'contact_firstname' => 'required',
        'contact_lastname' => 'required',
        'contact_function' => 'required',
        'contact_email' => 'required|email',
        'contact_tel1' => 'nullable',
        'contact_tel2' => 'nullable',
        'street_number' => 'required',
        'street_name' => 'required',
        'postal_code' => 'required',
        'city' => 'required',
        'country' => 'required',
        'company_id' => 'required'
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
        $item = Customer::create([
            'name' => $arrayRequest['name'],
            'company_id' => $arrayRequest['company_id']
        ]);

        $item->details()->update([
            'siret' => $arrayRequest['siret'],
            'code' => $arrayRequest['code'] ?? null,
            'type' => $arrayRequest['type'] ?? null,
            'contact_firstname' => $arrayRequest['contact_firstname'],
            'contact_lastname' => $arrayRequest['contact_lastname'],
            'contact_function' => $arrayRequest['contact_function'],
            'contact_email' => $arrayRequest['contact_email'],
            'contact_tel1' => $arrayRequest['contact_tel1'],
            'contact_tel2' => $arrayRequest['contact_tel2'],
            'street_number' => $arrayRequest['street_number'],
            'street_name' => $arrayRequest['street_name'],
            'postal_code' => $arrayRequest['postal_code'],
            'city' => $arrayRequest['city'],
            'country' => $arrayRequest['country'],
        ]);

        return $item;
    }

    protected function updateItem($item, array $arrayRequest)
    {
        $item->update([
            'name' => $arrayRequest['name'],
            'company_id' => $arrayRequest['company_id']
        ]);

        if ($arrayRequest['siret'] != $item->details->siret && CompanyDetails::where('detailable_type', Company::class)->where('siret', $arrayRequest['siret'])->exists()) {
            throw new ApiException('Siret déjà utilisé par une autre sociéte.');
        }

        $item->details()->update([
            'siret' => $arrayRequest['siret'],
            'code' => $arrayRequest['code'] ?? null,
            'type' => $arrayRequest['type'] ?? null,
            'contact_firstname' => $arrayRequest['contact_firstname'],
            'contact_lastname' => $arrayRequest['contact_lastname'],
            'contact_function' => $arrayRequest['contact_function'],
            'contact_email' => $arrayRequest['contact_email'],
            'contact_tel1' => $arrayRequest['contact_tel1'],
            'contact_tel2' => $arrayRequest['contact_tel2'],
            'street_number' => $arrayRequest['street_number'],
            'street_name' => $arrayRequest['street_name'],
            'postal_code' => $arrayRequest['postal_code'],
            'city' => $arrayRequest['city'],
            'country' => $arrayRequest['country'],
        ]);

        return $item;
    }
}
