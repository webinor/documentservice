<?php

namespace App\Services\Export;

use Carbon\Carbon;

class DocumentExportFormatter
{
    /**
     * Transforme les documents en lignes Excel.
     *
     * @param array $documents
     * @param array $columns
     * @param array $workflowMetadata
     * @return array
     */
    public function format(
        array $documents,
        array $columns,
        array $workflowMetadata = []
    ) {
        $rows = [];

        foreach ($documents as $document) {
            $row = [];

            foreach ($columns as $column) {
                $value = call_user_func(
                    $column['value'],
                    $document,
                    isset($workflowMetadata[$document['id']])
                        ? $workflowMetadata[$document['id']]
                        : []
                );

                $row[] = $this->normalize(
                    $value,
                    $column['type']
                );
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Normalise une valeur avant son insertion dans Excel.
     */
    private function normalize($value, $type)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($type === 'amount') {
            if (is_numeric($value)) {
                return (float) $value;
            }

            return null;
        }

        if ($type === 'date') {
            try {
                return Carbon::parse($value)
                    ->format('d/m/Y H:i');
            } catch (\Exception $e) {
                return (string) $value;
            }
        }

        return is_scalar($value)
            ? (string) $value
            : '';
    }
}