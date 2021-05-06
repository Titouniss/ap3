<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Traits\ReturnsJsonResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

abstract class BaseApiController extends Controller
{
    use ReturnsJsonResponse;

    protected static $per_page = 10;

    protected $model;
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
     * Provides custom extra data to be returned by the indexx function.
     */
    protected function extraIndexData(Request $request, $items, $nonPaginatedQuery)
    {
        return [];
    }

    /**
     * Provides custom filtering for the index query.
     *
     * @throws \App\Exceptions\ApiException
     * @throws \Exception
     */
    protected function filterIndexQuery(Request $request, $query)
    {
    }

    /**
     * Display a listing of the resources.
     */
    public function index(Request $request)
    {
        try {
            if ($error = $this->permissionErrors('read')) {
                return $error;
            }

            $fields = collect(['*']);
            if ($request->has('fields') && $request->fields) {
                try {
                    $fields = collect(['id']);
                    if (is_array($request->fields)) {
                        $fields = $fields->merge($request->fields);
                    } else {
                        $fields->push($request->fields);
                    }
                } catch (\Throwable $th) {
                    throw new ApiException("Paramètre 'fields' n'est pas valide.");
                }
            }

            $model = app($this->model);
            $table = $model->getTable();
            $query = $model->select("{$table}.{$fields->shift()}");
            foreach ($fields as $field) {
                $query->addSelect("{$table}.{$field}");
            }

            $user = Auth::user();
            if (!$user->is_admin) {
                if ($this->modelHasCompany()) {
                    $query->where('company_id', $user->company_id);
                }
            }

            $extra = collect([]);

            if ($this->modelUsesSoftDelete() && $request->has('with_trashed') && $request->with_trashed) {
                $query->withTrashed();
            }

            if ($request->has('order_by')) {
                try {
                    $direction = 'asc';
                    if ($request->has('order_by_desc')) {
                        $direction = filter_var($request->order_by_desc, FILTER_VALIDATE_BOOLEAN) ? 'desc' : 'asc';
                    }

                    $query->orderBy($request->order_by, $direction);
                } catch (\Throwable $th) {
                    throw new ApiException("Paramètre 'order_by' n'est pas valide.");
                }
            }

            $this->filterIndexQuery($request, $query);

            if ($request->has('q') && $request->q) {
                try {
                    $query->where(function ($query) use ($model, $request) {
                        $columns = DB::select("describe " . $model->getTable());
                        foreach ($columns as $column) {
                            if (str_contains($column->Type, "char")) {
                                $query->orWhere($column->Field, 'like', '%' . $request->q . '%');
                            }
                        }
                    });
                } catch (\Throwable $th) {
                    throw new ApiException("Paramêtre 'q' n'est pas valide.");
                }
            }

            $nonPaginatedQuery = clone $query;

            if ($request->has('page')) {
                if (!is_numeric($request->page)) {
                    throw new ApiException("Paramètre 'page' doit être un nombre.");
                }

                $per_page = static::$per_page;
                if ($request->has('per_page')) {
                    if (!is_numeric($request->per_page)) {
                        throw new ApiException("Paramètre 'per_page' doit être un nombre.");
                    }
                    $per_page = $request->per_page;
                }

                $current_page = $request->page;
                $paginator = $query->paginate($per_page, $current_page);

                $pageParameter = 'page=' . $current_page;
                $extra->put('pagination', [
                    'first_page_url' => str_replace($pageParameter, 'page=1', $request->fullUrl()),
                    'prev_page_url' => !$paginator->onFirstPage() ? str_replace($pageParameter, 'page=' . ($current_page - 1), $request->fullUrl()) : null,
                    'next_page_url' => $current_page < $paginator->lastPage() ? str_replace($pageParameter, 'page=' . ($current_page + 1), $request->fullUrl()) : null,
                    'last_page_url' => str_replace($pageParameter, 'page=' . $paginator->lastPage(), $request->fullUrl()),
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'count' => $paginator->count(),
                    'total' => $paginator->total()
                ]);
            }

            if ($request->has('loads')) {
                try {
                    if ($loads = $request->loads) {
                        $query->with($loads);
                    }
                } catch (\Throwable $th) {
                    throw new ApiException("Paramètre 'loads' n'est pas valide.");
                }
            } else {
                $query->with(static::$index_load);
            }

            $items = $query->get();

            if ($request->has('appends')) {
                try {
                    $appends = $request->appends;
                    if (!is_array($appends)) {
                        $appends = $appends ? [$appends] : [];
                    }
                    $items->each->setAppends($appends);
                } catch (\Throwable $th) {
                    throw new ApiException("Paramètre 'appends' n'est pas valide.");
                }
            } else if (static::$index_append) {
                $items->each->append(static::$index_append);
            }

            $extra = $extra->merge($this->extraIndexData($request, $items, $nonPaginatedQuery));

            return $this->successResponse($items, 'Chargement terminé avec succès.', $extra->toArray());
        } catch (ApiException $th) {
            return $this->errorResponse($th->getMessage(), $th->getHttpCode());
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage(), static::$response_codes['error_server']);
        }
    }

    /**
     * Show the specified resource.
     */
    public function show(int $id)
    {
        try {
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
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage(), static::$response_codes['error_server']);
        }
    }

    /**
     * Stores a new resource in storage based off of a request.
     *
     * @throws \App\Exceptions\ApiException
     * @throws \Exception
     */
    protected abstract function storeItem(array $arrayRequest);

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
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
            } catch (ApiException $th) {
                DB::rollBack();
                return $this->errorResponse($th->getMessage(), $th->getHttpCode());
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
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage(), static::$response_codes['error_server']);
        }
    }

    /**
     * Updates the specified resource in storage based off of a request.
     *
     * @throws \App\Exceptions\ApiException
     * @throws \Exception
     */
    protected abstract function updateItem($item, array $arrayRequest);

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        try {
            $item = app($this->model)->find($id);
            if ($error = $this->itemErrors($item, 'edit')) {
                return $error;
            }

            $arrayRequest = $request->all();
            $validator = Validator::make($arrayRequest, static::$update_validation_array);
            if ($validator->fails()) {
                return $this->errorResponse($validator->errors());
            }

            DB::beginTransaction();
            try {
                $item = $this->updateItem($item, $arrayRequest);
            } catch (ApiException $th) {
                DB::rollBack();
                return $this->errorResponse($th->getMessage(), $th->getHttpCode());
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
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage(), static::$response_codes['error_server']);
        }
    }

    /**
     * Restores the item in storage.
     *
     * @throws \App\Exceptions\ApiException
     * @throws \Exception
     */
    protected function restoreItem($item)
    {
        return $item->restore();
    }

    /**
     * Restore the specified resource in storage.
     */
    public function restore(Request $request, int $id = null)
    {
        try {
            if (!$this->modelUsesSoftDelete()) {
                return $this->errorResponse("Erreur d'accès.", static::$response_codes['error_server']);
            }

            $ids = collect($id ? [$id] : []);
            if ($ids->isEmpty()) {
                $arrayRequest = $request->all();
                $validator = Validator::make($arrayRequest, [
                    'ids' => 'required|array'
                ]);
                if ($validator->fails()) {
                    return $this->errorResponse($validator->errors());
                }
                $ids = collect($arrayRequest['ids']);
            }

            DB::beginTransaction();
            foreach ($ids as $idToRestore) {
                $item = app($this->model)->withTrashed()->find($idToRestore);
                if ($error = $this->itemErrors($item, 'delete')) {
                    DB::rollBack();
                    return $error;
                }

                try {
                    if (!$this->restoreItem($item)) {
                        throw new Exception();
                    }
                } catch (ApiException $th) {
                    DB::rollBack();
                    return $this->errorResponse($th->getMessage(), $th->getHttpCode());
                } catch (\Throwable $th) {
                    DB::rollBack();
                    return $this->errorResponse("Erreur lors de la restauration.", static::$response_codes['error_server']);
                }
            }
            DB::commit();

            $items = app($this->model)->whereIn('id', $ids)->get();

            if (static::$show_load) {
                $items->load(static::$show_load);
            }

            if (static::$show_append) {
                $items->each->append(static::$show_append);
            }

            return $this->successResponse($items, "Restauration terminé avec succès.");
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage(), static::$response_codes['error_server']);
        }
    }

    /**
     * Soft deletes the item from storage.
     *
     * @return boolean
     * @throws \App\Exceptions\ApiException
     * @throws \Exception
     */
    protected function destroyItem($item)
    {
        return $item->delete();
    }

    /**
     * Soft deletes the specified resources from storage.
     */
    public function destroy(Request $request, int $id = null)
    {
        try {
            $modelUsesSoftDelete = $this->modelUsesSoftDelete();

            $ids = collect($id ? [$id] : []);
            if ($ids->isEmpty()) {
                $arrayRequest = $request->all();
                $validator = Validator::make($arrayRequest, [
                    'ids' => 'required|array'
                ]);
                if ($validator->fails()) {
                    return $this->errorResponse($validator->errors());
                }
                $ids = collect($arrayRequest['ids']);
            }

            DB::beginTransaction();
            foreach ($ids as $idToArchive) {
                $item = app($this->model)->find($idToArchive);
                if ($error = $this->itemErrors($item, 'delete')) {
                    DB::rollBack();
                    return $error;
                }

                try {
                    if (!$this->destroyItem($item)) {
                        throw new Exception();
                    }
                } catch (ApiException $th) {
                    DB::rollBack();
                    return $this->errorResponse($th->getMessage(), $th->getHttpCode());
                } catch (\Throwable $th) {
                    DB::rollBack();
                    return $this->errorResponse("Erreur lors de " . $modelUsesSoftDelete ? "l'archivage." : "la suppression.", static::$response_codes['error_server']);
                }
            }
            DB::commit();

            if ($modelUsesSoftDelete) {
                $items = app($this->model)->withTrashed()->whereIn('id', $ids)->get();

                if (static::$show_load) {
                    $items->load(static::$show_load);
                }

                if (static::$show_append) {
                    $items->each->append(static::$show_append);
                }

                return $this->successResponse($items, "Archivage terminé avec succès.");
            }

            return $this->successResponse(true, 'Suppression terminée avec succès.');
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage(), static::$response_codes['error_server']);
        }
    }

    /**
     * Deletes the item from storage.
     *
     * @throws \App\Exceptions\ApiException
     * @throws \Exception
     */
    protected function forceDestroyItem($item)
    {
        return $item->forceDelete();
    }

    /**
     * Deletes the specified resources from storage.
     */
    public function forceDestroy(Request $request, int $id = null)
    {
        try {
            if (!$this->modelUsesSoftDelete()) {
                return $this->errorResponse("Erreur d'accès.", static::$response_codes['error_server']);
            }

            $ids = collect($id ? [$id] : []);
            if ($ids->isEmpty()) {
                $arrayRequest = $request->all();
                $validator = Validator::make($arrayRequest, [
                    'ids' => 'required|array'
                ]);
                if ($validator->fails()) {
                    return $this->errorResponse($validator->errors());
                }
                $ids = collect($arrayRequest['ids']);
            }

            DB::beginTransaction();
            foreach ($ids as $idToDelete) {
                $item = app($this->model)->withTrashed()->find($idToDelete);
                if ($error = $this->itemErrors($item, 'delete')) {
                    DB::rollBack();
                    return $error;
                }

                try {
                    if (!$this->forceDestroyItem($item)) {
                        throw new Exception();
                    }
                } catch (ApiException $th) {
                    DB::rollBack();
                    return $this->errorResponse($th->getMessage(), $th->getHttpCode());
                } catch (\Throwable $th) {
                    DB::rollBack();
                    return $this->errorResponse($th->getMessage(), static::$response_codes['error_server']);
                    return $this->errorResponse("Erreur lors de la suppression.", static::$response_codes['error_server']);
                }
            }
            DB::commit();

            return $this->successResponse(true, 'Suppression terminée avec succès.');
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage(), static::$response_codes['error_server']);
        }
    }

    protected function itemErrors($item, string $perm)
    {
        if (!$item) {
            return $this->notFoundResponse();
        }

        if ($error = $this->permissionErrors($perm, $item)) {
            return $error;
        }

        return null;
    }

    /**
     * Checks whether the current user has permission to access the specified resource.
     */
    protected function permissionErrors(string $perm, $item = null)
    {
        $user = Auth::user();

        if (!$user || $user->cant($perm, $item ?? $this->model)) {
            return $this->unauthorizedResponse();
        }

        return null;
    }

    /**
     * Checks if the model uses soft delete
     */
    private function modelUsesSoftDelete()
    {
        return in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($this->model));
    }

    /**
     * Checks if the model belongs to a company
     */
    private function modelHasCompany()
    {
        return in_array('App\Traits\HasCompany', class_uses($this->model));
    }
}
