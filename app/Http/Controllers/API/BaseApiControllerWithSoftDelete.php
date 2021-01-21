<?php

namespace App\Http\Controllers\API;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

abstract class BaseApiControllerWithSoftDelete extends BaseApiController
{
    protected static $cascade = false;

    /**
     * Restore the specified resource in storage.
     */
    public function restore(Request $request, int $id = null)
    {
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
                if (!(static::$cascade ? $item->restoreCascade() : $item->restore())) {
                    throw new Exception();
                }
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
    }

    /**
     * Soft deletes the specified resources from storage.
     */
    public function destroy(Request $request, int $id = null)
    {
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
                if (!(static::$cascade ? $item->deleteCascade() : $item->delete())) {
                    throw new Exception();
                }
            } catch (\Throwable $th) {
                DB::rollBack();
                return $this->errorResponse("Erreur lors de l'archivage.", static::$response_codes['error_server']);
            }
        }
        DB::commit();

        $items = app($this->model)->withTrashed()->whereIn('id', $ids)->get();

        if (static::$show_load) {
            $items->load(static::$show_load);
        }

        if (static::$show_append) {
            $items->each->append(static::$show_append);
        }

        return $this->successResponse($items, "Archivage terminé avec succès.");
    }

    /**
     * Deletes the specified resources from storage.
     */
    public function forceDestroy(Request $request, int $id = null)
    {
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
                if (!$item->forceDelete()) {
                    throw new Exception();
                }
            } catch (\Throwable $th) {
                DB::rollBack();
                return $this->errorResponse("Erreur lors de la suppression.", static::$response_codes['error_server']);
            }
        }
        DB::commit();

        return $this->successResponse(true, 'Suppression terminée avec succès.');
    }
}
