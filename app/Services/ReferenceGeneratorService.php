<?php

namespace App\Services;

use App\Models\Counter;
use Illuminate\Support\Facades\DB;

class ReferenceGeneratorService
{
    public function generate(string $type): string
    {
        $prefix = strtoupper(substr($type, 0, 3));
        $year = date('Y');

        return DB::transaction(function () use ($type, $prefix, $year) {

            // verrouille la ligne pour éviter concurrence
            $counter = Counter::where('type', $type)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (!$counter) {
                $counter = Counter::create([
                    'type' => $type,
                    'year' => $year,
                    'last_number' => 0
                ]);
            }

            // incrément
            $counter->last_number += 1;
            $counter->save();

            return sprintf(
                '%s-%s-%05d',
                $prefix,
                $year,
                $counter->last_number
            );
        });
    }
}