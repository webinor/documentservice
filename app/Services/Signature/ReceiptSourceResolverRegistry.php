<?php

namespace App\Services\Signature;


use App\Contracts\Signature\ReceiptSourceResolverInterface;
use App\Models\Misc\Document;
use Illuminate\Support\Collection;
use RuntimeException;

class ReceiptSourceResolverRegistry
{
    /**
     * @var ReceiptSourceResolverInterface[]
     */
    protected array $resolvers;

    public function __construct(
        array $resolvers
    ) {
        $this->resolvers = $resolvers;
    }

    /**
     * Retourne le résolveur correspondant au document.
     */
    public function resolve(Document $document): Collection
    {
        foreach ($this->resolvers as $resolver) {
            if ($resolver->supports($document)) {
                return $resolver->resolve($document);
            }
        }

        throw new RuntimeException(
            'Aucun résolveur de justificatifs configuré pour ce document.'
        );
    }
}