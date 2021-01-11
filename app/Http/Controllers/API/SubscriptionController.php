<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Package;
use App\Models\Subscription;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{
    public $successStatus = 200;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $items = Subscription::withTrashed()->with('company')->get();

        return response()->json(['success' => $items->sortBy('ends_at')->sortBy('starts_at')->sortBy('status_order')->values()], $this->successStatus);
    }

    /**
     * Display a listing of the package resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function packages()
    {
        return response()->json(['success' => Package::all()], $this->successStatus);
    }

    /**
     * Display a listing of the resource based on the company.
     *
     * @param  Company  $item
     * @return \Illuminate\Http\Response
     */
    public function getByCompany(Company $item)
    {
        if (!$item) {
            return response()->json(['error' => 'Société inconnue'], 404);
        }

        $items = Subscription::withTrashed()->where('company_id', $item->id)->with('company', 'packages')->get();

        return response()->json(['success' => $items->sortBy('ends_at')->sortBy('starts_at')->sortBy('status_order')->values()], $this->successStatus);
    }

    /**
     * Show the specified resource.
     *
     * @param  Subscription  $item
     * @return \Illuminate\Http\Response
     */
    public function show($item)
    {
        if (!$item) {
            return response()->json(['error' => 'Abonnement inconnu'], 404);
        }

        return response()->json(['success' => $item->load('company', 'packages')], $this->successStatus);
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
            'company_id' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $item = new Subscription;
        $item->company_id = $arrayRequest['company_id'];
        return $this->createOrUpdateSubscription($arrayRequest, $item);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Subscription  $item
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Subscription $item)
    {
        return $this->createOrUpdateSubscription($request->all(), $item);
    }

    /**
     * Updates a subscription with new values
     *
     * @param  array  $subscriptionArray
     * @param  Subscription  $item
     * @return \Illuminate\Http\Response
     */
    private function createOrUpdateSubscription(array $subscriptionArray, Subscription $item)
    {
        if (!$item) {
            return response()->json(['error' => 'Abonnement inconnu'], 404);
        }

        $validator = Validator::make($subscriptionArray, [
            'starts_at' => 'required',
            'ends_at' => 'required',
            'packages' => 'required',
            'is_trial' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        try {
            $item->starts_at = Carbon::createFromFormat('Y-m-d H:i:s', $subscriptionArray['starts_at'] . ' 00:00:00');
            $item->ends_at = Carbon::createFromFormat('Y-m-d H:i:s', $subscriptionArray['ends_at'] . ' 23:59:59');
            $item->is_trial = $subscriptionArray['is_trial'];

            $conflicts = Subscription::where('company_id', $item->company_id);
            if ($item->exists) {
                $conflicts = $conflicts->where('id', '!=', $item->id);
            }
            $conflicts = $conflicts->where(function ($query) use ($item) {
                $query->whereBetween('starts_at', [$item->starts_at, $item->ends_at])
                    ->orWhereBetween('ends_at', [$item->starts_at, $item->ends_at])
                    ->orWhere(function ($query) use ($item) {
                        $query->where('starts_at', '<', $item->starts_at)
                            ->where('ends_at', '>', $item->starts_at);
                    })
                    ->orWhere(function ($query) use ($item) {
                        $query->where('starts_at', '<', $item->ends_at)
                            ->where('ends_at', '>', $item->ends_at);
                    });
            });
            if ($conflicts->exists()) {
                return response()->json(['error' => 'Impossible d\'avoir deux abonnements sur une même période'], 400);
            }

            $item->save();
            $item->packages()->sync($subscriptionArray['packages']);
            if ($item->starts_at->isFuture()) {
                $item->state = 'pending';
            } else if ($item->ends_at->isFuture()) {
                $item->state = 'active';
            } else {
                $item->state = 'inactive';
            }
            if (isset($subscriptionArray['is_cancelled'])) {
                if ($subscriptionArray['is_cancelled']) {
                    $item->state = 'cancelled';
                }
            }
            $item->save();
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 400);
        }


        return response()->json(['success' => $item->load('company', 'packages')], $this->successStatus);
    }

    /**
     * Restore the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function restore(int $id)
    {
        try {
            $item = Subscription::withTrashed()->find($id);
            $success = $item->restore();

            if (!$success) {
                throw new Exception('Impossible de restaurer l\'abonnement');
            }

            return response()->json(['success' => $item->load('company', 'packages')], $this->successStatus);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'error' => $th->getMessage()], 400);
        }
    }

    /**
     * Archive the specified resource from storage.
     *
     * @param  Subscription  $item
     * @return \Illuminate\Http\Response
     */
    public function destroy(Subscription $item)
    {
        try {
            $success = $item->delete();

            if (!$success) {
                throw new Exception('Impossible d\'archiver l\'abonnement');
            }

            return response()->json(['success' => $item->load('company', 'packages')], $this->successStatus);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'error' => $th->getMessage()], 400);
        }
    }

    /**
     * Delete the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function forceDelete(int $id)
    {
        try {
            $item = Subscription::withTrashed()->find($id);
            $success = $item->forceDelete();

            if (!$success) {
                throw new Exception('Impossible de supprimer l\'abonnement');
            }

            return response()->json(['success' => true], $this->successStatus);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'error' => $th->getMessage()], 400);
        }
    }
}
