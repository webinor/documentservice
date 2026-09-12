<?php

namespace App\Services\Export;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Events\AfterSheet;

class DocumentsExport extends DefaultValueBinder implements
    FromArray,
    WithHeadings,
    WithTitle,
    WithStyles,
    WithEvents,
    ShouldAutoSize,
    WithColumnFormatting,
    WithCustomValueBinder
{
    protected $rows;

    protected $columns;

    protected $title;

    public function __construct(
        array $rows,
        array $columns,
        string $title
    ) {
        $this->rows = $rows;
        $this->columns = $columns;
        $this->title = $title;
    }

    /**
     * Données Excel.
     */
    public function array(): array
    {
        return $this->rows;
    }

    /**
     * En-têtes Excel.
     */
    public function headings(): array
    {
        return array_map(function ($column) {
            return $column['label'];
        }, $this->columns);
    }

    /**
     * Nom de la feuille.
     */
    public function title(): string
    {
        $title = preg_replace(
            '/[\\\\\/\*\?\:\[\]]/',
            '',
            $this->title
        );

        $title = trim($title);

        if ($title === '') {
            $title = 'Export';
        }

        return substr($title, 0, 31);
    }

    /**
     * Style de la première ligne.
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                ],
            ],
        ];
    }

    /**
     * Formats numériques.
     *
     * Les montants restent de vrais nombres Excel.
     *
     * Avec la locale française, l'affichage attendu est :
     *
     * 3000       => 3.000,00
     * 12500.50   => 12.500,50
     * 1500000    => 1.500.000,00
     */
    public function columnFormats(): array
    {
        $formats = [];

        foreach ($this->columns as $index => $column) {
            if ($column['type'] !== 'amount') {
                continue;
            }

            $letter = Coordinate::stringFromColumnIndex(
                $index + 1
            );

            /*
             * Format numérique avec deux décimales.
             *
             * Le rendu des séparateurs est interprété
             * selon la locale du classeur / de l'application Excel.
             */
            $formats[$letter] = '#,##0.00';
        }

        return $formats;
    }

    /**
     * Sécurise les chaînes de caractères Excel.
     *
     * Cela empêche notamment qu'une donnée commençant par "="
     * soit interprétée comme une formule Excel.
     */
    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value)) {
            $cell->setValueExplicit(
                $value,
                DataType::TYPE_STRING
            );

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    /**
     * Événements Excel.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                /*
                 * Figer les en-têtes.
                 */
                $sheet->freezePane('A2');

                /*
                 * Activer le filtre automatique.
                 */
                $sheet->setAutoFilter(
                    $sheet->calculateWorksheetDimension()
                );
            },
        ];
    }
}