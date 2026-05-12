<?php

namespace App\Services;

use App\Contracts\PayableDocumentInterface;
use App\Models\Misc\Document;
use App\Services\UserServiceClient;
use Exception;

class NotifyBeneficiaryService
{
    protected UserServiceClient $userService;

    public function __construct(UserServiceClient $userService)
    {
        $this->userService = $userService;
    }

    // public function execute(int $documentId): array
    // {
    //     $document = Document::with('document_type')->findOrFail($documentId);
    //     $child = $document->{$document->document_type->relation_name};

    //     if (!$child->beneficiary) {
    //         throw new Exception("Beneficiary missing.");
    //     }

    //     $total = collect($child->rides)->reduce(function ($carry, $item) {
    //         return $carry + (int) ($item['montant'] ?? 0);
    //     }, 0);

    //     // Optionnel : vérifier existence user
    //     // $userResponse = $this->userService->getUser($child->beneficiary);

    //     // if (!$userResponse->successful()) {
    //     //     throw new Exception("Unable to fetch beneficiary.");
    //     // }

    //     $eventResponse = $this->userService->dispatchPaymentEvent(
    //         $child->beneficiary,
    //         $total,
    //         $child->reason ?? ""
    //     );

    //     if (!$eventResponse->successful()) {
    //         throw new Exception("Event dispatch failed.");
    //     }

    //     return [
    //         // "user" => $userResponse->json()['user'],
    //         "user" => $eventResponse->json()['user'],
    //         "transaction_code" => $eventResponse->json()['transaction_code']
    //     ];
    // }

     public function execute(int $documentId , string $transactionTypeCode): array
    {
            $document = Document::with('document_type')
                ->findOrFail($documentId);

            $child = $document->{$document->document_type->relation_name};

            if (!$child instanceof PayableDocumentInterface) {
                throw new Exception("Document not payable.");
            }

            $recipient = $child->getSettlementActor();

            $amount = $child->getSettlementAmount();

            $reason = $child->getSettlementReason();

            $direction = $child->getSettlementDirection();


            $eventResponse = $this->userService
                ->dispatchPaymentEvent(
                    $recipient,
                    $amount,
                    $reason,
                    $direction,
                    $transactionTypeCode,
                    $document->id
                );

            if (!$eventResponse->successful()) {
                // throw new Exception("Event $transactionTypeCode dispatch failed.");

                 $errorMessage = $eventResponse->json('message')
        ?? $eventResponse->body()
        ?? 'Unknown error';

    throw new Exception(
        "Event {$transactionTypeCode} dispatch failed: " . $errorMessage
    );

            }

            return [
                "user" => $eventResponse->json()['user'],
                "transaction_code" => $eventResponse->json()['transaction_code']
            ];
    }
}