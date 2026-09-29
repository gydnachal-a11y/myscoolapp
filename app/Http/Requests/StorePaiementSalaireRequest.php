<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaiementSalaireRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette requête.
     * Le middleware CheckRole gère déjà l'accès admin, donc on retourne true.
     * Si vous souhaitez une autorisation plus fine (par permissions), utilisez une Policy.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation.
     */
    public function rules(): array
    {
        return [
            'paiements' => 'required|array|min:1',
            'paiements.*.user_id' => 'required|exists:users,id',
            'paiements.*.mois_scolaire_id' => 'required|exists:mois_scolaires,id',
            'paiements.*.montant_paye_usd' => 'required|numeric|min:0',
            'paiements.*.motif_ecart' => 'nullable|string|max:255',
            'paiements.*.motif_ecart_type' => 'nullable|string|in:avance,absence,erreur,sanction,prime,autre',
            'paiements.*.date_paiement' => 'nullable|date',
        ];
    }

    /**
     * Messages personnalisés.
     */
    public function messages(): array
    {
        return [
            'paiements.required' => 'Veuillez fournir au moins un paiement.',
            'paiements.array' => 'Le format des paiements est invalide.',
            'paiements.min' => 'Veuillez fournir au moins un paiement.',

            'paiements.*.user_id.required' => 'Veuillez sélectionner un employé pour chaque paiement.',
            'paiements.*.user_id.exists' => 'L\'employé sélectionné est invalide.',

            'paiements.*.mois_scolaire_id.required' => 'Veuillez sélectionner un mois de paiement.',
            'paiements.*.mois_scolaire_id.exists' => 'Le mois sélectionné est invalide.',

            'paiements.*.montant_paye_usd.required' => 'Le montant payé est obligatoire.',
            'paiements.*.montant_paye_usd.numeric' => 'Le montant payé doit être un nombre.',
            'paiements.*.montant_paye_usd.min' => 'Le montant payé doit être positif ou nul.',

            'paiements.*.motif_ecart.max' => 'Le motif d\'écart ne peut pas dépasser 255 caractères.',

            'paiements.*.motif_ecart_type.in' => 'Le type de motif sélectionné est invalide.',

            'paiements.*.date_paiement.date' => 'La date de paiement doit être une date valide.',
        ];
    }
}