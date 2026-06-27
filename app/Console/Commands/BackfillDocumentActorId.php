<?php

namespace App\Console\Commands;

use App\Models\Misc\Document;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class BackfillDocumentActorId extends Command
{
    protected $signature = 'documents:backfill-actor 
                            {--mode=safe : safe|exec}
                            {--limit=100 : number of documents per batch}';

    protected $description = 'Backfill actor_id in documents using child relation + user-service';

    public function handle()
    {
        $mode = $this->option('mode');
        $limit = (int) $this->option('limit');

        $this->info("Mode: {$mode}");
        // $this->info("limit: {$limit}");

        if (!in_array($mode, ['safe', 'exec'])) {
            $this->error("Mode must be safe or exec");
            return Command::FAILURE;
        }



        Document::with('document_type')
            ->whereNull('actor_id')
            ->chunk($limit, function ($documents) use ($mode) {


                foreach ($documents as $document) {

                    try {
                        $relation = $document->document_type->relation_name ?? null;

                        if (!$relation || !isset($document->$relation)) {
                            $this->warn("Skip document {$document->id} (no relation)");
                            continue;
                        }

                        $child = $document->$relation;

                        // ⚠️ ici ton champ source
                        $userId = $child->actor_id2 ?? null;

                        if (!$userId) {
                            $this->warn("Skip document {$document->id} (no actor_id2)");
                            continue;
                        }

                        // 🔥 call user service
                        $employeeId = $this->fetchEmployeeId($userId);

                        if (!$employeeId) {
                            $this->error("No employee found for user {$userId}");
                            continue;
                        }

                        if ($mode === 'safe') {
                            $this->line("[SAFE] Document {$document->id} => actor_id={$employeeId}");
                            continue;
                        }

                        $document->actor_type = "EMPLOYEE";
                        $document->actor_id = $employeeId;
                        $document->save();

                        $this->info("Updated document {$document->id}");

                    } catch (\Throwable $e) {
                        $this->error("Error doc {$document->id}: " . $e->getMessage());
                    }
                }
            });

        return Command::SUCCESS;
    }

    /**
     * Call user service to map user_id -> employee_id
     */
    private function fetchEmployeeId(int $userId): ?int
    {
        $url = config('services.user_service.base_url');

        $response = Http::timeout(5)
            ->get("{$url}/{$userId}");

        if (!$response->ok()) {
            return null;
        }

        $user = $response->json('user');

        return $user['employee_id'] ?? null;
    }
}