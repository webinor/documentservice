<?php 


return [
    "invoice_provider" => [
        "fields" => [
            "amount" => "amount",
            "acteur_principal" => "provider",
        ],
    ],

    "taxi_paper" => [
        "fields" => [
            "rides" => "rides",
            "demandeur" => "beneficiary",
        ],
    ],

        "fee_note" => [
        "fields" => [
            "amount" => "amount",
            "rides" => "rides",
            "demandeur" => "beneficiary",
        ],
    ],

    "mission_provider" => [
        "fields" => [
            "total_cost" => "total_cost",
            "acteur_principal" => "mission_lead",
        ],
    ],
];
