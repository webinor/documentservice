<?php

namespace App\Services;

use App\Contracts\PayableDocumentInterface;
use App\Models\Misc\Document;
use App\Services\Document\DocumentService;
use App\Services\Transaction\TransactionTypeLabelService;
use App\Services\UserServiceClient;
use Exception;

class NotifyBeneficiaryService
{
    protected UserServiceClient $userService;
    protected TransactionTypeLabelService $transactionTypeLabelService;
    protected DocumentService $documentService;

    public function __construct(
        UserServiceClient $userService,
        TransactionTypeLabelService $transactionTypeLabelService,
        DocumentService $documentService
    ) {
        $this->userService = $userService;
        $this->transactionTypeLabelService = $transactionTypeLabelService;
        $this->documentService = $documentService;
    }

    public function execute(string $documentIdentifier, string $transactionTypeCode): array
    {
        // $document = Document::with("document_type")->findOrFail($documentId);

        $document = $this->documentService->getDoc($documentIdentifier);

        $child = $document->{$document->document_type->relation_name};

        if (!$child instanceof PayableDocumentInterface) {
            throw new Exception("Document not payable.");
        }

        $actor = $child->getSettlementActor();

        $amount = $child->getSettlementAmount($transactionTypeCode);

        // $reason = $this->transactionTypeLabelService->getLabel($transactionTypeCode);//  $child->getSettlementReason($transactionTypeCode);
        $reason = $child->getSettlementReason($transactionTypeCode);

        $direction = $child->getSettlementDirection($transactionTypeCode);

        $details = $child->getSettlementDetails();

        // throw new Exception(json_encode($direction), 1);

        $eventResponse = $this->userService->dispatchPaymentEvent(
            $actor,
            $amount,
            $reason,
            $direction,
            $transactionTypeCode,
            $document->id,
            $details
        );

        if (!$eventResponse->successful()) {

            $errorMessage =
                $eventResponse->json("message") ??
                ($eventResponse->body() ?? "Unknown error");

            throw new Exception(
                "Event {$transactionTypeCode} dispatch failed: " . $errorMessage
            );
        }

        $transactionCode = $eventResponse->json()['transaction_code'];

        $child->createSettlementRecord(
    $transactionTypeCode,
    $transactionCode
);

        return [
            "actor" => $eventResponse->json()["actor"],
            "transaction_code" => $transactionCode,
            "amount" => $amount,
        ];
    }
}
