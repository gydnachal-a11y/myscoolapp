<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFonctionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nom' => ['required','string','max:255', Rule::unique('fonctions')->ignore($this->fonction->id)],
            'description' => 'nullable|string|max:500',
            'section_id' => 'nullable|exists:sections,id',
        ];
    }
}