<?php

namespace App\Services\Common;


use App\Models\Misc\Attachment;
use App\Models\Misc\AttachmentType;
use App\Models\DocumentReference;
use App\Models\DocumentReferenceType;
use Closure;

class WorkflowRequirementService
{
    /**
     * Retourne les types requis manquants pour un document.
     *
     * @param int $documentId
     * @param array $requiredIds
     * @param string $existingModel
     * @param string $existingColumn
     * @param string $typeModel
     * @param Closure $mapper
     * @param string|null $relation
     * @return \Illuminate\Support\Collection
     */
    private function getMissingTypes(
        int $documentId,
        array $requiredIds,
        string $existingModel,
        string $existingColumn,
        string $typeModel,
        Closure $mapper,
        $relation = null
    ) {
        if (empty($requiredIds)) {
            return collect();
        }

        /**
         * Types déjà présents sur le document.
         */
        $existingIds = $existingModel::query()
            ->where('document_id', $documentId)
            ->pluck($existingColumn);


        /**
         * Types obligatoires mais absents.
         */
        $missingIds = collect($requiredIds)
            ->diff($existingIds);


        if ($missingIds->isEmpty()) {
            return collect();
        }


        /**
         * Chargement des types manquants.
         */
        $query = $typeModel::query()
            ->whereIn('id', $missingIds);


        if ($relation) {
            $query->with($relation);
        }


        return $query->get()
            ->map($mapper)
            ->values();
    }


    /**
     * Retourne les types de pièces jointes manquants.
     */
    public function getMissingAttachments(
        int $documentId,
        array $requiredIds
    ) {
        return $this->getMissingTypes(
            $documentId,
            $requiredIds,
            Attachment::class,
            'attachment_type_id',
            AttachmentType::class,
            function ($type) {
                return [
                    'id' => $type->id,
                    'name' => $type->name,
                    'slug' => $type->slug,
                    'attachment_number_required' => $type->attachment_number_required,
                    'category_id' => optional($type->attachmentTypeCategory)->id,
                    'category_name' => optional($type->attachmentTypeCategory)->name,
                ];
            },
            'attachmentTypeCategory'
        );
    }


    /**
     * Retourne les types de références documentaires manquants.
     */
    public function getMissingReferences(
        int $documentId,
        array $requiredIds
    ) {
        return $this->getMissingTypes(
            $documentId,
            $requiredIds,
            DocumentReference::class,
            'document_reference_type_id',
            DocumentReferenceType::class,
            function ($type) {
                return [
                    'id' => $type->id,
                    'code' => $type->code,
                    'name' => $type->name,
                    'label' => $type->label,
                    'description' => $type->description,
                    'referenceHasAttachment'=>$type->has_attachment
                ];
            }
        );
    }
}