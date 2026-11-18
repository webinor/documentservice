<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRequestOrderPaymentScheduleRequest;
use App\Http\Requests\UpdateRequestOrderPaymentScheduleRequest;
use App\Models\RequestOrder;
use App\Models\RequestOrderPaymentSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class RequestOrderPaymentScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    /**
     * Récupère les échéances de paiement d'un bon de commande.
     */
    public function index(RequestOrder $requestOrder): JsonResponse
    {
        $requestOrder->load([
            'initialPaymentSchedule',
            'paymentSchedules',
        ]);

        return response()->json([
            'success' => true,
            'data' => $requestOrder,
            'request_order' => $requestOrder,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreRequestOrderPaymentScheduleRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequestOrderPaymentScheduleRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\RequestOrderPaymentSchedule  $requestOrderPaymentSchedule
     * @return \Illuminate\Http\Response
     */
    public function show(RequestOrderPaymentSchedule $requestOrderPaymentSchedule)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\RequestOrderPaymentSchedule  $requestOrderPaymentSchedule
     * @return \Illuminate\Http\Response
     */
    public function edit(RequestOrderPaymentSchedule $requestOrderPaymentSchedule)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateRequestOrderPaymentScheduleRequest  $request
     * @param  \App\Models\RequestOrderPaymentSchedule  $requestOrderPaymentSchedule
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRequestOrderPaymentScheduleRequest $request, RequestOrderPaymentSchedule $requestOrderPaymentSchedule)
    {
       
        $validated = $request->validated();

        $schedule = DB::transaction(function () use ($requestOrderPaymentSchedule, $validated) {

            $schedule = $requestOrderPaymentSchedule
                ->lockForUpdate()
                ->firstOrFail();

            if (array_key_exists('paid_amount', $validated)) {
                if ((float) $validated['paid_amount'] > (float) $schedule->amount) {
                    abort(422, 'Le montant payé ne peut pas dépasser le montant de l’échéance.');
                }

                $schedule->paid_amount = $validated['paid_amount'];

                $schedule->status = $validated['paid_amount'] == $requestOrderPaymentSchedule->amount ? "PAID" : "PARTIALLY_PAID" ;

            }

            if (array_key_exists('status', $validated)) {
                $schedule->status = $validated['status'];
            }

            $schedule->save();

            return $schedule->fresh();
        });

        return response()->json([
            'success' => true,
            'message' => 'Échéance de paiement mise à jour avec succès.',
            'data' => $schedule,
        ]);
    
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\RequestOrderPaymentSchedule  $requestOrderPaymentSchedule
     * @return \Illuminate\Http\Response
     */
    public function destroy(RequestOrderPaymentSchedule $requestOrderPaymentSchedule)
    {
        //
    }
}
