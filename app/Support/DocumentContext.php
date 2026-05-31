<?php

namespace App\Support;

class DocumentContext
{
    private static ?array $workflowStatuses = [];

    public static function setWorkflowStatus(int $documentId, array $status): void
    {
        self::$workflowStatuses[$documentId] = $status;
    }

    public static function getWorkflowStatus(int $documentId): ?array
    {
        return self::$workflowStatuses[$documentId] ?? null;
    }
}