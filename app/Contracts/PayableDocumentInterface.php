<?php

namespace App\Contracts;

interface PayableDocumentInterface
{
    public function getSettlementActor(): int;

    public function getSettlementAmount(string $transaction_type_code): float;

    public function getSettlementReason(string $transaction_type_code): string;

    public function getSettlementDirection(string $transaction_type_code): string;

    public function getSettlementDetails(): array;



}