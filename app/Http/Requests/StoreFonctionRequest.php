<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFonctionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nom' => 'required|string|max:255|unique:fonctions,nom',
            'description' => 'nullable|string|max:500',
            'section_id' => 'nullable|exists:sections,id',
        ];
    }
}