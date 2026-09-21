<?php

namespace App\Http\Controllers;

use App\Models\Misc\Document;
use App\Services\DocumentPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

        'config' => [
            'required',
            'array',
        ],
    ]);

    /*
    |--------------------------------------------------------------------------
    | Document
    |--------------------------------------------------------------------------
    */

    $document = Document::where(
        'uuid',
        $request->document_uuid
    )->firstOrFail();


    $config =  $contexts = $request->input(
        'config'
    );
    /*
    |--------------------------------------------------------------------------
    | Contextes demandés
    |--------------------------------------------------------------------------
    */

    $contexts = $config['contexts'];

    /*
    |--------------------------------------------------------------------------
    | Génération des documents
    |--------------------------------------------------------------------------
    */

    $documents = [];

    foreach ($contexts as $context) {


        /*
     * Chaque contexte travaille sur une nouvelle instance
     * du document récupérée depuis la base de données.
     */
    $documentForGeneration = Document::where(
        'uuid',
        $request->document_uuid
    )->firstOrFail();

  
     Log::info('CONTEXTE AVANT GENERATION', [
        'context' => $context,
        'object_id' => spl_object_id($document),
        'leave_type' => data_get(
            $document,
            'absence_request.leave_type.name'
        ),
        'simulation' => data_get(
            $document,
            'absence_request.simulation'
        ),
    ]);


    

        $result = $documentPdfService->generate(
            $documentForGeneration,
            $request->bearerToken(),
            $context,
            $config['data']
        );

        /*
        |--------------------------------------------------------------------------
        | Nom du fichier
        |--------------------------------------------------------------------------
        */

        $fileName = $result['file_name'];

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
        | Document généré
        |--------------------------------------------------------------------------
        */

        $documents[] = [
            'type' =>
                $context,

            'document_uuid' =>
                $document->uuid,

            'instance_id' =>
                $request->instance_id,

            'context' =>
                $context,

            'file_name' =>
                $fileName,

            'path' =>
                $path,

            'url' =>
                Storage::disk('public')
                    ->url($path),
        ];
    }

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

        'documents' =>
            $documents,
    ]);
}
}