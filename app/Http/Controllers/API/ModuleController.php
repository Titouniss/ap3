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
        // $obx = SqlModule::find(1);
        // Config::set('database.connections.' . $obx->module->name, $obx->connectionData());
        // DB::purge($obx->module->name);
        // return response()->json(['success' => DB::connection($obx->module->name)->table($obx->module->moduleDataTypes->find(1)->source)->get()], $this->successStatus);

        return response()->json(['success' => BaseModule::all()->load('company')], $this->successStatus);
    }

    /** 
     * get single item api 
     * 
     * @return \Illuminate\Http\Response 
     */
    public function show(BaseModule $item)
    {
        return response()->json(['success' => $item->load('company')], $this->successStatus);
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
        $module = null;
        switch ($arrayRequest['type']) {
            case 'sql':
                $module = SqlModule::create([]);
                break;
            case 'api':
                $module = ApiModule::create([]);
                break;

            default:
                return response()->json(['error' => "Type inconnu"], 400);
        }
        $baseModule = BaseModule::create([
            'name' => $arrayRequest['name'],
            'company_id' => $arrayRequest['company_id'],
            'modulable_id' => $module->id,
            'modulable_type' => get_class($module),
        ]);
        return response()->json(['success' => $baseModule->load('company')], $this->successStatus);
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
            $module = null;
            switch ($arrayRequest['type']) {
                case 'sql':
                    $module = SqlModule::create([]);
                    break;
                case 'api':
                    $module = ApiModule::create([]);
                    break;

                default:
                    return response()->json(['error' => "Type inconnu"], 400);
            }
            $item->modulable->delete();
            $item->update([
                'modulable_id' => $module->id,
                'modulable_type' => get_class($module),
            ]);
        }
        $item->update([
            'name' => $arrayRequest['name'],
            'company_id' => $arrayRequest['company_id'],
        ]);
        return response()->json(['success' => $item->load('company')], $this->successStatus);
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
}
