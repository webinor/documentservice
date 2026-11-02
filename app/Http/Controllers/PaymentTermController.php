<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PaymentTerm;
use App\Models\PaymentTermInstallment;
use App\Services\PaymentTerms\PaymentInstallmentValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PaymentTermController extends Controller
{
    protected $installmentValidator;

    public function __construct(
        PaymentInstallmentValidator $installmentValidator
    ) {
        $this->installmentValidator = $installmentValidator;
    }

    /**
     * Liste les conditions de paiement.
     */
    public function index(Request $request)
    {
        $query = PaymentTerm::query()
            ->with('installments');

        if ($request->has('active')) {
            $query->where(
                'is_active',
                filter_var(
                    $request->input('active'),
                    FILTER_VALIDATE_BOOLEAN
                )
            );
        }

        $terms = $query
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $terms,
        ]);
    }

    /**
     * Affiche une condition de paiement.
     */
    public function show($id)
    {
        $term = PaymentTerm::with('installments')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $term,
        ]);
    }

    /**
     * Crée une condition de paiement.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                'unique:payment_terms,code',
            ],
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'payment_mode' => [
                'required',
                'string',
                'max:50',
            ],
            'due_days' => 'nullable|integer|min:0|max:65535',
            'is_default' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',

            'installments' => 'required|array|min:1',
            'installments.*.sequence' => 'required|integer|min:1',
            'installments.*.label' => 'required|string|max:150',
            'installments.*.trigger_type' => [
                'required',
                'string',
                Rule::in($this->installmentValidator->allowedTriggers()),
            ],
            'installments.*.days_after_trigger' => 'nullable|integer|min:0|max:65535',
            'installments.*.percentage' => 'required|numeric|gt:0|lte:100',
        ]);

        $this->installmentValidator->validate(
            $validated['installments']
        );

        $this->validateSequences($validated['installments']);

        $user = $request->get('user');

        $term = DB::transaction(function () use ($validated, $user) {
            if (!empty($validated['is_default'])) {
                PaymentTerm::query()->update([
                    'is_default' => false,
                ]);
            }

            $term = PaymentTerm::create([
                'code' => $validated['code'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'payment_mode' => $validated['payment_mode'],
                'due_days' => $validated['due_days'] ?? null,
                'is_default' => $validated['is_default'] ?? false,
                'is_active' => $validated['is_active'] ?? true,
                'created_by' => isset($user['id'])
                    ? $user['id']
                    : null,
            ]);

            $term->installments()->createMany(
                $this->normalizeInstallments($validated['installments'])
            );

            return $term->load('installments');
        });

        return response()->json([
            'success' => true,
            'message' => 'Condition de paiement créée avec succès.',
            'data' => $term,
        ], 201);
    }

    /**
     * Modifie une condition de paiement.
     */
    public function update(Request $request, $id)
    {
        $term = PaymentTerm::findOrFail($id);

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('payment_terms', 'code')->ignore($term->id),
            ],
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'payment_mode' => 'required|string|max:50',
            'due_days' => 'nullable|integer|min:0|max:65535',
            'is_default' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',

            'installments' => 'required|array|min:1',
            'installments.*.sequence' => 'required|integer|min:1',
            'installments.*.label' => 'required|string|max:150',
            'installments.*.trigger_type' => [
                'required',
                'string',
                Rule::in($this->installmentValidator->allowedTriggers()),
            ],
            'installments.*.days_after_trigger' => 'nullable|integer|min:0|max:65535',
            'installments.*.percentage' => 'required|numeric|gt:0|lte:100',
        ]);

        $this->installmentValidator->validate(
            $validated['installments']
        );

        $this->validateSequences($validated['installments']);

        $user = $request->get('user');

        $term = DB::transaction(function () use (
            $term,
            $validated,
            $user
        ) {
            if (!empty($validated['is_default'])) {
                PaymentTerm::where('id', '<>', $term->id)
                    ->update(['is_default' => false]);
            }

            $term->fill([
                'code' => $validated['code'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'payment_mode' => $validated['payment_mode'],
                'due_days' => $validated['due_days'] ?? null,
                'is_default' => $validated['is_default'] ?? false,
                'is_active' => $validated['is_active'] ?? true,
                'updated_by' => isset($user['id'])
                    ? $user['id']
                    : null,
            ]);

            $term->save();

            /*
             * On remplace les échéances du catalogue.
             * Les devis existants ne sont pas affectés,
             * car ils possèdent leurs propres copies.
             */
            $term->installments()->delete();

            $term->installments()->createMany(
                $this->normalizeInstallments($validated['installments'])
            );

            return $term->load('installments');
        });

        return response()->json([
            'success' => true,
            'message' => 'Condition de paiement modifiée avec succès.',
            'data' => $term,
        ]);
    }

    /**
     * Active ou désactive une condition.
     */
    public function toggle(Request $request, $id)
    {
        $term = PaymentTerm::findOrFail($id);

        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $term->is_active = $validated['is_active'];
        $term->updated_by = optional($request->get('user'))['id'] ?? null;
        $term->save();

        return response()->json([
            'success' => true,
            'message' => 'Statut de la condition mis à jour.',
            'data' => $term,
        ]);
    }

    /**
     * Vérifie que les séquences ne sont pas dupliquées.
     */
    private function validateSequences(array $installments)
    {
        $sequences = array_column($installments, 'sequence');

        if (count($sequences) !== count(array_unique($sequences))) {
            abort(422, 'Les numéros de séquence des échéances doivent être uniques.');
        }
    }

    /**
     * Normalise les échéances avant enregistrement.
     */
    private function normalizeInstallments(array $installments)
    {
        return array_map(function ($installment) {
            return [
                'sequence' => $installment['sequence'],
                'label' => $installment['label'],
                'trigger_type' => $installment['trigger_type'],
                'days_after_trigger' => $installment['days_after_trigger'] ?? 0,
                'percentage' => $installment['percentage'],
            ];
        }, $installments);
    }
}