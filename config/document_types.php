<?php 


return [
    "invoice_provider" => [
        "fields" => [
            "amount" => "amount",
            "acteur_principal" => "provider",
        ],
    ],

    "taxi_provider" => [
        "fields" => [
            "amount" => "amount",
            "acteur_principal" => "driver_name",
        ],
    ],

        "fee_note" => [
        "fields" => [
            "amount" => "amount",
            "acteur_principal" => "driver_name",
        ],
    ],

    "mission_provider" => [
        "fields" => [
            "total_cost" => "total_cost",
            "acteur_principal" => "mission_lead",
        ],
    ],
];
