<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnneeScolaireRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'libelle' => 'required|string|max:255|unique:annees_scolaires,libelle',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'effectif_attendu' => 'required|integer|min:0',
            'paiement_ouvert' => 'sometimes|boolean',
            'nombre_mois' => 'nullable|integer|min:1|max:24',
            'nombre_tranches' => 'nullable|integer|min:1|max:12',
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.required' => 'Le libellé est obligatoire.',
            'libelle.unique' => 'Ce libellé est déjà utilisé.',
            'date_debut.required' => 'La date de début est obligatoire.',
            'date_fin.required' => 'La date de fin est obligatoire.',
            'date_fin.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
            'effectif_attendu.required' => 'L\'effectif attendu est obligatoire.',
            'nombre_mois.integer' => 'Le nombre de mois doit être un entier.',
            'nombre_mois.min' => 'Le nombre de mois doit être au moins 1.',
            'nombre_mois.max' => 'Le nombre de mois ne peut pas dépasser 24.',
            'nombre_tranches.integer' => 'Le nombre de tranches doit être un entier.',
            'nombre_tranches.min' => 'Le nombre de tranches doit être au moins 1.',
            'nombre_tranches.max' => 'Le nombre de tranches ne peut pas dépasser 12.',
        ];
    }
}