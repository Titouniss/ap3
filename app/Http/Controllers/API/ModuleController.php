<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ApiModule;
use App\Models\BaseModule;
use App\Models\Company;
use App\Models\DataType;
use App\Models\ModuleDataRow;
use App\Models\ModuleDataType;
use App\Models\SqlModule;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ModuleController extends BaseApiController
{
    protected static $company_id_field = 'company_id';
    protected static $index_load = ['company:id,name'];
    protected static $index_append = null;
    protected static $show_load = ['company:id,name', 'moduleDataTypes', 'moduleDataTypes.moduleDataRows'];
    protected static $show_append = ['connection'];

    protected static $store_validation_array = [
        'name' => 'required',
        'company_id' => 'required',
        'type' => 'required',
        'is_active' => 'nullable'
    ];

    protected static $update_validation_array = [
        'name' => 'required',
        'company_id' => 'required',
        'modulable_id' => 'required',
        'type' => 'required',
        'is_active' => 'nullable'
    ];

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        parent::__construct(BaseModule::class);
    }

    protected function storeItem(array $arrayRequest)
    {
        $modulable = null;
        switch ($arrayRequest['type']) {
            case 'sql':
                $modulable = SqlModule::create([]);
                break;
            case 'api':
                $modulable = ApiModule::create([]);
                break;

            default:
                throw new Exception("Type de module inconnu.");
        }

        return BaseModule::create([
            'name' => $arrayRequest['name'],
            'company_id' => $arrayRequest['company_id'],
            'modulable_id' => $modulable->id,
            'modulable_type' => get_class($modulable),
            'is_active' => $arrayRequest['is_active'] ?? true
        ]);
    }

    protected function updateItem($item, array $arrayRequest)
    {
        if ($item->type !== $arrayRequest['type']) {
            $modulable = null;
            switch ($arrayRequest['type']) {
                case 'sql':
                    $modulable = SqlModule::create([]);
                    break;
                case 'api':
                    $modulable = ApiModule::create([]);
                    break;

                default:
                    return response()->json(['error' => "Type de module inconnu."], 400);
            }
            $item->modulable->delete();
            $item->update([
                'modulable_id' => $modulable->id,
                'modulable_type' => get_class($modulable),
            ]);
        }
        $item->update([
            'name' => $arrayRequest['name'],
            'company_id' => $arrayRequest['company_id'],
            'is_active' => $arrayRequest['is_active'] ?? true
        ]);

        return $item;
    }

    protected function destroyItem($item)
    {
        return $item->modulable->delete() && $item->delete();
    }

    /**
     * Display a listing of the data type resource.
     */
    public function dataTypes()
    {
        if ($error = $this->permissionErrors('read')) {
            return $error;
        }

        $items = DataType::orderBy('display_name_plurial')->with('dataRows')->get();

        return $this->successResponse($items);
    }

    /**
     * Updates the specified module's connection data.
     */
    public function updateConnection(Request $request, int $id)
    {
        $modulable = null;

        DB::beginTransaction();
        try {
            $arrayRequest = $request->all();
            $validator = Validator::make($arrayRequest, [
                'type' => 'required',
            ]);
            if ($validator->fails()) {
                throw new Exception($validator->errors());
            }
            switch ($arrayRequest['type']) {
                case 'sql':
                    $validator = Validator::make($arrayRequest, [
                        'driver' => 'required',
                        'host' => 'required',
                        'port' => 'nullable',
                        'charset' => 'required',
                        'database' => 'required',
                        'username' => 'required',
                        'password' => 'nullable',
                        'c_password' => 'nullable|same:password',
                    ]);
                    $modulable = SqlModule::find($id);
                    break;
                case 'api':
                    $validator = Validator::make($arrayRequest, [
                        'url' => 'required',
                        'auth_headers' => 'nullable',
                    ]);
                    $modulable = ApiModule::find($id);
                    break;

                default:
                    throw new Exception("Type de module inconnu.");
            }
            if ($validator->fails()) {
                throw new Exception($validator->errors());
            }

            if ($error = $this->itemErrors($modulable, 'edit')) {
                return $error;
            }

            if ($arrayRequest['type'] === 'sql') {
                $modulable->update([
                    'driver' => $arrayRequest['driver'],
                    'host' => $arrayRequest['host'],
                    'charset' => $arrayRequest['charset'],
                    'database' => $arrayRequest['database'],
                    'username' => $arrayRequest['username'],
                ]);

                $port = $arrayRequest['port'];
                if (!$port) {
                    switch ($arrayRequest['driver']) {
                        case 'pgsql':
                            $port = '5432';
                            break;
                        case 'sqlsrv':
                            $port = '1433';
                            break;
                        case 'mysql':
                            $port = '3306';
                            break;
                        default: // SQLite
                            $port = null;
                            break;
                    }
                }
                $modulable->update([
                    'port' => $port
                ]);

                if ($arrayRequest['password']) {
                    $modulable->update([
                        'password' => $arrayRequest['password']
                    ]);
                }
            } else {
                $modulable->update([
                    'url' => $arrayRequest['url'],
                    'auth_headers' => $arrayRequest['auth_headers'],
                ]);
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->errorResponse($th->getMessage());
        }
        DB::commit();

        return $this->successResponse($modulable->module->load(static::$show_load)->append(static::$show_append), "Mise à jour terminée avec succès.");
    }

    /**
     * Updates the specified item module data types.
     */
    public function updateDataTypes(Request $request, int $id)
    {
        $module = BaseModule::find($id);
        if ($error = $this->itemErrors($module, 'edit')) {
            return $error;
        }

        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'module_data_types' => 'required',
            ]);
            if ($validator->fails()) {
                throw new Exception($validator->errors());
            }

            $module_data_types = $request->all()['module_data_types'];
            foreach ($module_data_types as $mdt) {
                $validator = Validator::make($mdt, [
                    'data_type_id' => 'required',
                    'source' => 'required',
                    'module_data_rows' => 'required',
                ]);
                if ($validator->fails()) {
                    throw new Exception($validator->errors());
                }
                foreach ($mdt['module_data_rows'] as $mdr) {
                    $validator = Validator::make($mdr, [
                        'data_row_id' => 'required',
                        'source' => 'nullable',
                        'default_value' => 'nullable',
                        'details' => 'nullable',
                    ]);
                    if ($validator->fails()) {
                        throw new Exception($validator->errors());
                    }
                }
            }

            ModuleDataType::where('module_id', $module->id)->delete();
            foreach ($module_data_types as $mdt) {
                $moduleDataType = ModuleDataType::create([
                    'module_id' => $module->id,
                    'data_type_id' => $mdt['data_type_id'],
                    'source' => $mdt['source']
                ]);
                foreach ($mdt['module_data_rows'] as $mdr) {
                    ModuleDataRow::create([
                        'module_data_type_id' => $moduleDataType->id,
                        'data_row_id' => $mdr['data_row_id'],
                        'source' => $mdr['source'],
                        'default_value' => $mdr['default_value'],
                        'details' => $mdr['details'],
                    ]);
                }
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->errorResponse($th->getMessage());
        }
        DB::commit();

        return $this->successResponse($module->load(static::$show_load)->append(static::$show_append), "Mise à jour terminée avec succès.");
    }

    /**
     * Tests database connection.
     */
    public function testConnection(Request $request)
    {
        try {
            $arrayRequest = $request->all();
            $validator = Validator::make($arrayRequest, [
                'id' => 'required',
                'driver' => 'required',
                'host' => 'required',
                'port' => 'nullable',
                'charset' => 'required',
                'database' => 'required',
                'username' => 'required',
                'password' => 'nullable',
            ]);
            if ($validator->fails()) {
                throw new Exception($validator->errors());
            }

            if ($arrayRequest['driver'] !== 'sqlite') {
                $port = $arrayRequest['port'];
                if (!$port) {
                    switch ($arrayRequest['driver']) {
                        case 'pgsql':
                            $port = '5432';
                            break;
                        case 'sqlsrv':
                            $port = '1433';
                            break;
                        default: // MySQL
                            $port = '3306';
                            break;
                    }
                }
                $arrayRequest['port'] = $port;
            }

            if (!$arrayRequest['password']) {
                if ($module = SqlModule::find($arrayRequest['id'])) {
                    $arrayRequest['password'] = $module->password;
                } else {
                    throw new Exception("Aucun mot de passe enregistré pour la connexion.");
                }
            }

            try {
                Config::set('database.connections.test', $arrayRequest);
                DB::purge('test');
                DB::connection('test')->getPdo();
            } catch (\Throwable $th) {
                throw new Exception("Connexion impossible.");
            }
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage());
        }

        return $this->successResponse(true, "Connexion réussie.");
    }

    /**
     * sync item api
     */
    public function sync(int $id)
    {
        $item = BaseModule::find($id);
        if (!$item) {
            return $this->notFoundResponse();
        }

        if (!$item->sync()) {
            return $this->errorResponse("Synchronisation impossible.", static::$response_codes['error_server']);
        }

        return $this->successResponse($item->load(static::$show_load)->append(static::$show_append), "Synchronisation réussie.");
    }
}
