<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\User;
use App\Models\Customer;
use Exception;
use Illuminate\Support\Facades\Auth;
use Validator;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class CustomerController extends Controller
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
        if ($user->is_admin) {
            $customers = Customer::withTrashed()->get()->load('company');
        } else {
            $customers = Customer::withTrashed()->get()->load('company');
            // Add link to specific company ?
        }
        return response()->json(['success' => $customers], $this->successStatus);
    }

    /**
     * get single item api
     *
     * @param \App\Models\Customer $customer
     * @return \Illuminate\Http\Response
     */
    public function show(Customer $customer)
    {
        return response()->json(['success' => $customer], $this->successStatus);
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
            'professional' => 'required',
            'company' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }
        if ($user != null) {
            $arrayRequest['company_id'] = $arrayRequest['company']['id'];
            $item = Customer::create($arrayRequest)->load('company');
            return response()->json(['success' => $item], $this->successStatus);
        }
        return response()->json(['success' => 'notAuthentified'], 500);
    }

    /**
     * update item api
     *
     * @param \App\Models\Customer $customer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Customer $customer)
    {
        $arrayRequest = $request->all();

        $validator = Validator::make($arrayRequest, [
            'name' => 'required',
            'lastname' => 'required',
            'siret' => 'required',
            'professional' => 'required',
            'company' => 'required'
        ]);

        $customer->name = $arrayRequest['name'];
        $customer->lastname = $arrayRequest['lastname'];
        $customer->siret = $arrayRequest['siret'];
        $customer->professional = $arrayRequest['professional'];
        $customer->company_id = $arrayRequest['company']['id'];
        $customer->save();

        return response()->json(['success' => true, 'item' => $customer], $this->successStatus);
    }

    /**
     * Restore the specified resource in storage.
     *
     * @param \App\Models\Customer $customer
     * @return \Illuminate\Http\Response
     */
    public function restore(Customer $customer)
    {
        if (!$customer->restoreCascade()) {
            return response()->json(['success' => false, 'error' => 'Impossible de restaurer le client'], 400);
        }

        return response()->json(['success' => $customer], $this->successStatus);
    }

    /**
     * Archive the specified resource from storage.
     *
     * @param \App\Models\Customer $customer
     * @return \Illuminate\Http\Response
     */
    public function destroy(Customer $customer)
    {
        if (!$customer->deleteCascade()) {
            return response()->json(['success' => false, 'error' => 'Impossible d\'archiver le client'], 400);
        }

        return response()->json(['success' => $customer], $this->successStatus);
    }

    /**
     * forceDelete the specified resource from storage.
     *
     * @param \App\Models\Customer $customer
     * @return \Illuminate\Http\Response
     */
    public function forceDelete(Customer $customer)
    {
        if (!$customer->forceDelete()) {
            return response()->json(['success' => false, 'error' => 'Impossible de supprimer le client'], 400);
        }

        return response()->json(['success' => true], $this->successStatus);
    }
}
