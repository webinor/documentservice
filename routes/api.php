<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AttachmentTypeCategoryController;
use App\Http\Controllers\AttachmentTypeController;
use App\Http\Controllers\DepartmentDocumentTypeController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentTypeController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\LedgerCodeTypeController;
use App\Http\Controllers\TestThumbnailController;

// Models
use App\Models\Misc\Document;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware("jwt.check")
    ->prefix("documents")
    ->group(function () {
        /**
         * 📌 DocumentTypeController
         */
        Route::controller(DocumentTypeController::class)->group(function () {
            Route::get("/document_types/allowed", "getDocumentTypesWithPermissions");
            Route::get("/document_types/allowed-by-role", "getDocumentTypesWithPermissionsForRoles");
            Route::get("/getRolesByDocumentType/{documentTypeCode}", "getRolesByDocumentType");

        });

                /**
         * 📌 FolderController
         */
        Route::controller(FolderController::class)->group(function () {
            Route::get("/folders/allowed", "getFoldersWithPermissions");
            Route::get("/folders/allowed-by-role", "getFoldersWithPermissionsForRoles");
            Route::get("/getRolesByFolders/{folderCode}", "getRolesByFolders");

                Route::get('/folders/{folderId}/authorized-departments','getAuthorizedDepartments')
                ->whereNumber('folderId'); // ✅ Ne matchera pas "allowed-by-role"


        });

        /**
         * 📌 DocumentController
         */
        Route::controller(DocumentController::class)->group(function () {
            Route::get("/by-ids", "getDocumentsByIds");
            Route::get("/{id}/available-actions", "getAvailableActions");
            Route::get("/{documentId}/attachments", "getAttachments");
            Route::get("/search", "searchDocumentByReference");
            Route::get('/{id}/details', 'getDetails')->name('documents.details');

        });

        /**
         * 📌 AttachmentTypeController
         */
        Route::get("/attachment-types/{category}", [AttachmentTypeController::class, "index"]);
        Route::get('/attachment-types/by-id/{attachmentType}', [AttachmentTypeController::class, 'show']);
        Route::post('/{documentId}/missing-attachment-types', [AttachmentTypeController::class, 'missingForDocument']);


        /**
         * 📌 LedgerCodeTypeController
         */
        Route::get("/ledger-code-types", [LedgerCodeTypeController::class, "index"]);

        /**
         * 📌 AttachmentController
         */
        Route::post("/attachments", [AttachmentController::class, "store"]);

        /**
         * 📌 API Resources
         */
        Route::apiResource("/attachment-type-categories", AttachmentTypeCategoryController::class);
        Route::apiResource("/documentTypes", DocumentTypeController::class);
        Route::apiResource("/folders", FolderController::class);

        // Documents CRUD
        Route::apiResource("/", DocumentController::class)->parameters([
            "" => "document",
        ]);
    });

/**
 * 📌 Public / hors middleware
 */

Route::get('/documents/{id}/download', [DocumentController::class, 'download'])->name('documents.download');


// Génération de thumbnail
Route::get("documents/{document}/generate-thumbnail", [TestThumbnailController::class, "handle"]);

// Affichage des pièces jointes
Route::get("documents/attachments/{attachment}", [AttachmentController::class, "show"]);
Route::get("documents/main_attachment/{document}", [AttachmentController::class, "getMainAttachment"]);

// Thumbnail direct
Route::get("documents/{document}/thumbnail", function (Document $document) {
    $file = $document->attachment->thumbnail->file;

    if (!$file) {
        return response()->json(["message" => "Thumbnail not found"], 404);
    }

    return response()->file(storage_path("app/public/thumbnails/" . $file->path));
});
