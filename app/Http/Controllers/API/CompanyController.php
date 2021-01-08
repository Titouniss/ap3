<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Company;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\WorkareasSkill;
use Carbon\Carbon;
use Exception;
use Validator;
use Illuminate\Database\Eloquent\SoftDeletes;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class CompanyController extends Controller
{
    use SoftDeletes;

    public $successStatus = 200;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        $items = [];
        if ($user->is_admin) {
            //$items = Company::all()->load('skills');
            $items = Company::withTrashed()->get()->load('skills');
        } else if ($user->company_id != null) {
            $items = Company::where('id', $user->company_id)->get()->load('skills');
        }

        return response()->json(['success' => $items], $this->successStatus);
    }

    /**
     * Show the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $item = Company::where('id', $id)->with('skills', 'subscriptions', 'subscriptions.packages')->first();
        return response()->json(['success' => $item], $this->successStatus);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $arrayRequest = $request->all();
        $validator = Validator::make($arrayRequest, [
            'name' => 'required',
            'siret' => 'required',
            'code' => 'required',
            'type' => 'required',
            'contact_firstname' => 'required',
            'contact_lastname' => 'required',
            'contact_email' => 'required|email',
            'street_number' => 'required',
            'street_name' => 'required',
            'postal_code' => 'required',
            'city' => 'required',
            'country' => 'required',
            'subscription' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        if (!isset($arrayRequest['contact_tel1']) && !isset($arrayRequest['contact_tel2'])) {
            return response()->json(['error' => 'Au moins un numéro de téléphone est obligatoire'], 400);
        }

        if (Company::where('siret', $arrayRequest['siret'])->exists()) {
            return response()->json(['error' => 'Siret déjà utilisé par une autre sociéte'], 400);
        }

        $item = Company::create([
            'name' => $arrayRequest['name'],
            'siret' => $arrayRequest['siret'],
            'code' => $arrayRequest['code'],
            'type' => $arrayRequest['type'],
            'contact_firstname' => $arrayRequest['contact_firstname'],
            'contact_lastname' => $arrayRequest['contact_lastname'],
            'contact_email' => $arrayRequest['contact_email'],
            'street_number' => $arrayRequest['street_number'],
            'street_name' => $arrayRequest['street_name'],
            'postal_code' => $arrayRequest['postal_code'],
            'city' => $arrayRequest['city'],
            'country' => $arrayRequest['country'],
        ]);
        if (isset($arrayRequest['contact_tel1'])) {
            $item->contact_tel1 = $arrayRequest['contact_tel1'];
        }
        if (isset($arrayRequest['contact_tel2'])) {
            $item->contact_tel2 = $arrayRequest['contact_tel2'];
        }
        $item->save();

        $subscriptionArray = $arrayRequest['subscription'];
        $validator = Validator::make($subscriptionArray, [
            'starts_at' => 'required',
            'ends_at' => 'required',
            'packages' => 'required',
            'is_trial' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $subscription = Subscription::create(['company_id' => $item->id]);

        try {
            $subscription->starts_at = Carbon::createFromFormat('d/m/Y H:i:s', $subscriptionArray['starts_at'] . ' 00:00:00');
            $subscription->ends_at = Carbon::createFromFormat('d/m/Y H:i:s', $subscriptionArray['ends_at'] . ' 23:59:59');
            $item->is_trial = $subscriptionArray['is_trial'];
            $subscription->packages()->sync($subscriptionArray['packages']);
            if ($subscription->starts_at->isFuture()) {
                $subscription->state = 'pending';
            } else if ($subscription->ends_at->isFuture()) {
                $subscription->state = 'active';
            } else {
                $subscription->state = 'inactive';
            }
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 400);
        }

        $subscription->save();

        return response()->json(['success' => $item], $this->successStatus);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $arrayRequest = $request->all();

        $validator = Validator::make($arrayRequest, [
            'name' => 'required',
            'siret' => 'required',
            'code' => 'required',
            'type' => 'required',
            'contact_firstname' => 'required',
            'contact_lastname' => 'required',
            'contact_email' => 'required|email',
            'street_number' => 'required',
            'street_name' => 'required',
            'postal_code' => 'required',
            'city' => 'required',
            'country' => 'required',
        ]);

        $item = Company::find($id)->update([
            'name' => $arrayRequest['name'],
            'siret' => $arrayRequest['siret'],
            'code' => $arrayRequest['code'],
            'type' => $arrayRequest['type'],
            'contact_firstname' => $arrayRequest['contact_firstname'],
            'contact_lastname' => $arrayRequest['contact_lastname'],
            'contact_email' => $arrayRequest['contact_email'],
            'street_number' => $arrayRequest['street_number'],
            'street_name' => $arrayRequest['street_name'],
            'postal_code' => $arrayRequest['postal_code'],
            'city' => $arrayRequest['city'],
            'country' => $arrayRequest['country'],
        ]);

        return response()->json(['success' => $item], $this->successStatus);
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
            $item = Company::withTrashed()->findOrFail($id);
            $success = $item->restoreCascade();

            if ($success) {
                return response()->json(['success' => $item], $this->successStatus);
            } else {
                throw new Exception('Impossible de restaurer la société');
            }
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'error' => $th->getMessage()], 400);
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
            $item = Company::findOrFail($id);
            $success = $item->deleteCascade();

            if (!$success) {
                throw new Exception('Impossible d\'archiver la société');
            }

            return response()->json(['success' => $item], $this->successStatus);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'error' => $th->getMessage()], 400);
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
        $controllerLog = new Logger('company');
        $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
        $controllerLog->info('company', ['forceDelete']);

        try {
            $item = Company::withTrashed()->findOrFail($id);
            $success = $item->forceDelete();

            if (!$success) {
                throw new Exception('Impossible de supprimer la société');
            }

            return response()->json(['success' => true], $this->successStatus);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'error' => $th->getMessage()], 400);
        }
    }
}
