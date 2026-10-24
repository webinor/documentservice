<?php

namespace App\Http\Requests;


use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseSupplierQuoteRequest extends FormRequest
{
    /**
     * Autorise la requête.
     *
     * Les permissions métier doivent être vérifiées
     * par le middleware ou la Policy appropriée.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Aucun champ métier n'est requis pour initialiser
     * un devis vide.
     *
     * @return array
     */
    public function rules()
    {
        return [];
    }
}