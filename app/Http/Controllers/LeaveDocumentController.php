<?php

namespace App\Http\Controllers;

use App\Models\Misc\Document;
use App\Services\DocumentPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LeaveDocumentController extends Controller
{
    public function generate(
        Request $request,
        DocumentPdfService $documentPdfService
    ) {
        $request->validate([
            'document_uuid' => [
                'required',
                'uuid',
            ],

            'instance_id' => [
                'nullable',
                'integer',
            ],

            'context' => [
                'nullable',
                'array',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Document
        |--------------------------------------------------------------------------
        */

        $document =
            Document::where(
                'uuid',
                $request->document_uuid
            )->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Génération PDF
        |--------------------------------------------------------------------------
        */

        $result =
            $documentPdfService->generate(
                $document,
                $request->bearerToken()
            );

        /*
        |--------------------------------------------------------------------------
        | Nom du fichier
        |--------------------------------------------------------------------------
        */

        $fileName =
            $result['file_name'];

        /*
        |--------------------------------------------------------------------------
        | Stockage
        |--------------------------------------------------------------------------
        */

        $path =
            'documents/' .
            $document->uuid .
            '/' .
            $fileName;

        Storage::disk('public')->put(
            $path,
            $result['content']
        );

        /*
        |--------------------------------------------------------------------------
        | Réponse
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,

            'document_uuid' =>
                $document->uuid,

            'instance_id' =>
                $request->instance_id,

            'context' =>
                $request->context,

            'file_name' =>
                $fileName,

            'path' =>
                $path,

            'url' =>
                Storage::disk('public')
                    ->url($path),
        ]);
    }
}