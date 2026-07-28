<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'currency' => ['sometimes', 'in:MAD'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Le titre est obligatoire.',
            'amount.gt' => 'Le montant doit être supérieur à zéro.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'titre',
            'amount' => 'montant',
            'currency' => 'devise',
        ];
    }
}