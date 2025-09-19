<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AttachmentTypeController;
use App\Http\Controllers\DepartmentDocumentTypeController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentTypeController;
use App\Http\Controllers\LedgerCodeTypeController;
use App\Http\Controllers\TestThumbnailController;
use App\Models\DepartmentDocumentType;
use App\Models\Misc\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/



Route::middleware('jwt.check')->prefix("documents")->group(function () {

    Route::get('/document_types/allowed', [DocumentTypeController::class , 'getDocumentTypesWithPermissions']);

    Route::get('/document_types/allowed-by-role', [DocumentTypeController::class , 'getDocumentTypesWithPermissionsForRoles']);

    Route::get('/getRolesByDocumentType/{documentTypeCode}', [DocumentTypeController::class , 'getRolesByDocumentType']);

    Route::get('/by-ids', [DocumentController::class , 'getDocumentsByIds']);

    Route::get('/{id}/available-actions', [DocumentController::class, 'getAvailableActions']);

    Route::get('/attachment-types', [AttachmentTypeController::class, 'index']);

    Route::get('/ledger-code-types', [LedgerCodeTypeController::class, 'index']);


    Route::post('/attachments', [AttachmentController::class, 'store']);

    Route::get('/{documentId}/attachments', [DocumentController::class, 'getAttachments']);



   Route::apiResource('/documentTypes', DocumentTypeController::class);

   


   Route::apiResource('/', DocumentController::class)->parameters([
    '' => 'document'
]);




    
});


Route::get('documents/{document}/generate-thumbnail', [TestThumbnailController::class, 'handle']);//->excludedMiddleware('jwt.checks');


Route::get('/documents/attachments/{document}', [AttachmentController::class, 'show']);

Route::get('/documents/main_attachment/{document}', [AttachmentController::class, 'getMainAttachment']);


Route::get('/documents/{document}/thumbnail', function(Document $document){
    $file = $document->attachment->thumbnail->file;
    if(!$file) return response()->json(['message'=>'Thumbnail not found'], 404);

    //dd($file);
    return response()->file(storage_path('app/public/thumbnails/'.$file->path));
});
