<?php

namespace App\Contracts;

interface PayableDocumentInterface
{
    public function getPaymentRecipient(): int;

    public function getPaymentAmount(): float;

    public function getPaymentReason(): string;
}