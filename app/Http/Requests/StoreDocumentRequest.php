<?php

namespace App\Http\Requests;

use App\Models\Misc\DocumentType;
use App\Services\Document\DocumentValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */

    public function rules()
{
    $type = DocumentType::findOrFail(
        $this->document_type_id
    );

    if ($type->reception_mode === 'AUTO_BY_ROLE') {
        return [
            'titre' => 'required',
            'destination' => 'required|exists:folders,id',
            'document_type_id' => 'required|exists:document_types,id',
            'courrier' => 'required|file|max:10240',
        ];
    }

    return DocumentValidationRules::for(
        $type->slug,
        $this
    );
}


}
