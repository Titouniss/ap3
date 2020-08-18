<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\User;
use App\Models\Customers;
use Exception;
use Illuminate\Support\Facades\Auth;
use Validator;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class CustomersController extends Controller
{
    public $successStatus = 200;

    /** 
     * list of items api 
     * 
     * @return \Illuminate\Http\Response 
     */
    public function index()
    {
        $user = Auth::user();
        $customers = [];
        if ($user->hasRole('superAdmin')) {
            $customers = Customers::withTrashed()->get();
        } else {
            $customers = Customers::all();
            // Add link to specific company ?
        }
        return response()->json(['success' => $customers], $this->successStatus);
    }

    /** 
     * get single item api 
     * 
     * @return \Illuminate\Http\Response 
     */
    public function show($id)
    {
        $item = Customers::where('id', $id)->first();
        return response()->json(['success' => $item], $this->successStatus);
    }

    /** 
     * create item api 
     * 
     * @return \Illuminate\Http\Response 
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $arrayRequest = $request->all();
        $controllerLog = new Logger('customer');
        $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
        $controllerLog->info('customer', [$arrayRequest]);
        $validator = Validator::make($arrayRequest, [
            'name' => 'nullable',
            'lastname' => 'nullable',
            'siret' => 'nullable',
            'professional' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }
        if ($user != null) {
            $item = Customers::create($arrayRequest);
            return response()->json(['success' => $item], $this->successStatus);
        }
        return response()->json(['success' => 'notAuthentified'], 500);
    }

    /** 
     * update item api 
     * 
     * @return \Illuminate\Http\Response 
     */
    public function update(Request $request, $id)
    {
        $arrayRequest = $request->all();

        $validator = Validator::make($arrayRequest, [
            'name' => 'required',
            'lastname' => 'required',
            'siret' => 'required',
            'professional' => 'required'
        ]);
        $customer = Customers::where('id', $id)->first();
        if ($customer != null) {
            $customer->name = $arrayRequest['name'];
            $customer->lastname = $arrayRequest['lastname'];
            $customer->siret = $arrayRequest['siret'];
            $customer->professional = $arrayRequest['professional'];
            $customer->save();
        }
        return response()->json(['success' => true, 'item' => $customer], $this->successStatus);
    }

    /**
     * Restore the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function restore($id)
    {
        try {
            $item = Customers::withTrashed()->findOrFail($id);
            $success = $item->restoreCascade();

            if ($success) {
                return response()->json(['success' => $item], $this->successStatus);
            } else {
                throw new Exception('Impossible de restaurer le client');
            }
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'error' => $th->getMessage()], 404);
        }
    }

    /** 
     * Archive the specified resource from storage. 
     * 
     * @param  int  $id
     * @return \Illuminate\Http\Response 
     */
    public function destroy($id)
    {
        try {
            $item = Customers::findOrFail($id);
            $success = $item->deleteCascade();

            if (!$success) {
                throw new Exception('Impossible d\'archiver le client');
            }

            return response()->json(['success' => $item], $this->successStatus);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'error' => $th->getMessage()], 404);
        }
    }

    /**
     * forceDelete the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function forceDelete($id)
    {
        try {
            $item = Customers::withTrashed()->findOrFail($id);
            $success = $item->forceDelete();

            if (!$success) {
                throw new Exception('Impossible de supprimer le client');
            }

            return response()->json(['success' => true], $this->successStatus);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'error' => $th->getMessage()], 404);
        }
    }
}
