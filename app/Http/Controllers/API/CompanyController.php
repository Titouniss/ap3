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
        if ($user->hasRole('superAdmin')) {
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
            'is_trial' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        if (Company::where('siret', $arrayRequest['siret'])->exists()) {
            return response()->json(['error' => 'Siret déjà utilisé par une autre sociéte'], 400);
        }

        $item = Company::create([
            'name' => $arrayRequest['name'],
            'siret' => $arrayRequest['siret'],
            'is_trial' => $arrayRequest['is_trial'],
        ]);
        if ($item->is_trial && !$item->expires_at) {
            $item->expires_at = Carbon::now()->addWeeks(4);
            $item->save();
        }

        if (isset($arrayRequest['subscription'])) {
            try {
                $this->createOrUpdateSubscription($arrayRequest['subscription'], $item);
            } catch (\Throwable $th) {
                return response()->json(['error' => $th->getMessage()], 400);
            }
        }

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
            'is_trial' => 'required'
        ]);

        $item = Company::find($id);
        $item->name = $arrayRequest['name'];
        $item->siret = $arrayRequest['siret'];
        if ($user->hasRole('superAdmin')) {
            $item->is_trial = $arrayRequest['is_trial'];
            if ($item->is_trial && !$item->expires_at) {
                $item->expires_at = Carbon::now()->addWeeks(4);
            }
        }
        $item->save();

        if (isset($arrayRequest['subscription'])) {
            try {
                $this->createOrUpdateSubscription($arrayRequest['subscription'], $item);
            } catch (\Throwable $th) {
                return response()->json(['error' => $th->getMessage()], 400);
            }
        }

        return response()->json(['success' => $item], $this->successStatus);
    }

    private function createOrUpdateSubscription(array $subscriptionArray, Company $item)
    {
        $validator = Validator::make($subscriptionArray, [
            'start_date' => 'required',
            'end_date' => 'required',
            'packages' => 'required',
            'state' => 'required'
        ]);
        if ($validator->fails()) {
            throw new Exception($validator->errors());
        }

        $subscription = isset($subscriptionArray['id'])
            ? Subscription::find($subscriptionArray['id'])
            : Subscription::create(['company_id' => $item->id]);

        try {
            $subscription->start_date = Carbon::createFromFormat('d/m/Y H:i:s', $subscriptionArray['start_date'] . ' 00:00:00');
            $subscription->end_date = Carbon::createFromFormat('d/m/Y H:i:s', $subscriptionArray['end_date'] . ' 23:59:59');
            $subscription->packages()->sync($subscriptionArray['packages']);
            $subscription->state = $subscriptionArray['state'];
        } catch (\Throwable $th) {
            throw $th;
        }

        $subscription->save();
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
