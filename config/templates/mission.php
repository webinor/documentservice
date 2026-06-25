<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mapping Cellule => Donnée métier
    |--------------------------------------------------------------------------
    */

    

    'cells' => [

        'A5' => 'mission.code',
        'C7' => 'mission.contractor.name',
        'A8' => 'mission.contractor.position',

        'E8'  => 'agent.name',
        'H8'  => 'agent.position',

        'F13' => 'mission.destination',
        'A19' => 'mission.title',

        'B25'=> 'mission.departure_base_date',
        'F25' => 'mission.arrival_site_date',
        'F26' => 'mission.departure_site_date',
        'B26' => 'mission.arrival_base_date',

        'E12' => 'mission.departure_date',
        'E15' => 'mission.arrival_date',

        'E30' => 'advance.amount',
    ],


    

  'signatures' => [

  'top' => [


    

    'HEAD_OF_DEPARTMENT' => [

        'placements' => [

            [
                'signature' => 'A12',
                'name'      => 'A14',
                'date'      => 'A16',
            ],

            //   [
            //     'signature' => 'D49',
            //      'name'      => 'D53',
            //     'date'      => 'D54',
            // ],

        ],

    ],

    'CL' => [

        'placements' => [

            [
                'signature' => 'B12',
                'name'      => 'B14',
                'date'      => 'B16',
            ],

        ],

    ],

    'DO' => [

        'placements' => [

            [
                'signature' => 'C12',
                'name'      => 'C14',
                'date'      => 'C16',
            ],

        ],

    ],

    'DG' => [

        'placements' => [

            [
                'signature' => 'D12',
                'name'      => 'D14',
                'date'      => 'D16',
            ],

        ],

    ],

  ],

  'bottom' => [

         'start_after_table_offset' => 2,

        'positions' => [


                // 'role'=>[
            'OWNER' => 'A',
            'DG' => 'H',
            'DAF' => 'F',
            'DO' => 'G',
            'HEAD_OF_DEPARTMENT' => 'D',
            'CC' =>'E',

                // ],

            // 'actor' => [
            //     'OWNER' => 'A'
            // ]
          
        ],

        'rows' => [
            'signature' => 0,
            'name' => 2,
            'date' => 4,
        ],

  ]

],

'tables' => [

    'previsionnelles' => [

        'source' => 'expenses.previsionnelles',

        'start_row' => 33,

        'columns' => [

            'A' => 'label',
            'E' => 'amount',
            'G' => 'quantity',
            'H' => 'total',
        ],

         'footer' => [
            'enabled' => true,
            'label_cell' => 'C',
            'value_cell' => 'H',
            'label' => 'TOTAL',
            'formula' => 'sum', // future extensibilité
        ],
    ],

    // 'declarees' => [

    //     'source' => 'expenses.declarees',

    //     'start_row' => 40,

    //     'columns' => [

    //         'A' => 'expense_category.name',
    //         'B' => 'quantity',
    //         'C' => 'amount',
    //         'D' => 'total',
    //     ],
    // ],

],

'after_table' => [

    'previsionnelles' => [

        [
            // 'label' => 'Avance mission',
            'value' => 'mission.advance',
            'label_cell' => 'C',
            'value_cell' => 'H',
        ],

    ],

],


];