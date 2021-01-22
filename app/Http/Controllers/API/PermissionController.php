<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Auth;

class PermissionController extends BaseApiController
{
    protected static $index_load = null;
    protected static $index_append = null;
    protected static $show_load = null;
    protected static $show_append = null;
    protected static $cascade = false;

    protected static $store_validation_array = [
        'name' => 'required',
        'name_fr' => 'required',
        'is_public' => 'required'
    ];

    protected static $update_validation_array = [
        'name' => 'required',
        'name_fr' => 'required',
        'is_public' => 'required'
    ];

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        parent::__construct(Permission::class);
    }

    protected function filterIndexQuery($query, Request $request)
    {
        $user = Auth::user();
        if (!$user->is_admin) {
            $query->where('is_public', true);
        }
        if (!$request->has('order_by')) {
            $query->orderBy('name', 'desc');
        }
    }

    protected function storeItem(array $arrayRequest)
    {
        $user = Auth::user();

        $item = Permission::create([
            'name' => $arrayRequest['name'],
            'name_fr' => $arrayRequest['name_fr'],
            'is_public' => $arrayRequest['is_public'] && $user->is_admin,
            'guard_name' => 'web'
        ]);

        return $item;
    }

    protected function updateItem($item, array $arrayRequest)
    {
        $user = Auth::user();

        $item->update([
            'name' => $arrayRequest['name'],
            'name_fr' => $arrayRequest['name_fr'],
            'is_public' => $arrayRequest['is_public'] && $user->is_admin,
            'guard_name' => 'web'
        ]);

        return $item;
    }
}
