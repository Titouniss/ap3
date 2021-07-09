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
        'name' => 'required',
        'siret' => 'nullable',
        'code' => 'nullable',
        'type' => 'nullable',
        'contact_firstname' => 'nullable',
        'contact_lastname' => 'nullable',
        'contact_function' => 'nullable',
        'contact_email' => 'required|email',
        'contact_tel1' => 'nullable',
        'contact_tel2' => 'nullable',
        'street_number' => 'nullable',
        'street_name' => 'nullable',
        'postal_code' => 'nullable',
        'city' => 'nullable',
        'country' => 'nullable',
        'company_id' => 'required'
    ];

    protected static $update_validation_array = [
        'name' => 'required',
        'siret' => 'nullable',
        'code' => 'nullable',
        'type' => 'nullable',
        'contact_firstname' => 'nullable',
        'contact_lastname' => 'nullable',
        'contact_function' => 'nullable',
        'contact_email' => 'required|email',
        'contact_tel1' => 'nullable',
        'contact_tel2' => 'nullable',
        'street_number' => 'nullable',
        'street_name' => 'nullable',
        'postal_code' => 'nullable',
        'city' => 'nullable',
        'country' => 'nullable',
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
            'siret' => $arrayRequest['siret'] ?? null,
            'code' => $arrayRequest['code'] ?? null,
            'type' => $arrayRequest['type'] ?? null,
            'contact_firstname' => $arrayRequest['contact_firstname'] ?? null,
            'contact_lastname' => $arrayRequest['contact_lastname'] ?? null,
            'contact_function' => $arrayRequest['contact_function'] ?? null,
            'contact_email' => $arrayRequest['contact_email'],
            'contact_tel1' => $arrayRequest['contact_tel1'],
            'contact_tel2' => $arrayRequest['contact_tel2'] ?? null,
            'street_number' => $arrayRequest['street_number'] ?? null,
            'street_name' => $arrayRequest['street_name'] ?? null,
            'postal_code' => $arrayRequest['postal_code'] ?? null,
            'city' => $arrayRequest['city'] ?? null,
            'country' => $arrayRequest['country'] ?? null,
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
            'siret' => $arrayRequest['siret'] ?? null,
            'code' => $arrayRequest['code'] ?? null,
            'type' => $arrayRequest['type'] ?? null,
            'contact_firstname' => $arrayRequest['contact_firstname'] ?? null,
            'contact_lastname' => $arrayRequest['contact_lastname'] ?? null,
            'contact_function' => $arrayRequest['contact_function'] ?? null,
            'contact_email' => $arrayRequest['contact_email'],
            'contact_tel1' => $arrayRequest['contact_tel1'],
            'contact_tel2' => $arrayRequest['contact_tel2'] ?? null,
            'street_number' => $arrayRequest['street_number'] ?? null,
            'street_name' => $arrayRequest['street_name'] ?? null,
            'postal_code' => $arrayRequest['postal_code'] ?? null,
            'city' => $arrayRequest['city'] ?? null,
            'country' => $arrayRequest['country'] ?? null,
        ]);

        return $item;
    }
}
