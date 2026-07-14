<?php

namespace App\Services\Absence;

use App\Models\LeaveType;
use App\Models\Misc\Document;
use App\Services\DocumentType\DocumentTypeHandlerInterface;

class AbsenceService implements DocumentTypeHandlerInterface
{
    public function create(Document $document, array $validated)
    {
        $data = [
            "reason" =>
                $validated["motif"] ??
                (LeaveType::find($validated["type_conge"])->name ?? null),
            "type" => $validated["titre"] ?? null,
            "leave_type_id" => $validated["type_conge"] ?? null,

            /**
             * BASE
             */
            "departure_date" => $this->toDate($validated["dateDepart"] ?? null),

            "departure_time" => $validated["heureDepart"] ?? null,

            "return_date" => $this->toDate($validated["dateRetour"] ?? null),

            "return_time" => $validated["heureRetour"] ?? null,
        ];


        return $document->absence_request()->create($data);
    }

    private function toDate($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            // format frontend: d-m-Y (ex: 02-05-2026)
            return \Carbon\Carbon::createFromFormat("d-m-Y", $value)->format(
                "Y-m-d"
            );
        } catch (\Exception $e) {
            try {
                // fallback si déjà au format Y-m-d ou ISO
                return \Carbon\Carbon::parse($value)->format("Y-m-d");
            } catch (\Exception $e) {
                return null;
            }
        }
    }

    public function all(): array
    {
        return collect(LeaveType::all())
            ->map(function ($leaveType) {
                return [
                    "id" => $leaveType["id"],
                    "code" => $leaveType["code"],
                    "name" => $leaveType["name"],
                    "paid_days" => $leaveType["paid_days"] ?? 0,
                    "uses_balance" => $leaveType["uses_balance"] ?? true,
                    "max_days" => $leaveType["max_days"],
                ];
            })
            ->values()
            ->toArray();
    }
}
