<?php

namespace App\Http\Controllers;

use App\Models\Misc\Document;
use App\Models\PurchaseReceipt;
use App\Models\RequestOrder;
use App\Models\RequestOrderItem;
use App\Services\Common\FileManager;
use App\Services\Document\DocumentService;
use App\Services\ResponsibilityService;
use App\Services\WorkflowServiceClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class PurchaseReceiptController extends Controller
{
    private DocumentService $documentService;

    public function __construct(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    /**
     * Confirme la transmission physique de tous les justificatifs
     * d'un bon de commande.
     *
     * Appelé par le Workflow Service avant la signature
     * de réception ou de clôture du règlement.
     *
     * POST /api/documents/{document}/purchase-receipts/confirm-physical-transmission
     */
    public function confirmPhysicalTransmission(
        Request $request,
        int $document
    ) {
        $validated = $request->validate([
            'document_uuid' => ['required', 'string'],
            'received_by' => ['required', 'integer'],
            'received_by_name' => ['required', 'string', 'max:255'],
            'received_by_type' => ['required', 'string', 'max:100'],
            'received_by_role' => ['required', 'string', 'max:255'],
            'source' => ['required', 'string', 'max:100'],
            'transaction_type_code' => ['required', 'string', 'max:100'],
        ]);

        $requestId = (string) Str::uuid();

        $transactionTypeCode = strtoupper(
            trim($validated['transaction_type_code'])
        );

        Log::info(
            '[PurchaseReceiptController] Début de confirmation physique.',
            [
                'request_id' => $requestId,
                'document_id' => $document,
                'document_uuid' => $validated['document_uuid'],
                'received_by' => $validated['received_by'],
                'source' => $validated['source'],
                'transaction_type_code' => $transactionTypeCode,
            ]
        );

        /*
         * La confirmation physique intervient uniquement
         * lors du règlement du bon de commande.
         */
        if ($transactionTypeCode !== 'PURCHASE_SETTLEMENT') {
            return response()->json([
                'success' => false,
                'message' =>
                    'La confirmation physique est autorisée uniquement lors du règlement du bon de commande.',
                'error_code' => 'INVALID_PHYSICAL_TRANSMISSION_TRANSACTION',
                'request_id' => $requestId,
            ], 422);
        }

        try {
            $result = DB::transaction(function () use (
                $document,
                $validated,
                $requestId
            ) {
                /*
                 * 1. Verrouiller le document GED.
                 */
                $documentModel = Document::query()
                    ->where('id', $document)
                    ->where('uuid', $validated['document_uuid'])
                    ->lockForUpdate()
                    ->first();

                if (!$documentModel) {
                    return [
                        'http_status' => 404,
                        'body' => [
                            'success' => false,
                            'message' =>
                                'Document introuvable ou UUID non correspondant.',
                            'error_code' => 'DOCUMENT_NOT_FOUND',
                            'request_id' => $requestId,
                        ],
                    ];
                }

                /*
                 * 2. Retrouver le bon de commande.
                 */
                $requestOrder = RequestOrder::query()
                    ->where('document_id', $documentModel->id)
                    ->lockForUpdate()
                    ->first();

                if (!$requestOrder) {
                    return [
                        'http_status' => 404,
                        'body' => [
                            'success' => false,
                            'message' =>
                                'Aucun bon de commande associé à ce document.',
                            'error_code' => 'REQUEST_ORDER_NOT_FOUND',
                            'request_id' => $requestId,
                        ],
                    ];
                }

                /*
                 * 3. Charger et verrouiller les justificatifs.
                 */
                $receipts = PurchaseReceipt::query()
                    ->where('request_order_id', $requestOrder->id)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                if ($receipts->isEmpty()) {
                    return [
                        'http_status' => 422,
                        'body' => [
                            'success' => false,
                            'message' =>
                                'Aucun justificatif n’est enregistré pour ce bon de commande.',
                            'error_code' => 'NO_PURCHASE_RECEIPTS',
                            'request_id' => $requestId,
                        ],
                    ];
                }

                /*
                 * 4. Confirmer la réception physique.
                 *
                 * Une réception déjà confirmée conserve
                 * sa date et son employé de première réception.
                 */
                $now = now();

                $updatedCount = 0;
                $alreadyReceivedCount = 0;

                foreach ($receipts as $receipt) {
                    if ($receipt->physical_receipt_received) {
                        $alreadyReceivedCount++;
                        continue;
                    }

                    $receipt->physical_receipt_received = true;
                    $receipt->physical_receipt_received_at = $now;

                    $receipt->physical_receipt_received_by_employee_id =
                        $validated['received_by'];

                    $receipt->save();

                    $updatedCount++;
                }

                /*
                 * 5. Vérifier que tous les justificatifs
                 * sont effectivement confirmés.
                 */
                $receivedCount = PurchaseReceipt::query()
                    ->where('request_order_id', $requestOrder->id)
                    ->where('physical_receipt_received', true)
                    ->count();

                $totalCount = $receipts->count();

                if ($receivedCount !== $totalCount) {
                    throw new \RuntimeException(
                        sprintf(
                            'Confirmation physique incomplète : %d justificatif(s) reçu(s) sur %d.',
                            $receivedCount,
                            $totalCount
                        )
                    );
                }

                Log::info(
                    '[PurchaseReceiptController] Tous les justificatifs physiques ont été confirmés.',
                    [
                        'request_id' => $requestId,
                        'document_id' => $documentModel->id,
                        'request_order_id' => $requestOrder->id,
                        'total_receipts' => $totalCount,
                        'updated_receipts' => $updatedCount,
                        'already_received_receipts' => $alreadyReceivedCount,
                        'received_by' => $validated['received_by'],
                    ]
                );

                return [
                    'http_status' => 200,
                    'body' => [
                        'success' => true,
                        'message' =>
                            'La transmission physique de tous les justificatifs du bon de commande a été confirmée.',
                        'request_id' => $requestId,
                        'data' => [
                            'document_id' => $documentModel->id,
                            'document_uuid' => $documentModel->uuid,
                            'request_order_id' => $requestOrder->id,
                            'total_receipts' => $totalCount,
                            'updated_receipts' => $updatedCount,
                            'already_received_receipts' => $alreadyReceivedCount,
                            'all_receipts_received' => true,
                        ],
                    ],
                ];
            }, 3);

            return response()->json(
                $result['body'],
                $result['http_status']
            );
        } catch (Throwable $exception) {
            Log::error(
                '[PurchaseReceiptController] Échec de confirmation physique.',
                [
                    'request_id' => $requestId,
                    'document_id' => $document,
                    'document_uuid' => $validated['document_uuid'],
                    'exception' => get_class($exception),
                    'error' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' =>
                    'Impossible de confirmer la transmission physique des justificatifs du bon de commande.',
                'error_code' => 'PHYSICAL_TRANSMISSION_CONFIRMATION_ERROR',
                'request_id' => $requestId,
            ], 500);
        }
    }

    /**
     * Enregistre ou annule la réception physique d'un justificatif.
     *
     * Seul un utilisateur possédant TREASURER peut effectuer
     * cette opération.
     *
     * PATCH /api/documents/purchase-receipts/{receipt}/physical-receipt
     */
    public function updatePhysicalReceiptStatus(
        Request $request,
        ResponsibilityService $responsibilityService,
        WorkflowServiceClient $workflowServiceClient,
        int $receipt
    ) {
        $validated = $request->validate([
            'received' => ['required', 'boolean'],
        ]);

        /*
         * 1. Vérifier l'utilisateur connecté.
         */
        $user = $request->get('user');

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non authentifié.',
            ], 401);
        }

        /*
         * 2. Vérifier la responsabilité TREASURER.
         */
        $responsibilities =
            $user['employeeContext']['responsibilities'] ?? [];

        $canManagePhysicalReceipts =
            $responsibilityService->hasAnyCode(
                $responsibilities,
                ['TREASURER']
            );

        if (!$canManagePhysicalReceipts) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Vous ne disposez pas de la responsabilité TREASURER.',
            ], 403);
        }

        /*
         * 3. Charger le justificatif et son bon de commande.
         */
        $purchaseReceipt = PurchaseReceipt::query()
            ->with([
                'requestOrder.document',
            ])
            ->findOrFail($receipt);

        if (!$purchaseReceipt->requestOrder) {
            return response()->json([
                'success' => false,
                'message' => 'Le bon de commande associé est introuvable.',
            ], 404);
        }

        $requestOrder = $purchaseReceipt->requestOrder;

        $received = (bool) $validated['received'];

        /*
         * 4. Enregistrer la réception physique.
         */
        DB::transaction(function () use (
            $purchaseReceipt,
            $received,
            $user
        ) {
            $lockedReceipt = PurchaseReceipt::query()
                ->lockForUpdate()
                ->findOrFail($purchaseReceipt->id);

            $lockedReceipt->physical_receipt_received = $received;

            $lockedReceipt->physical_receipt_received_at =
                $received ? now() : null;

            $lockedReceipt->physical_receipt_received_by_employee_id =
                $received
                    ? $user['employee_id']
                    : null;

            $lockedReceipt->save();
        });

        /*
         * 5. Vérifier si tous les justificatifs sont reçus.
         */
        $allReceiptsReceived = $this->areAllReceiptsReceived(
            $requestOrder
        );

        /*
         * 6. Mettre à jour le statut du workflow.
         */
        $documentUuid = optional(
            $requestOrder->document
        )->uuid;

        if (!$documentUuid) {
            Log::warning(
                '[PurchaseReceiptController] Document GED introuvable pour le bon de commande.',
                [
                    'request_order_id' => $requestOrder->id,
                    'receipt_id' => $purchaseReceipt->id,
                ]
            );

            return response()->json([
                'success' => true,
                'message' =>
                    'La réception physique a été enregistrée, mais le document GED associé est introuvable.',
                'workflow_updated' => false,
                'data' => [
                    'receipt_id' => $purchaseReceipt->id,
                    'physical_receipt_received' => $received,
                    'all_receipts_received' => $allReceiptsReceived,
                ],
            ]);
        }

        $statusCode = $allReceiptsReceived
            ? 'RECEIPTS_RECEIVED_WAITING_CLOSURE'
            : 'WAITING_PHYSICAL_SUPPORTING_DOCUMENTS';

        $workflowResponse = $workflowServiceClient
            ->markReceiptsReceivedWaitingClosure(
                $documentUuid,
                $statusCode
            );

        $workflowResult = $workflowResponse->json();

        if (!$workflowResponse->successful()) {
            Log::error(
                '[PurchaseReceiptController] Échec de mise à jour du workflow.',
                [
                    'document_uuid' => $documentUuid,
                    'request_order_id' => $requestOrder->id,
                    'requested_status_code' => $statusCode,
                    'http_status' => $workflowResponse->status(),
                    'response' => $workflowResult,
                ]
            );

            return response()->json([
                'success' => true,
                'message' =>
                    'La réception physique a été enregistrée, mais la mise à jour du workflow a échoué.',
                'workflow_updated' => false,
                'workflow_error' => $workflowResult['message']
                    ?? 'Erreur de communication avec le service Workflow.',
                'data' => [
                    'receipt_id' => $purchaseReceipt->id,
                    'physical_receipt_received' => $received,
                    'all_receipts_received' => $allReceiptsReceived,
                ],
            ]);
        }

        /*
         * 7. Journaliser la modification.
         */
        Log::info(
            '[PurchaseReceiptController] Statut de réception physique mis à jour.',
            [
                'receipt_id' => $purchaseReceipt->id,
                'request_order_id' => $requestOrder->id,
                'received' => $received,
                'user_id' => $user['employee_id'],
                'all_receipts_received' => $allReceiptsReceived,
                'workflow_status' => $statusCode,
            ]
        );

        $purchaseReceipt->refresh();

        return response()->json([
            'success' => true,
            'message' => $received
                ? 'Le justificatif physique a été déclaré reçu.'
                : 'Le justificatif physique est déclaré en attente.',
            'workflow_updated' => true,
            'data' => [
                'receipt_id' => $purchaseReceipt->id,
                'physical_receipt_received' =>
                    (bool) $purchaseReceipt->physical_receipt_received,
                'physical_receipt_received_at' =>
                    $purchaseReceipt->physical_receipt_received_at,
                'physical_receipt_received_by_employee_id' =>
                    $purchaseReceipt->physical_receipt_received_by_employee_id,
                'all_receipts_received' => $allReceiptsReceived,
            ],
        ]);
    }

    /**
     * Vérifie si toutes les réceptions physiques d'un bon de commande
     * sont confirmées.
     */
    private function areAllReceiptsReceived(
        RequestOrder $requestOrder
    ): bool {
        $receipts = $requestOrder->receipts();

        if (!$receipts->exists()) {
            return false;
        }

        return !$requestOrder->receipts()
            ->where('physical_receipt_received', false)
            ->exists();
    }

 /**
 * Récupère les justificatifs associés au bon de commande.
 *
 * GET /api/documents/{document}/purchase-receipts
 */
public function getPurchaseReceipts($documentIdentifier)
{
    $document = $this->documentService->getDoc(
        $documentIdentifier
    );

    $document->load([
        'purchase_request.request_order',
        'purchase_request.request_order.receipts.file',
        'purchase_request.request_order.receipts.paymentSchedules',
    ]);

    $requestOrder = $document->purchase_request->request_order ?? null;

    if (!$requestOrder) {
        return response()->json([
            'success' => true,
            'receipts' => [],
        ]);
    }

    $baseUrl = rtrim(config('app.url'), '/');

    $receipts = $requestOrder->receipts->map(function ($receipt) use ($baseUrl) {
        $file = $receipt->file;

        $receiptData = [
            'id' => $receipt->id,
            'code' => $receipt->code,
            'reference' => $receipt->reference,

            'physical_receipt_received' =>
                $receipt->physical_receipt_received,

            'physical_receipt_received_at' =>
                $receipt->physical_receipt_received_at,

            'physical_receipt_received_by_employee_id' =>
                $receipt->physical_receipt_received_by_employee_id,

            'payment_schedules' => $receipt->paymentSchedules,

            'created_at' => $receipt->created_at,
            'updated_at' => $receipt->updated_at,

            'file' => null,
        ];

        if (!$file) {
            return $receiptData;
        }

        $viewUrl =
            "{$baseUrl}/api/documents/purchase-receipts/{$file->path}/view";

        $downloadUrl =
            "{$baseUrl}/api/documents/purchase-receipts/{$file->path}/download";

        $signedViewUrl = null;
        $signedDownloadUrl = null;

        if (!empty($file->signed_path)) {
            $signedViewUrl =
                "{$baseUrl}/api/documents/purchase-receipts/{$file->signed_path}/view";

            $signedDownloadUrl =
                "{$baseUrl}/api/documents/purchase-receipts/{$file->signed_path}/download";
        }

        $receiptData['file'] = [
            'id' => $file->id,
            'name' => $file->name,
            'path' => $file->path,

            'view_url' => $viewUrl,
            'download_url' => $downloadUrl,

            'signed_path' => $file->signed_path,
            'signed_view_url' => $signedViewUrl,
            'signed_download_url' => $signedDownloadUrl,

            'signed_at' => $file->signed_at,
            'is_signed' => !empty($file->signed_path),
        ];

        return $receiptData;
    });

    return response()->json([
        'success' => true,
        'receipts' => $receipts,
    ]);
}

  /**
 * Synchronise les échéances de paiement associées à un justificatif.
 *
 * PUT /api/documents/{document}/purchase-receipts/{receiptCode}/payment-schedules
 *
 * Payload :
 *
 * {
 *     "payment_schedules": [
 *         {
 *             "payment_schedule_id": 10,
 *             "allocated_amount": 150000
 *         }
 *     ]
 * }
 */
public function syncPaymentSchedules(
    Request $request,
    string $documentIdentifier,
    string $receiptCode
) {
    $validated = $request->validate([
        'payment_schedules' => ['present', 'array'],

        'payment_schedules.*.payment_schedule_id' => [
            'required',
            'integer',
            'distinct',
        ],

        'payment_schedules.*.allocated_amount' => [
            'required',
            'numeric',
            'min:0',
        ],
    ]);

    $document = $this->documentService->getDoc(
        $documentIdentifier
    );

    $document->load('purchase_request.request_order');

    $requestOrder = $document->purchase_request->request_order ?? null;

    abort_if(
        !$requestOrder,
        404,
        'Aucun bon de commande associé à ce document.'
    );

    $receipt = PurchaseReceipt::query()
        ->where('code', $receiptCode)
        ->where('request_order_id', $requestOrder->id)
        ->firstOrFail();

    return DB::transaction(function () use (
        $validated,
        $requestOrder,
        $receipt
    ) {
        $paymentSchedules = $validated['payment_schedules'];

        $scheduleIds = collect($paymentSchedules)
            ->pluck('payment_schedule_id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->all();

        /*
         * Vérifier que toutes les échéances appartiennent
         * au bon de commande concerné.
         */
        $validSchedulesCount = $requestOrder
            ->paymentSchedules()
            ->whereIn('id', $scheduleIds)
            ->count();

        abort_if(
            $validSchedulesCount !== count($scheduleIds),
            422,
            'Une ou plusieurs échéances ne sont pas associées à ce bon de commande.'
        );

        /*
         * Construire les données de la table pivot.
         */
        $syncData = [];

        foreach ($paymentSchedules as $schedule) {
            $scheduleId = (int) $schedule['payment_schedule_id'];

            $syncData[$scheduleId] = [
                'allocated_amount' => $schedule['allocated_amount'],
            ];
        }

        /*
         * Synchroniser les échéances.
         *
         * Les anciennes associations absentes du payload
         * seront supprimées.
         */
        $receipt->paymentSchedules()->sync($syncData);

        /*
         * Recharger le justificatif.
         */
        $receipt->load([
            'file',
            'paymentSchedules',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Échéances du justificatif mises à jour avec succès.',
            'data' => $receipt,
        ]);
    });
}

    /**
 * Enregistre un nouveau justificatif de réception.
 *
 * POST /api/documents/{document}/purchase-receipts
 */
public function store(
    Request $request,
    $documentIdentifier,
    FileManager $fileManager
) {
    $validated = $request->validate([
        'reference' => ['nullable', 'string', 'max:255'],
        'receipt' => ['nullable', 'file', 'max:20480'],
    ]);

    $document = Str::isUuid($documentIdentifier)
        ? Document::where('uuid', $documentIdentifier)->firstOrFail()
        : Document::findOrFail($documentIdentifier);

    $document->load('purchase_request.request_order');

    $requestOrder = $document->purchase_request->request_order ?? null;

    abort_if(
        !$requestOrder,
        404,
        'Aucun bon de commande associé à ce document.'
    );

    return DB::transaction(function () use (
        $request,
        $validated,
        $requestOrder,
        $fileManager
    ) {
        /*
         * 1. Créer le justificatif.
         */
        $receipt = PurchaseReceipt::create([
            'code' => Str::random(10),
            'request_order_id' => $requestOrder->id,
            'reference' => $validated['reference'] ?? null,
        ]);

        /*
         * 2. Enregistrer le fichier.
         */
        if ($request->hasFile('receipt')) {
            $fileManager->replace(
                $receipt,
                'RECEIPT',
                $request->file('receipt')
            );
        }

        /*
         * 3. Charger les relations.
         */
        $receipt->load([
            'file',
            'paymentSchedules',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Justificatif d’achat ajouté avec succès.',
            'data' => $receipt,
        ], 201);
    });
}

   /**
 * Supprime un justificatif du bon de commande.
 *
 * DELETE /api/documents/{document}/purchase-receipts/{receiptCode}
 */
public function destroy(
    string $documentIdentifier,
    string $receiptCode
) {
    $document = $this->documentService->getDoc(
        $documentIdentifier
    );

    $document->load('purchase_request.request_order');

    $requestOrder = $document->purchase_request->request_order ?? null;

    abort_if(
        !$requestOrder,
        404,
        'Aucun bon de commande associé à ce document.'
    );

    $receipt = PurchaseReceipt::query()
        ->where('code', $receiptCode)
        ->where('request_order_id', $requestOrder->id)
        ->firstOrFail();

    DB::transaction(function () use ($receipt) {
        /*
         * 1. Supprimer les associations avec les échéances.
         */
        $receipt->paymentSchedules()->detach();

        /*
         * 2. Supprimer le fichier associé.
         */
        $receipt->load('file');

        if ($receipt->file) {
            Storage::delete($receipt->file->path);

            if (!empty($receipt->file->signed_path)) {
                Storage::delete($receipt->file->signed_path);
            }

            $receipt->file->delete();
        }

        /*
         * 3. Supprimer le justificatif.
         */
        $receipt->delete();
    });

    return response()->json([
        'success' => true,
        'message' => 'Justificatif du bon de commande supprimé avec succès.',
    ]);
}
}