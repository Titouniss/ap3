<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Traits\ReturnsJsonResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DocumentController extends Controller
{
    use ReturnsJsonResponse;

    /**
     * Store a newly created document.
     */
    public function store(Request $request)
    {
        $arrayRequest = $request->all();
        $validator = Validator::make($arrayRequest, [
            'name' => 'required',
            'path' => 'required',
            'token' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors());
        }

        $item = null;
        DB::beginTransaction();
        try {
            $item = Document::create([
                'name' => $arrayRequest['name'],
                'path' => $arrayRequest['path'],
                'token' => $arrayRequest['token'],
                'is_file' => false
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->errorResponse($th->getMessage());
        }
        DB::commit();

        return $this->successResponse($item, 'Création terminée avec succès');
    }

    public function show($path)
    {
        $item = Document::where('path', 'LIKE', '%' . $path)->first();
        if (!$item) {
            return $this->notFoundResponse();
        }

        return response()->file(Storage::path($item->path));
    }

    /**
     * Uploads a new document to the server.
     */
    public function upload(Request $request, string $token)
    {
        $arrayRequest = $request->all();
        $validator = Validator::make($arrayRequest, [
            'file' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors());
        }

        $item = null;
        DB::beginTransaction();
        try {
            $originalName = $arrayRequest['file']->getClientOriginalName();
            $path = $arrayRequest['file']->store('documents/tmp');
            $item = Document::create(['name' => $originalName, 'path' => $path, 'token' => $token, 'is_file' => true]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->errorResponse($th->getMessage());
        }
        DB::commit();

        return $this->successResponse($item, 'Téléchargement terminé avec succès');
    }

    /**
     * Delete the specified documents from storage.
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
        foreach ($ids as $idToDelete) {
            $item = Document::find($idToDelete);
            if (!$item) {
                DB::rollBack();
                return $this->notFoundResponse();
            }

            try {
                if (!$item->deleteFile()) {
                    throw new Exception();
                }
            } catch (\Throwable $th) {
                DB::rollBack();
                return $this->errorResponse('Erreur lors de la suppression.', static::$response_codes['error_server']);
            }
        }
        DB::commit();

        return $this->successResponse(true, 'Suppression terminée avec succès.');
    }
}
