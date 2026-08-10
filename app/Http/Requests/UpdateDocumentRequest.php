<?php

namespace App\Http\Requests;

use App\Models\Misc\Document;
use App\Services\Document\DocumentValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentRequest extends FormRequest
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



    $document = Document::where(
        'uuid',
        $this->route('document')
    )->firstOrFail();

    // throw new \Exception(json_encode($document), 1);

    // throw new \Exception(json_encode($this->all()), 1);


    return DocumentValidationRules::for(
        $document->document_type->slug,
        $this
    );
}
}
