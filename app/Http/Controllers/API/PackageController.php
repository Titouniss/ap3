<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ApiException;
use App\Models\Package;
use Illuminate\Http\Request;

class PackageController extends BaseApiController
{
    protected static $index_load = null;
    protected static $index_append = null;
    protected static $show_load = null;
    protected static $show_append = null;

    protected static $store_validation_array = [
        'name'          => 'required',
        'display_name'  => 'required',
    ];

    protected static $update_validation_array = [
        'name'          => 'required',
        'display_name'  => 'required',
    ];

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        parent::__construct(Package::class);
    }

    protected function filterIndexQuery(Request $request, $query)
    {
        if (!$request->has('order_by')) {
            $query->orderBy('display_name', 'desc');
        }
    }

    protected function storeItem(array $arrayRequest)
    {
        throw new ApiException('Method not implemented', 500);
    }

    protected function updateItem($item, array $arrayRequest)
    {
        throw new ApiException('Method not implemented', 500);
    }
}
