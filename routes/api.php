<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AttachmentTypeCategoryController;
use App\Http\Controllers\AttachmentTypeController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentPaymentController;
use App\Http\Controllers\DocumentTypeController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\LedgerCodeTypeController;
use App\Http\Controllers\MissionAllowanceController;
use App\Http\Controllers\MissionController;
use App\Http\Controllers\MissionDocumentController;
use App\Http\Controllers\MissionExpenseController;
use App\Http\Controllers\MissionFinancialReportController;
use App\Http\Controllers\MissionFinancialSummaryController;
use App\Http\Controllers\SettlementController;
use App\Http\Controllers\TestThumbnailController;
use App\Models\Misc\Document;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::get("/documents/by-status", [DocumentController::class, "getByStatus"]);
Route::post('/documents/settlements/mark-paid', [SettlementController::class, 'markAsPaid']);

 Route::get('/documents/attachments/{id}/download', [AttachmentController::class, 'download'])
    ->name('attachments.download');

Route::middleware("jwt.check")
    ->prefix("documents")
    ->group(function () {
        /**
         * 📌 DocumentTypeController
         */

        Route::post('/types-by-ids', [DocumentController::class, 'typesByIds']);
        Route::controller(DocumentTypeController::class)->group(function () {
            Route::get(
                "/document_types/allowed",
                "getDocumentTypesWithPermissions"
            );
            Route::get(
                "/document_types/allowed-by-role",
                "getDocumentTypesWithPermissionsForRoles"
            );
            Route::get(
                "/getRolesByDocumentType/{documentTypeCode}",
                "getRolesByDocumentType"
            );

            Route::get(
                "/document_types/getByRelation",
                "getDocumentTypesByRelationName"
            );

            Route::get('/documentTypes/by-ids', [DocumentTypeController::class, 'getByIds']);
        });

        Route::post("/mission-expenses/calculate", [
            MissionExpenseController::class,
            "calculate",
        ]);

        /**
         * 📌 FolderController
         */
        Route::controller(FolderController::class)->group(function () {
            Route::get("/folders/allowed", "getFoldersWithPermissions");
            Route::get(
                "/folders/allowed-by-role",
                "getFoldersWithPermissionsForRoles"
            );
            Route::get("/getRolesByFolders/{folderCode}", "getRolesByFolders");

            Route::get(
                "/folders/{folderId}/authorized-departments",
                "getAuthorizedDepartments"
            )->whereNumber("folderId"); // ✅ Ne matchera pas "allowed-by-role"
        });

        /**
         * 📌 DocumentController
         */
        Route::controller(DocumentController::class)->group(function () {
            Route::get("/by-ids", "getDocumentsByIds");
            Route::get("/{id}/available-actions", "getAvailableActions");
            Route::get("/{documentId}/attachments", "getAttachments");
            Route::get("/search", "searchDocumentByReference");
            Route::get("/{id}/details", "getDetails")->name(
                "documents.details"
            );
            Route::post("/notify-beneficiary", [
                DocumentController::class,
                "notifyBeneficiary",
            ]);

            Route::post("/export-invoices", "exportInvoices");

            
        });

        Route::get('/{document}/missions/sheet', [MissionController::class, 'generateSheet']);
        // Route::post('/settlements/mark-paid', [SettlementController::class, 'markAsPaid']);

        Route::controller(DocumentPaymentController::class)->group(function () {
            Route::get("/{document}/payment-status", "paymentStatus");
            Route::post("/{document}/register-payment", "registerPayment");
        });

        Route::get("/expense-categories", [
            ExpenseCategoryController::class,
            "index",
        ]);

        Route::post("/{document}/mission-expenses", [
            MissionExpenseController::class,
            "store",
        ]);

        Route::get("{document}/mission-expenses", [
            MissionExpenseController::class,
            "getMissionExpenses",
        ]);

        Route::patch("/{document}/mission", [
            MissionController::class,
            "update",
        ]);

        Route::get("/{document}/financial-summary", [
            MissionFinancialSummaryController::class,
            "show",
        ]);

        Route::get(
    '/{document}/financial-report',
    [MissionFinancialReportController::class, 'show']
);

Route::get(
    '/{document}/financial-report/export',
    [MissionFinancialReportController::class, 'export']
);

        Route::delete("/{document}/mission-expenses/{missionExpense}", [
            MissionExpenseController::class,
            "destroy",
        ]);

        Route::put("/{document}/mission-expenses/{missionExpense}", [
            MissionExpenseController::class,
            "update",
        ]);

        Route::prefix("missions")->group(function () {
            Route::post("/generate", [
                MissionDocumentController::class,
                "generate",
            ]);

    //     Route::get('/attachments/{id}/download', [AttachmentController::class, 'download'])
    // ->name('attachments.download');

            Route::get("/{mission}/generate-mission-letter", [
                MissionDocumentController::class,
                "generateMissionLetter",
            ]);

            Route::get("/{mission}/generate-mission-order", [
                MissionDocumentController::class,
                "generateMissionOrder",
            ]);

            Route::get("/{mission}/generate-regularization-sheet", [
                MissionDocumentController::class,
                "generateRegularizationSheet",
            ]);
        });

        Route::get("/{document}/mission-allowances", [
            MissionAllowanceController::class,
            "index",
        ]);

        Route::get("mission-expenses/categories-limits", [
            MissionExpenseController::class,
            "categoriesLimits",
        ]);

        /**
         * 📌 AttachmentTypeController
         */
        Route::get("/attachment-types/{category}", [
            AttachmentTypeController::class,
            "index",
        ]);
        Route::get("/attachment-types/by-id/{attachmentType}", [
            AttachmentTypeController::class,
            "show",
        ]);

        Route::get("/get-attachment-types", [
            AttachmentTypeController::class,
            "get_attachment_types",
        ]);

        Route::post("/{documentId}/missing-attachment-types", [
            AttachmentTypeController::class,
            "missingForDocument",
        ]);

        /**
         * 📌 LedgerCodeTypeController
         */
        Route::get("/ledger-code-types", [
            LedgerCodeTypeController::class,
            "index",
        ]);

        /**
         * 📌 AttachmentController
         */
        Route::post("/attachments", [AttachmentController::class, "store"]);

        /**
         * 📌 API Resources
         */
        Route::apiResource(
            "/attachment-type-categories",
            AttachmentTypeCategoryController::class
        );
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

Route::get("/documents/{id}/download", [
    DocumentController::class,
    "download",
])->name("documents.download");

Route::get("/documents/{doc}/download-document", [
    DocumentController::class,
    "download_document",
])->name("documents.download_document");

// Génération de thumbnail
Route::get("documents/{document}/generate-thumbnail", [
    TestThumbnailController::class,
    "handle",
]);

// Affichage des pièces jointes
Route::get("documents/attachments/{attachment}", [
    AttachmentController::class,
    "show",
]);
Route::get("documents/main_attachment/{document}", [
    AttachmentController::class,
    "getMainAttachment",
]);

// Thumbnail direct
Route::get("documents/{document}/thumbnail", function (Document $document) {
    $file = $document->attachment->thumbnail->file;

    if (!$file) {
        return response()->json(["message" => "Thumbnail not found"], 404);
    }

    return response()->file(
        storage_path("app/public/thumbnails/" . $file->path)
    );
});
