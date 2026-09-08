<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkCalendarRequest;
use App\Http\Requests\UpdateWorkCalendarRequest;
use App\Models\WorkCalendar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkCalendarController extends Controller
{
    /**
     * Liste des calendriers de travail.
     *
     * GET /work-calendars
     */
    public function index(Request $request): JsonResponse
    {
        $query = WorkCalendar::query()
            ->orderBy('name');

        /*
        |--------------------------------------------------------------------------
        | Recherche
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Filtre actif
        |--------------------------------------------------------------------------
        */

        if ($request->has('active')) {
            $query->where(
                'is_active',
                filter_var(
                    $request->active,
                    FILTER_VALIDATE_BOOLEAN
                )
            );
        }

        $workCalendars = $query->get();

        return response()->json([
            'success' => true,
            'data' => $workCalendars,
        ]);
    }


    /**
     * Afficher un calendrier.
     *
     * GET /work-calendars/{workCalendar}
     */
    public function show(
        WorkCalendar $workCalendar
    ): JsonResponse {
        $workCalendar->load('publicHolidays');

        return response()->json([
            'success' => true,
            'data' => $workCalendar,
        ]);
    }


    /**
     * Créer un calendrier.
     *
     * POST /work-calendars
     */
    public function store(
        StoreWorkCalendarRequest $request
    ): JsonResponse {
        $workCalendar = WorkCalendar::create(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Calendrier de travail créé avec succès.',
            'data' => $workCalendar,
        ], 201);
    }


    /**
     * Modifier un calendrier.
     *
     * PUT /work-calendars/{workCalendar}
     */
    public function update(
        UpdateWorkCalendarRequest $request,
        WorkCalendar $workCalendar
    ): JsonResponse {
        $workCalendar->update(
            $request->validated()
        );

        $workCalendar->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Calendrier de travail modifié avec succès.',
            'data' => $workCalendar,
        ]);
    }


    /**
     * Supprimer un calendrier.
     *
     * DELETE /work-calendars/{workCalendar}
     */
    public function destroy(
        WorkCalendar $workCalendar
    ): JsonResponse {
        /*
        |--------------------------------------------------------------------------
        | Empêcher la suppression si des jours fériés existent
        |--------------------------------------------------------------------------
        */

        if ($workCalendar->publicHolidays()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer ce calendrier car des jours fériés y sont associés.',
            ], 422);
        }

        $workCalendar->delete();

        return response()->json([
            'success' => true,
            'message' => 'Calendrier de travail supprimé avec succès.',
        ]);
    }
}