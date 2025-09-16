<?php

namespace App\Http\Controllers;

use App\Jobs\GeneratePdfThumbnail;
use App\Models\Misc\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TestThumbnailController extends Controller
{
    public function handle(Document $document): JsonResponse
    {
        // Dispatch du job
        GeneratePdfThumbnail::dispatch($document->attachment);

        return response()->json([
            'message' => 'Job de génération de miniature lancé avec succès.',
            'document' => $document,
        ]);
    }
}
