<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttachmentRequest extends FormRequest
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
        $rules = [];

        if ($this->source=="new") {
            $rules += [
                'attachment' => 'required|file|max:10240', // max 10MB
            ];
        }
        else if($this->source=="exist"){

             $rules += [
                'reference' => 'required|string', // max 10MB
            ];

        }


                if ($this->selectedCategory=="Paiement") {
            $rules = [
                'attachment_number' => 'required|string', // max 10MB
            ];
        }
        else if($this->selectedCategory=="Engagement"){

             $rules = [
                'attachment_number' => 'nullable|string', // max 10MB
            ];

        }



        return $rules + [
        'attachmentType' => 'required|exists:attachment_types,id',
        'documentId' => 'required|exists:documents,id',
        'source' => 'required|string',
        'selectedCategory' => 'required|string',
        
            
    ];
    }
}
