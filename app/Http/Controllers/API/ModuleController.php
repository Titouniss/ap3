<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ApiModule;
use App\Models\BaseModule;
use App\Models\Company;
use App\Models\SqlModule;
use Exception;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use PDO;

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
     * @return \Illuminate\Http\Response 
     */
    public function show(BaseModule $item)
    {
        if ($item) {
            $modulable = $item->modulable;
            $connection = [];
            if (get_class($modulable) === SqlModule::class) {
                $connection = [
                    'id' => $modulable->id,
                    'driver' => $modulable->driver ?? "",
                    'host' => $modulable->host ?? "",
                    'port' => $modulable->port ?? "",
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
            $module = [
                'base' => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'type' => $item->type,
                    'company' => $item->company,
                    'dataTypes' => $item->moduleDataTypes
                ],
                'connection' => $connection
            ];
            return response()->json(['success' => $module], $this->successStatus);
        } else {
            return response()->json(['error' => "Not found"], 404);
        }
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
            'type' => 'required'
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
        ]);
        return response()->json(['success' => $module->load('company')], $this->successStatus);
    }

    /** 
     * update item api 
     * 
     * @return \Illuminate\Http\Response 
     */
    public function update(Request $request, BaseModule $item)
    {
        $arrayRequest = $request->all();
        $validator = Validator::make($arrayRequest, [
            'name' => 'required',
            'company_id' => 'required',
            'modulable_id' => 'required',
            'type' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        if (!Company::where('id', $arrayRequest['company_id'])->exists()) {
            return response()->json(['error' => 'Société inconnue'], 400);
        }
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
                    return response()->json(['error' => "Type inconnu"], 400);
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
        ]);
        return response()->json(['success' => $item->load('company')], $this->successStatus);
    }

    /** 
     * update item api 
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
                    'username' => 'required',
                    'password' => 'nullable',
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
                'port' => $arrayRequest['port'],
                'username' => $arrayRequest['username'],
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
     * delete item api 
     * 
     * @return \Illuminate\Http\Response 
     */
    public function destroy(BaseModule $item)
    {
        try {
            $success = $item->modulable->delete() && $item->delete();

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
}
