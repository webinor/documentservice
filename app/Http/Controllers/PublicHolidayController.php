<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePublicHolidayRequest;
use App\Http\Requests\UpdatePublicHolidayRequest;
use App\Models\PublicHoliday;
use App\Models\WorkCalendar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PublicHolidayController extends Controller
{
    /**
     * Liste des jours fériés
     *
     * GET /public-holidays
     *
     * Filtres :
     * ?work_calendar_id=1
     * ?year=2026
     */
    public function index(Request $request): JsonResponse
    {
        $query = PublicHoliday::query()
            ->with('workCalendar')
            ->orderBy('date')
            ->orderBy('name');

        /*
        |--------------------------------------------------------------------------
        | Filtre calendrier
        |--------------------------------------------------------------------------
        */

        if ($request->filled('work_calendar_id')) {
            $query->where(
                'work_calendar_id',
                $request->work_calendar_id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filtre année
        |--------------------------------------------------------------------------
        */

        if ($request->filled('year')) {
            $year = (int) $request->year;

            $query->whereYear('date', $year);
        }

        /*
        |--------------------------------------------------------------------------
        | Recherche
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $holidays = $query->get();

        return response()->json([
            'success' => true,
            'data' => $holidays,
        ]);
    }


    /**
     * Afficher un jour férié
     *
     * GET /public-holidays/{publicHoliday}
     */
    public function show(PublicHoliday $publicHoliday): JsonResponse
    {
        $publicHoliday->load('workCalendar');

        return response()->json([
            'success' => true,
            'data' => $publicHoliday,
        ]);
    }


    /**
     * Créer un jour férié
     *
     * POST /public-holidays
     */
    public function store(StorePublicHolidayRequest $request): JsonResponse
    {
        $validated = $request->validated();

        /*
        |--------------------------------------------------------------------------
        | Valeurs par défaut
        |--------------------------------------------------------------------------
        */

        $validated['counts_for_leave'] =
            $validated['counts_for_leave'] ?? false;

        $validated['is_recurring'] =
            $validated['is_recurring'] ?? false;

        /*
        |--------------------------------------------------------------------------
        | Vérifier les doublons
        |--------------------------------------------------------------------------
        */

        $exists = PublicHoliday::where(
            'work_calendar_id',
            $validated['work_calendar_id']
        )
            ->whereDate('date', $validated['date'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Un jour férié existe déjà à cette date pour ce calendrier.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Création
        |--------------------------------------------------------------------------
        */

        $publicHoliday = PublicHoliday::create($validated);

        $publicHoliday->load('workCalendar');

        return response()->json([
            'success' => true,
            'message' => 'Jour férié créé avec succès.',
            'data' => $publicHoliday,
        ], 201);
    }


    /**
     * Modifier un jour férié
     *
     * PUT /public-holidays/{publicHoliday}
     */
    public function update(
        UpdatePublicHolidayRequest $request,
        PublicHoliday $publicHoliday
    ): JsonResponse {

        $validated = $request->validated();

        /*
        |--------------------------------------------------------------------------
        | Vérifier les doublons
        |--------------------------------------------------------------------------
        |
        | On exclut le jour férié actuellement modifié.
        |
        */

        $exists = PublicHoliday::where(
            'work_calendar_id',
            $validated['work_calendar_id']
        )
            ->whereDate('date', $validated['date'])
            ->where('id', '!=', $publicHoliday->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Un autre jour férié existe déjà à cette date pour ce calendrier.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Valeurs par défaut
        |--------------------------------------------------------------------------
        */

        $validated['counts_for_leave'] =
            $validated['counts_for_leave'] ?? false;

        $validated['is_recurring'] =
            $validated['is_recurring'] ?? false;

        /*
        |--------------------------------------------------------------------------
        | Mise à jour
        |--------------------------------------------------------------------------
        */

        $publicHoliday->update($validated);

        $publicHoliday->refresh();

        $publicHoliday->load('workCalendar');

        return response()->json([
            'success' => true,
            'message' => 'Jour férié modifié avec succès.',
            'data' => $publicHoliday,
        ]);
    }


    /**
     * Supprimer un jour férié
     *
     * DELETE /public-holidays/{publicHoliday}
     */
    public function destroy(
        PublicHoliday $publicHoliday
    ): JsonResponse {

        $publicHoliday->delete();

        return response()->json([
            'success' => true,
            'message' => 'Jour férié supprimé avec succès.',
        ]);
    }
}
