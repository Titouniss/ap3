<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ApiModule;
use App\Models\BaseModule;
use App\Models\Company;
use App\Models\ModuleDataRow;
use App\Models\ModuleDataType;
use App\Models\SqlModule;
use Exception;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class ModuleController extends Controller
{
    public $successStatus = 200;

    /**
     * list of items api
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(['success' => BaseModule::all()->load('company')], $this->successStatus);
    }

    /**
     * get single item api
     *
     * @param \App\Models\BaseModule $module
     * @return \Illuminate\Http\Response
     */
    public function show(BaseModule $module)
    {
        $modulable = $module->modulable;
        $connection = [];
        if (get_class($modulable) === SqlModule::class) {
            $connection = [
                'id' => $modulable->id,
                'driver' => $modulable->driver ?? "",
                'host' => $modulable->host ?? "",
                'port' => $modulable->port ?? "",
                'charset' => $modulable->charset ?? "",
                'database' => $modulable->database ?? "",
                'username' => $modulable->username ?? "",
                'has_password' => $modulable->password !== null,
            ];
        } else {
            $connection = [
                'id' => $modulable->id,
                'url' => $modulable->url ?? "",
                'auth_headers' => $modulable->auth_headers ?? "",
            ];
        }
        $json_module = [
            'base' => [
                'id' => $module->id,
                'name' => $module->name,
                'type' => $module->type,
                'company' => $module->company,
                'module_data_types' => $module->moduleDataTypes->load('moduleDataRows')
            ],
            'connection' => $connection
        ];
        return response()->json(['success' => $json_module], $this->successStatus);
    }

    /**
     * create item api
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $arrayRequest = $request->all();
        $validator = Validator::make($arrayRequest, [
            'name' => 'required',
            'company_id' => 'required',
            'type' => 'required',
            'is_active' => 'nullable'
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        if (!Company::where('id', $arrayRequest['company_id'])->exists()) {
            return response()->json(['error' => 'Société inconnue'], 400);
        }
        $modulable = null;
        switch ($arrayRequest['type']) {
            case 'sql':
                $modulable = SqlModule::create([]);
                break;
            case 'api':
                $modulable = ApiModule::create([]);
                break;

            default:
                return response()->json(['error' => "Type inconnu"], 400);
        }
        $module = BaseModule::create([
            'name' => $arrayRequest['name'],
            'company_id' => $arrayRequest['company_id'],
            'modulable_id' => $modulable->id,
            'modulable_type' => get_class($modulable),
            'is_active' => isset($arrayRequest['is_active']) ? $arrayRequest['is_active'] : true
        ]);
        return response()->json(['success' => $module->load('company')], $this->successStatus);
    }

    /**
     * update item api
     *
     * @param \App\Models\BaseModule $module
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, BaseModule $module)
    {
        $arrayRequest = $request->all();
        $validator = Validator::make($arrayRequest, [
            'name' => 'required',
            'company_id' => 'required',
            'modulable_id' => 'required',
            'type' => 'required',
            'is_active' => 'nullable'
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        if (!Company::where('id', $arrayRequest['company_id'])->exists()) {
            return response()->json(['error' => 'Société inconnue'], 400);
        }
        if ($module->type !== $arrayRequest['type']) {
            $modulable = null;
            switch ($arrayRequest['type']) {
                case 'sql':
                    $modulable = SqlModule::create([]);
                    break;
                case 'api':
                    $modulable = ApiModule::create([]);
                    break;

                default:
                    return response()->json(['error' => "Type inconnu"], 400);
            }
            $module->modulable->delete();
            $module->update([
                'modulable_id' => $modulable->id,
                'modulable_type' => get_class($modulable),
            ]);
        }
        $module->update([
            'name' => $arrayRequest['name'],
            'company_id' => $arrayRequest['company_id'],
            'is_active' => isset($arrayRequest['is_active']) ? $arrayRequest['is_active'] : true
        ]);
        return response()->json(['success' => $module->load('company')], $this->successStatus);
    }

    /**
     * update module item api
     *
     * @return \Illuminate\Http\Response
     */
    public function updateModule(Request $request, $id)
    {
        $arrayRequest = $request->all();
        $validator = Validator::make($arrayRequest, [
            'type' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $modulable = null;
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
                return response()->json(['error' => "Type inconnu"], 400);
        }
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        if (!$modulable) {
            return response()->json(['error' => 'Module inconnu'], 404);
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
        return response()->json(['success' => true], $this->successStatus);
    }

    /**
     * update item module data types api
     *
     * @param \App\Models\BaseModule $module
     * @return \Illuminate\Http\Response
     */
    public function updateModuleDataTypes(Request $request, BaseModule $module)
    {
        $validator = Validator::make($request->all(), [
            'module_data_types' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $module_data_types = $request->all()['module_data_types'];
        foreach ($module_data_types as $mdt) {
            $validator = Validator::make($mdt, [
                'data_type_id' => 'required',
                'source' => 'required',
                'module_data_rows' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }
            foreach ($mdt['module_data_rows'] as $mdr) {
                $validator = Validator::make($mdr, [
                    'data_row_id' => 'required',
                    'source' => 'nullable',
                    'default_value' => 'nullable',
                    'details' => 'nullable',
                ]);
                if ($validator->fails()) {
                    return response()->json(['error' => $validator->errors()], 400);
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
        return response()->json(['success' => ['module_data_types' => ModuleDataType::where('module_id', $module->id)->with('moduleDataRows')->get()]], $this->successStatus);
    }

    /**
     * delete item api
     *
     * @param \App\Models\BaseModule $module
     * @return \Illuminate\Http\Response
     */
    public function destroy(BaseModule $module)
    {
        try {
            $success = $module->modulable->delete() && $module->delete();

            if (!$success) {
                throw new Exception('Impossible de supprimer le module');
            }

            return response()->json(['success' => true], $this->successStatus);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'error' => $th->getMessage()], 400);
        }
    }

    /**
     * test db connection api
     *
     * @return \Illuminate\Http\Response
     */
    public function testConnection(Request $request)
    {
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
            return response()->json(['error' => $validator->errors()], 400);
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

        try {
            if (!$arrayRequest['password']) {
                $arrayRequest['password'] = SqlModule::findOrFail($arrayRequest['id'])->password;
            }

            Config::set('database.connections.test', $arrayRequest);
            DB::purge('test');
            DB::connection('test')->getPdo();
            return response()->json(['success' => true], $this->successStatus);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'error' => $th->getMessage()], 400);
        }
    }

    /**
     * sync item api
     *
     * @return \Illuminate\Http\Response
     */
    public function sync(BaseModule $item)
    {
        if (!$item) {
            return response()->json(['success' => false, 'error' => 'Not found'], 404);
        }

        if (!$item->sync()) {
            return response()->json(['success' => false, 'error' => 'Synchronisation impossible'], 400);
        }

        return response()->json(['success' => $item->load('moduleDataTypes', 'moduleDataTypes.dataType')], $this->successStatus);
    }
}
