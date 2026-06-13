<?php

namespace App\Contracts;

interface WorkflowAvailabilityClient
{
    public function getDocumentContext(int $documentId): array;
}