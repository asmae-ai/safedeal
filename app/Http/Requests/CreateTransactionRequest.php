<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'amount'      => ['required', 'numeric', 'min:1', 'max:999999.99'],
            'currency'    => ['nullable', 'string', 'in:MAD,EUR,USD'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'  => 'Le titre est obligatoire.',
            'title.min'       => 'Le titre doit contenir au moins 3 caractères.',
            'amount.required' => 'Le montant est obligatoire.',
            'amount.min'      => 'Le montant doit être supérieur à 0.',
            'amount.max'      => 'Le montant ne peut pas dépasser 999 999,99.',
            'currency.in'     => 'La devise doit être MAD, EUR ou USD.',
        ];
    }
}