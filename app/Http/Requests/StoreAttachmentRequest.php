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
        $rules = ['selectedCategory' => 'required|string',];

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

        
        // dd("ok");      
        if ($this->selectedCategory=="Payment") {
            $rules += [
                'attachment_number' => 'nullable|string', // max 10MB
            ];
        }
        else if($this->selectedCategory=="Engagment"){

             $rules += [
                'attachment_number' => 'nullable|string', // max 10MB
            ];

        }



        return $rules + [
        'attachmentType' => 'required|exists:attachment_types,id',
        'documentId' => 'required|exists:documents,id',
        'source' => 'required|string',
        // 'selectedCategory' => 'required|string',
        
            
    ];
    }
}
