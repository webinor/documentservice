<?php

namespace App\Services\Financial;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FinancialCommitmentService
{
    /**
     * Types d'engagement acceptés par le système.
     *
     * Cette liste pourra être déplacée dans une configuration
     * ou une table de référence lorsque les types seront gérés
     * dynamiquement par l'application.
     */
    private const ALLOWED_TYPES = [
        'PURCHASE_ORDER',
        'SERVICE_CONTRACT',
        'AGREEMENT',
    ];

    /**
     * Crée un engagement financier.
     *
     * Le document GED doit avoir été créé au préalable.
     * La validation métier et les droits d'accès doivent être
     * contrôlés dans la couche applicative appelante.
     *
     * @param array $data
     * @return object
     */
    public function create(array $data)
    {
        if (
            empty($data['commitment_type'])
            || !in_array(
                $data['commitment_type'],
                self::ALLOWED_TYPES,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Le type d’engagement financier est invalide.'
            );
        }

        if (
            !isset($data['initial_amount'])
            || !is_numeric($data['initial_amount'])
            || (float) $data['initial_amount'] < 0
        ) {
            throw new InvalidArgumentException(
                'Le montant initial doit être un nombre positif ou nul.'
            );
        }

        if (
            isset($data['approved_amount'])
            && (
                !is_numeric($data['approved_amount'])
                || (float) $data['approved_amount'] < 0
            )
        ) {
            throw new InvalidArgumentException(
                'Le montant approuvé doit être positif ou nul.'
            );
        }

        return DB::transaction(function () use ($data) {
            $id = DB::table('financial_commitments')->insertGetId([
                'document_id' => $data['document_id'] ?? null,
                'purchase_request_id' =>
                    $data['purchase_request_id'] ?? null,
                'commitment_number' =>
                    $data['commitment_number'] ?? null,
                'commitment_type' => $data['commitment_type'],
                'supplier_id' => $data['supplier_id'] ?? null,
                'supplier_name' => $data['supplier_name'] ?? null,
                'title' => $data['title'] ?? null,
                'initial_amount' => $data['initial_amount'],
                'approved_amount' => $data['approved_amount'] ?? null,
                'currency' => $data['currency'] ?? 'XAF',
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'status' => $data['status'] ?? 'DRAFT',
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'] ?? null,
                'updated_by' => $data['updated_by'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return DB::table('financial_commitments')
                ->where('id', $id)
                ->first();
        });
    }
}