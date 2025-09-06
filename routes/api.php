<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\DepartmentDocumentTypeController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentTypeController;
use App\Models\DepartmentDocumentType;
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
    Route::get('/getRolesByDocumentType/{documentTypeCode}', [DocumentTypeController::class , 'getRolesByDocumentType']);

    Route::get('/by-ids', [DocumentController::class , 'getDocumentsByIds']);



   Route::apiResource('/documentTypes', DocumentTypeController::class);

   


   Route::apiResource('/', DocumentController::class)->parameters([
    '' => 'document'
]);



    
});


Route::get('/documents/attachments/{attachment}', [AttachmentController::class, 'show']);