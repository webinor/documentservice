<?php

namespace App\Services\InvoiceProvider;


use App\Models\Finance\InvoiceProvider;
use App\Models\Misc\Document;
use App\Services\DocumentType\DocumentTypeHandlerInterface;
use Illuminate\Support\Facades\DB;

class InvoiceProviderDocumentHandler implements DocumentTypeHandlerInterface
{
    public function create(Document $document, array $payload): void
    {
        DB::transaction(function () use ($document, $payload) {

            $invoice = new InvoiceProvider();

            $invoice->fill([
            'provider_type'      => $payload['provider_type'] ,//?? 'IT_SUPPLIER',
            'provider'        => $payload['prestataire'] ,
            'provider_reference' => $payload['reference_fournisseur'],
            'deposit_date'             => $payload['dateDepot'],
            'amount'               => $payload['montant'],
            ]);

            $invoice->document()->associate($document);

            $invoice->save();
        });
    }
}