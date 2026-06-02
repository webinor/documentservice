<?php

namespace App\Exports\Mission;

use Maatwebsite\Excel\Concerns\FromArray;

class MissionSummarySheet implements FromArray
{
    private $report;

    public function __construct(array $report)
    {
        $this->report = $report;
    }

    public function array(): array
    {
        return [

            ['MISSION', $this->report['mission']['code'] ?? 'N/A'],

            // =========================
            // DEPENSES
            // =========================
            ['--- DEPENSES ---', ''],

            ['DEPENSES PREVUES', $this->report['expenses']['total_prevu'] ?? 0],
            ['DEPENSES REELLES', $this->report['expenses']['total_reel'] ?? 0],
            ['ECART DEPENSES', $this->report['expenses']['variance'] ?? 0],
            ['VARIANCE % DEPENSES', $this->report['expenses']['variance_percentage'] ?? 0],

            // =========================
            // INDEMNITES
            // =========================
            ['--- INDEMNITES ---', ''],

            ['INDEMNITES PREVUES', $this->report['allowances']['total_estimated'] ?? 0],
            ['INDEMNITES REELLES', $this->report['allowances']['total_final'] ?? 0],
            ['ECART INDEMNITES', $this->report['allowances']['difference'] ?? 0],
            ['VARIANCE % INDEMNITES', $this->report['allowances']['variance_percentage'] ?? 0],

            // =========================
            // AVANCES
            // =========================
            ['--- AVANCES ---', ''],

            ['TOTAL AVANCES', $this->report['advances']['total'] ?? 0],
            ['AVANCES PAYEES', $this->report['advances']['paid']['total'] ?? 0],
            ['AVANCES EN ATTENTE', $this->report['advances']['pending']['total'] ?? 0],
            ['AVANCES ANNULEES', $this->report['advances']['cancelled']['total'] ?? 0],


            /**
             * REGULARISATIONS
             */
            ['RÉGULARISATIONS TRAITÉES',
                $this->report['regulations']['processed']['total'] ?? 0
            ],

            ['REMBOURSEMENTS AGENT',
                $this->report['regulations']['refunds']['total'] ?? 0
            ],

            ['COMPLÉMENTS ENTREPRISE',
                $this->report['regulations']['supplements']['total'] ?? 0
            ],

            // =========================
            // RESULTAT FINANCIER
            // =========================
            ['--- RESULTAT FINANCIER ---', ''],

            ['COUT REEL', $this->report['financial_summary']['real_cost'] ?? 0],
            ['TOTAL AVANCES', $this->report['financial_summary']['total_advances'] ?? 0],
            ['SOLDE', $this->report['financial_summary']['balance'] ?? 0],
            ['STATUT', $this->report['financial_summary']['status'] ?? 'N/A'],
        ];
    }
}