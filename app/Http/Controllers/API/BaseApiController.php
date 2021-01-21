<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

abstract class BaseApiController extends Controller
{
    protected static $response_codes = [
        'success' => 200,
        'error_request' => 400,
        'error_unauthorized' => 403,
        'error_not_found' => 404,
        'error_server' => 500
    ];
    protected static $per_page = 25;

    protected $model;
    protected static $company_id_field = null;
    protected static $index_load = null;
    protected static $index_append = null;
    protected static $show_load = null;
    protected static $show_append = null;
    protected static $store_validation_array = [];
    protected static $update_validation_array = [];

    /**
     * Create a new controller instance.
     */
    public function __construct(string $class)
    {
        $this->model = $class;
    }

    /**
     * Provides custom filtering for the index query
     *
     * @throws \Exception
     */
    protected function filterIndexQuery($query, Request $request)
    {
    }

    /**
     * Display a listing of the resources.
     */
    public function index(Request $request)
    {
        if ($error = $this->permissionErrors('read')) {
            return $error;
        }

        $model = app($this->model);
        $query = $model->select($model->getTable() . ".*");

        $user = Auth::user();
        if (!$user->is_admin && static::$company_id_field !== null) {
            $query->where(static::$company_id_field, $user->company_id);
        }

        $extra = [];

        try {
            if ($request->has('with_trashed') && $request->with_trashed) {
                $query->withTrashed();
            }

            if ($request->has('order_by')) {
                try {
                    $query->orderBy($request->order_by);
                } catch (\Throwable $th) {
                    throw new Exception("Paramètre 'order_by' n'est pas valide.");
                }
            }

            $this->filterIndexQuery($query, $request);

            if ($request->has('page')) {
                if (!is_numeric($request->page)) {
                    throw new Exception("Paramètre 'page' doit être un nombre.");
                }

                $per_page = static::$per_page;
                if ($request->has('per_page')) {
                    if (!is_numeric($request->per_page)) {
                        throw new Exception("Paramètre 'per_page' doit être un nombre.");
                    }
                    $per_page = $request->per_page;
                }

                $current_page = $request->page;
                $paginator = $query->paginate($per_page, $current_page);

                $pageParameter = 'page=' . $current_page;
                $extra['pagination'] = [
                    'first_page_url' => str_replace($pageParameter, 'page=1', $request->fullUrl()),
                    'prev_page_url' => !$paginator->onFirstPage() ? str_replace($pageParameter, 'page=' . ($current_page - 1), $request->fullUrl()) : null,
                    'next_page_url' => $current_page < $paginator->lastPage() ? str_replace($pageParameter, 'page=' . ($current_page + 1), $request->fullUrl()) : null,
                    'last_page_url' => str_replace($pageParameter, 'page=' . $paginator->lastPage(), $request->fullUrl()),
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'count' => $paginator->count(),
                    'total' => $paginator->total()
                ];
            }

            if (static::$index_load) {
                $query->with(static::$index_load);
            }
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage());
        }

        $items = $query->get();

        if (static::$index_append) {
            $items->each->append(static::$index_append);
        }

        return $this->successResponse($items, 'Chargement terminé avec succès.', $extra);
    }

    /**
     * Show the specified resource.
     */
    public function show(int $id)
    {
        $item = app($this->model)->find($id);
        if ($error = $this->itemErrors($item, 'show')) {
            return $error;
        }

        if (static::$show_load) {
            $item->load(static::$show_load);
        }

        if (static::$show_append) {
            $item->append(static::$show_append);
        }

        return $this->successResponse($item);
    }

    /**
     * Stores a new resource in storage based off of a request.
     *
     * @throws \Exception
     */
    protected abstract function storeItem(array $arrayRequest);

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if ($error = $this->permissionErrors('publish')) {
            return $error;
        }

        $arrayRequest = $request->all();
        $validator = Validator::make($arrayRequest, static::$store_validation_array);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors());
        }

        $item = null;
        DB::beginTransaction();
        try {
            $item = $this->storeItem($arrayRequest);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->errorResponse($th->getMessage());
        }
        DB::commit();

        if (static::$show_load) {
            $item->load(static::$show_load);
        }

        if (static::$show_append) {
            $item->append(static::$show_append);
        }

        return $this->successResponse($item, 'Création terminée avec succès');
    }

    /**
     * Updates the specified resource in storage based off of a request.
     *
     * @throws \Exception
     */
    protected abstract function updateItem($item, array $arrayRequest);

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        $item = app($this->model)->find($id);
        if ($error = $this->itemErrors($item, 'edit')) {
            return $error;
        }

        $arrayRequest = $request->all();
        $validator = Validator::make($arrayRequest, BaseApiController::$update_validation_array);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors());
        }

        DB::beginTransaction();
        try {
            $item = $this->updateItem($item, $arrayRequest);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->errorResponse($th->getMessage());
        }
        DB::commit();

        if (static::$show_load) {
            $item->load(static::$show_load);
        }

        if (static::$show_append) {
            $item->append(static::$show_append);
        }

        return $this->successResponse($item, 'Mise à jour terminée avec succès.');
    }

    /**
     * Deletes the item from storage.
     */
    protected function destroyItem($item)
    {
        return $item->delete();
    }

    /**
     * Delete the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $item = app($this->model)->find($id);
        if ($error = $this->itemErrors($item, 'delete')) {
            return $error;
        }

        DB::beginTransaction();
        try {
            if (!$this->destroyItem($item)) {
                throw new Exception();
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->errorResponse('Erreur lors de la suppression.', static::$response_codes['error_server']);
        }
        DB::commit();

        return $this->successResponse(true, 'Suppression terminée avec succès.');
    }

    protected function itemErrors($item, string $perm)
    {
        if (!$item) {
            return $this->notFoundResponse();
        }

        if ($result = $this->permissionErrors($perm, $item)) {
            return $result;
        }

        return null;
    }

    /**
     * Checks whether the current user has permission to access the specified resource.
     */
    protected function permissionErrors(string $perm, $item = null)
    {
        $user = Auth::user();
        if ($user->cant($perm, $item ?? $this->model)) {
            return $this->errorResponse("Accès non autorisé.", static::$response_codes['error_unauthorized']);
        }

        return null;
    }

    /**
     * Returns an item not found response.
     */
    protected function notFoundResponse()
    {
        return $this->errorResponse("Ressource introuvable.", static::$response_codes['error_not_found']);
    }

    /**
     * Returns a successful response.
     */
    protected function successResponse($payload, string $message = 'Chargement terminé avec succès.', array $extra = null)
    {
        $response = [
            'success' => true,
            'message' => $message,
            'payload' => $payload
        ];
        if ($extra != null) {
            $response = array_merge($response, $extra);
        }
        return response()->json($response, static::$response_codes['success']);
    }

    /**
     * Returns a successful response.
     */
    protected function errorResponse(string $message, int $status_code = null)
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        return response()->json($response, $status_code ?? static::$response_codes['error_request']);
    }
}
