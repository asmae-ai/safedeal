<?php

namespace App\Http\Requests\IdentityVerification;

use Illuminate\Foundation\Http\FormRequest;

class SubmitVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_document'      => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'id_document_type' => ['required', 'in:cin,passport'],
            'selfie'           => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_document.required'      => 'Le document d\'identité est obligatoire.',
            'id_document.mimes'         => 'Le document doit être JPG, PNG ou PDF.',
            'id_document.max'           => 'Le document ne doit pas dépasser 5Mo.',
            'id_document_type.required' => 'Le type de document est obligatoire.',
            'id_document_type.in'       => 'Le type doit être cin ou passport.',
        ];
    }
}