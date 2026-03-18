<?php

namespace App\Exports;

use Exception;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DocumentsExport implements FromCollection, WithHeadings
{
    private Collection $documents;
    private array $columnMap;

    /**
     * @param array|Collection $documents Documents à exporter
     * @param array $columnMap Mapping clé => nom de colonne
     *                         Ex : ['title' => 'Titre', 'amount' => 'Montant', 'prestataire_name' => 'Prestataire']
     */
    public function __construct($documents, array $columnMap)
    {
        $this->columnMap = $columnMap;
            
        // throw new Exception(json_encode($documents), 1);


        $this->documents = collect($documents)->map(function ($doc) {

            // Supprimer workflow_status et id principal
            unset($doc['workflow_status'], $doc['id']);

            // Aplatir le document
            $flat = $this->flatten($doc);
            

            // throw new Exception(json_encode($flat), 1);


            // Ne garder que les colonnes du mapping
            $flat = collect($this->columnMap)->mapWithKeys(function ($label, $key) use ($flat) {
                return [$label => $flat[$key] ?? null];
            })->toArray();

            return $flat;
        });

            // throw new Exception(json_encode($this->documents), 1);

    }

    /**
     * Aplatit récursivement un tableau/objet
     */
    private function flatten($array, string $prefix = ''): array
    {
        $result = [];

        // Transformer objets Laravel/StdClass en tableau
        if (is_object($array)) {
            $array = (array) $array;
        }

        foreach ($array as $key => $value) {

            // Supprimer workflow_status
            if ($key === 'workflow_status') continue;

            // Supprimer id principal et prestataire_id
            if (($prefix === '' && $key === 'id') || ($prefix === 'prestataire' && $key === 'id')) continue;

            $newKey = $prefix ? $prefix . '_' . $key : $key;

            if (is_object($value) || is_array($value)) {
                // Applatir récursivement
                $result = array_merge($result, $this->flatten($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }

    public function collection(): Collection
    {
        return $this->documents;
    }

    public function headings(): array
    {
        return array_values($this->columnMap);
    }
}