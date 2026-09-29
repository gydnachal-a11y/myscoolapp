<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAvanceSalaireRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'            => 'required|exists:users,id',
            'mois_scolaire_id'   => 'required|exists:mois_scolaires,id',
            'montant_avance_usd' => 'required|numeric|min:0',
            'date_avance'        => 'required|date',
            'motif'              => 'nullable|string|max:255',
            'commentaire'        => 'nullable|string',
        ];
    }
}