<?php

namespace App\Contracts;

interface PayableDocumentInterface
{
    public function getSettlementActor(): int;

    public function getSettlementAmount(): float;

    public function getSettlementReason(): string;

    public function getSettlementDirection(): string;
}