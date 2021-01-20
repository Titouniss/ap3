<?php

namespace App\Http\Controllers\API;

use Exception;
use Illuminate\Support\Facades\DB;

abstract class BaseApiControllerWithSoftDelete extends BaseApiController
{
    protected static $cascade = false;

    /**
     * Restore the specified resource in storage.
     */
    public function restore($id)
    {
        $item = app($this->model)->withTrashed()->find($id);
        if (!$item) {
            return $this->notFoundResponse();
        }

        if ($result = $this->unauthorizedTo('delete', $item)) {
            return $result;
        }

        DB::beginTransaction();
        try {
            if (!(static::$cascade ? $item->restoreCascade() : $item->restore())) {
                throw new Exception();
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->errorResponse('Erreur lors de la restauration.', static::$response_codes['error_server']);
        }
        DB::commit();

        if (static::$show_load) {
            $item->load(static::$show_load);
        }

        if (static::$show_append) {
            $item->append(static::$show_append);
        }

        return $this->successResponse($item, 'Restauration terminée avec succès.');
    }

    /**
     * Soft delete the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $item = app($this->model)->find($id);
        if (!$item) {
            return $this->notFoundResponse();
        }

        if ($result = $this->unauthorizedTo('delete', $item)) {
            return $result;
        }

        DB::beginTransaction();
        try {
            if (!(static::$cascade ? $item->deleteCascade() : $item->delete())) {
                throw new Exception();
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->errorResponse('Erreur lors de l\'archivage.', static::$response_codes['error_server']);
        }
        DB::commit();

        if (static::$show_load) {
            $item->load(static::$show_load);
        }

        if (static::$show_append) {
            $item->append(static::$show_append);
        }

        return $this->successResponse($item, 'Archivage terminé avec succès.');
    }

    /**
     * Delete the specified resource from storage.
     */
    public function forceDelete(int $id)
    {
        $item = app($this->model)->withTrashed()->find($id);
        if (!$item) {
            return $this->notFoundResponse();
        }

        if ($result = $this->unauthorizedTo('delete', $item)) {
            return $result;
        }

        DB::beginTransaction();
        try {
            if (!$item->forceDelete()) {
                throw new Exception();
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->errorResponse('Erreur lors de la suppression.', static::$response_codes['error_server']);
        }
        DB::commit();

        return $this->successResponse(true, 'Suppression terminée avec succès.');
    }
}
