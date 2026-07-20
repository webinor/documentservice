<?php

namespace App\Services;


use Illuminate\Support\Facades\Http;

class DocumentViewService
{
    public function getWorkflowStatusStatus($documentId)
    {
     
    $url =  config('services.workflow_service.base_url') . "/documents/{$documentId}/status";

        // 1. finance (mission service)
        // $financial = $mission->calculateSettlementAmount("DEFAULT");

        // 2. workflow (appel microservice workflow)
        $response = Http::withToken(request()->bearerToken())
            ->acceptJson()-> get(
           $url
        );

        if ($response->successful()) {
            return  $workflow = $response->json();

              return [
            // 'document' => $document,
            // 'financial_status' => $financial,
            // 'workflow_status' => $workflow['current_step'] ?? null,
            'transaction_types' => $workflow['transaction_types'] ?? [],
        ];
        } else {


         return response()->json(
                [
                    "error" => "Erreur lors de l’appel au microservice workflow",
                    "url" => $url,
                    "status" => $response->status(),
                    "body" => $response->body(),
                ],
                $response->status()
            );
           
        
        }
        

      
    }
}