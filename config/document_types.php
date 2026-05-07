<?php 


return [
    "invoice_provider" => [
        "fields" => [
            "amount" => "amount",
            "prestataire" => "provider",
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

        // 🆕 MISSION
    "mission" => [
        "fields" => [
            // 🧭 infos principales mission
            "title" => "title",
            "destination" => "destination",
            "start_date" => "start_date",
            "end_date" => "end_date",

            // 💰 budget global
            "estimated_budget" => "estimated_budget",
            "advance_amount" => "advance_amount",

            // 👤 acteur principal (interne/externe)
            "actor_type" => "actor_type",
            "actor_id" => "actor_id",

            // "actor" => [ "type" => "actor_type",
            // "id" => "actor_id",],

            // ⚙️ contexte métier
            "is_special" => "is_special",
            "description" => "description",
        ],
    ],
];
