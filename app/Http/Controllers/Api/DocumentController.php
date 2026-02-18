<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadDocumentRequest;
use App\Models\Document;
use App\Models\Startup;
use App\Services\LogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DocumentController extends Controller
{
    public function index(Startup $startup): JsonResponse
    {
        try {
            if ($startup->user_id !== request()->user()->id) {
                abort(403);
            }
            $documents = $startup->documents()->get();
            return response()->json($documents);
        } catch (Throwable $e) {
            LogService::exception($e, 'DocumentController@index failed');
            throw $e;
        }
    }

    public function store(UploadDocumentRequest $request, Startup $startup): JsonResponse
    {
        try {
            if ($startup->user_id !== request()->user()->id) {
                abort(403);
            }
            $file = $request->file('file');
            $path = $file->store('documents/' . $startup->id, 'local');
            $document = $startup->documents()->create([
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
            LogService::info('Document uploaded', ['document_id' => $document->id]);
            return response()->json($document, 201);
        } catch (Throwable $e) {
            LogService::exception($e, 'DocumentController@store failed');
            throw $e;
        }
    }

    public function destroy(Startup $startup, Document $document): JsonResponse
    {
        try {
            $this->authorize('delete', $document);
            if ($document->startup_id !== $startup->id) {
                abort(404);
            }
            Storage::disk('local')->delete($document->path);
            $document->delete();
            LogService::info('Document deleted', ['document_id' => $document->id]);
            return response()->json(null, 204);
        } catch (Throwable $e) {
            LogService::exception($e, 'DocumentController@destroy failed');
            throw $e;
        }
    }
}
