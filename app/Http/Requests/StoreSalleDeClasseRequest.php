<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalleDeClasseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => 'required|string|max:255',
            'section_id' => 'required|exists:sections,id',
            'option_id' => 'nullable|exists:options,id',
            'capacite_max' => 'required|integer|min:1',
            'frais_inscription' => 'required|numeric|min:0',
            'frais_annuel' => 'required|numeric|min:0',
            'age_min' => 'required|integer|min:0',
            'age_max' => 'required|integer|gte:age_min',
            'description' => 'nullable|string',
            'salle_superieure_id' => 'nullable|exists:salles_de_classe,id',
            'mode_paiement' => 'required|in:mensuel,tranche',
            'frais_scolarite_mensuel' => 'nullable|numeric|min:0',
            'frais_par_tranche' => 'nullable|numeric|min:0',   // ajouté
            'nombre_tranches' => 'nullable|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom est obligatoire.',
            'section_id.required' => 'La section est obligatoire.',
            'capacite_max.required' => 'La capacité maximale est obligatoire.',
            'frais_inscription.required' => 'Les frais d\'inscription sont obligatoires.',
            'frais_annuel.required' => 'Les frais annuels sont obligatoires.',
            'age_min.required' => 'L\'âge minimum est obligatoire.',
            'age_max.required' => 'L\'âge maximum est obligatoire.',
            'age_max.gte' => 'L\'âge maximum doit être supérieur ou égal à l\'âge minimum.',
            'mode_paiement.required' => 'Le mode de paiement est obligatoire.',
            'frais_par_tranche.numeric' => 'Le montant par tranche doit être un nombre.',
            'frais_par_tranche.min' => 'Le montant par tranche ne peut pas être négatif.',
        ];
    }
}