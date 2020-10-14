<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class DocumentController extends Controller
{
    public $successStatus = 200;

    public function getFile($path)
    {
        $item = Document::where('path', 'LIKE', '%' . $path)->first();
        if (!$item) {
            return response()->json(['error' => 'Fichier introuvable'], 404);
        }

        $controllerLog = new Logger('document');
        $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
        $controllerLog->info('document item', [$item]);

        return response()->file(Storage::path($item->path));
    }

    public function uploadFile(Request $request, $token)
    {
        $arrayRequest = $request->all();

        if ($arrayRequest['files']) {
            $originalName = $arrayRequest['files']->getClientOriginalName();
            $path = $arrayRequest['files']->store('documents/tmp');
            $response = Document::create(['name' => $originalName, 'path' => $path, 'token' => $token]);

            return response()->json(['success' => $response], $this->successStatus);
        }
    }

    public function deleteFile($id)
    {
        $item = Document::findOrFail($id);
        $item->deleteFile();
        return response()->json(['success' => true], $this->successStatus);
    }

    public function deleteFiles(Request $request)
    {
        $arrayRequest = $request->all();
        if ($arrayRequest) {
            $items = Document::whereIn('id', $arrayRequest)->get();

            foreach ($items as $item) {
                $item->deleteFile();
            }

            return response()->json(['success' => true], $this->successStatus);
        }
    }
}
