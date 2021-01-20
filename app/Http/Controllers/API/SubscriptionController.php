<?php

namespace App\Http\Controllers\API;

use App\Models\Company;
use App\Models\Package;
use App\Models\Subscription;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class SubscriptionController extends BaseApiControllerWithSoftDelete
{
    protected static $company_id_field = 'company_id';
    protected static $index_load = ['company:id,name', 'packages:id,name,display_name'];
    protected static $index_append = null;
    protected static $show_load = ['company:id,name', 'packages:id,name,display_name'];
    protected static $show_append = null;
    protected static $cascade = false;

    protected static $store_validation_array = [
        'starts_at' => 'required',
        'ends_at' => 'required',
        'packages' => 'required',
        'is_trial' => 'required'
    ];

    protected static $update_validation_array = [
        'starts_at' => 'required',
        'ends_at' => 'required',
        'packages' => 'required',
        'is_trial' => 'required'
    ];

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        parent::__construct(Subscription::class);
    }

    protected function filterIndexQuery($query, Request $request)
    {
        if ($request->has('company_id')) {
            $item = Company::find($request->company_id);
            if (!$item) {
                throw new Exception("Paramètre 'company_id' n'est pas valide.");
            }

            $query->whereIn('id', $item->subscriptions->pluck('id'));
        }

        if (!$request->has('order_by')) {
            $query->orderByRaw("FIELD(state , 'active', 'pending', 'cancelled', 'inactive') ASC")->orderBy('ends_at', 'desc')->orderBy('starts_at', 'desc');
        }
    }

    /**
     * Display a listing of the package resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function packages()
    {
        if ($result = $this->permissionErrors('read')) {
            return $result;
        }

        $items = Package::select("id", "name", "display_name");

        return $this->successResponse($items->get());
    }

    protected function storeItem(array $arrayRequest)
    {
        $item = new Subscription;
        $item->company_id = $arrayRequest['company_id'];
        return $this->createOrUpdateSubscription($arrayRequest, $item);
    }

    protected function updateItem($item, array $arrayRequest)
    {
        return $this->createOrUpdateSubscription($arrayRequest, $item);
    }

    /**
     * Creates or updates a subscription with new values
     */
    private function createOrUpdateSubscription(array $subscriptionArray, Subscription $item)
    {
        try {
            $item->starts_at = Carbon::createFromFormat('Y-m-d H:i:s', $subscriptionArray['starts_at'] . ' 00:00:00');
        } catch (\Throwable $th) {
            throw new Exception("Paramètre 'subscription.starts_at' doit être du format 'd/m/Y'.");
        }
        try {
            $item->ends_at = Carbon::createFromFormat('Y-m-d H:i:s', $subscriptionArray['ends_at'] . ' 23:59:59');
        } catch (\Throwable $th) {
            throw new Exception("Paramètre 'subscription.ends_at' doit être du format 'd/m/Y'.");
        }

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
            throw new Exception("Impossible d'avoir deux abonnements sur une même période");
        }

        $item->is_trial = $subscriptionArray['is_trial'];
        $item->save();

        try {
            $item->packages()->sync($subscriptionArray['packages']);
        } catch (\Throwable $th) {
            throw new Exception("Paramètre 'subscription.packages' contient des valeurs invalides.");
        }

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

        return $item;
    }
}
