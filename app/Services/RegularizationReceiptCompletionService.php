<?php

namespace App\Services;

use App\Models\RegularizationReceipt;
use App\Models\RegularizationSheet;

class RegularizationReceiptCompletionService
{
    /**
     * Vérifie que tous les justificatifs enregistrés pour la fiche
     * possèdent un fichier et ont été physiquement reçus.
     *
     * Une fiche sans justificatif ne peut pas être considérée
     * comme complète.
     *
     * @param RegularizationSheet $sheet
     * @return bool
     */
    public function areAllReceiptsReceived(
        RegularizationSheet $sheet
    ): bool {
        $receipts = $sheet->receipts()
            ->with('file')
            ->get();

        if ($receipts->isEmpty()) {
            return false;
        }

        foreach ($receipts as $receipt) {
            if (!$this->isReceiptComplete($receipt)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Vérifie la présence du fichier et la réception physique.
     *
     * @param RegularizationReceipt $receipt
     * @return bool
     */
    private function isReceiptComplete(
        RegularizationReceipt $receipt
    ): bool {
        return $receipt->file !== null
            && $receipt->physical_receipt_received === true;
    }
}