<?php

namespace App\Http\Controllers;

use App\Models\Misc\Document;
use App\Models\Payment;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DocumentPaymentController extends Controller
{
    public function paymentStatus(Document $document)
    {
        $totalAmount = $document->amount;

        $totalPaid = $document->payments()->sum('amount');

        if ($totalPaid <= 0) {
            $status = "WAITING_PAYMENT";
        } elseif ($totalPaid < $totalAmount) {
            $status = "PARTIALLY_PAID";
        } else {
            $status = "PAID";
        }

        return response()->json([
            "document_id" => $document->id,
            "total_amount" => $totalAmount,
            "total_paid" => $totalPaid,
            "status" => $status
        ]);
    }

    public function registerPayment(Request $request, Document $document)
{
    try {
        DB::beginTransaction();
        // Valider les données reçues
        $request->validate([
            'paid_amount' => 'required|numeric|min:0.01',
            'payment_mode' => 'required|string',
            'user_id' => 'required|integer',
            'is_full_pay' => 'sometimes|boolean',
        ]);


        // throw new \Exception(json_encode($request->all()));


        $paid_amount = floatval($request->input('paid_amount'));
        $isFullPay = (bool) $request->input('is_full_pay', false);


        // throw new Exception(json_encode($document->id));



        // Enregistrer le paiement
        $payment = Payment::create([
            'document_id' => $document->id,
            'amount' => $paid_amount,
            'payment_method' => $request->input('payment_mode'),
            'user_id' => $request->input('user_id'),
        ]);

        // dd('payment created');

        // throw new \Exception(json_encode($payment));




        // Recalculer le montant payé
        $totalPaid = $document->payments()->sum('amount');
        // $document->paid_amount = $totalPaid;


        // throw new \Exception(json_encode($totalPaid));


        // Mettre à jour le statut du document ////////////CECI EST DEJA EFFECTUE PAR PaymentObserver
        if ($totalPaid >= $document->amount || $isFullPay) {
            $document->status = 'Payée';
        } elseif ($totalPaid > 0) {
            $document->status = 'Partiellement payée';
        } else {
            $document->status = 'Non payé(e)';
        }

        $document->save();

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Paiement enregistré avec succès.',
            'document' => $document,
        ]);
    } catch (ValidationException $e) {
        DB::rollback();
        // Gestion des erreurs de validation
        return response()->json([
            'success' => false,
            'message' => 'Données invalides pour le paiement.',
            'errors' => $e->errors(),
        ], 422);
    } catch (Exception $e) {
        DB::rollback();
        // Gestion des autres exceptions
        // return response()->json([
        //     'success' => false,
        //     'message' => 'Impossible d’enregistrer le paiement : ' . $e->getMessage(),
        // ], 500);

          return response()->json([
        'success' => false,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ], 500);
    }
}
}
