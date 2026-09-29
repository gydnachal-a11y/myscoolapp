<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\PaiementFraisSupplementaire;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Throwable;

class StorePaiementFraisSupplementaireRequest extends FormRequest
{
    /**
     * Autorisation : super admin bypass + permissions custom.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (!$user) {
            return false;
        }

        // Super admin → bypass total
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        // Permission custom
        if (method_exists($user, 'hasPermission')
            && $user->hasPermission('paiements-frais-supplementaires.create')) {
            return true;
        }

        // Policy Laravel
        try {
            return $user->can('create', PaiementFraisSupplementaire::class);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Normalisation avant validation.
     */
    protected function prepareForValidation(): void
    {
        // Si eleve_id est fourni sans eleve_ids, on unifie
        if ($this->filled('eleve_id') && !$this->filled('eleve_ids')) {
            $this->merge([
                'eleve_ids' => [(int) $this->input('eleve_id')],
            ]);
        }

        // Cast des tableaux d'IDs en entiers uniques
        if ($this->filled('eleve_ids') && is_array($this->input('eleve_ids'))) {
            $this->merge([
                'eleve_ids' => array_values(array_unique(
                    array_map('intval', $this->input('eleve_ids'))
                )),
            ]);
        }

        // Trim du commentaire
        if ($this->filled('commentaire')) {
            $this->merge([
                'commentaire' => trim((string) $this->input('commentaire')) ?: null,
            ]);
        }
    }

    /**
     * Règles de validation.
     */
    public function rules(): array
    {
        return [
            'frais_supplementaire_id' => [
                'required',
                'integer',
                'exists:frais_supplementaires,id',
            ],

            'salle_classe_id' => [
                'required',
                'integer',
                'exists:salles_de_classe,id',
            ],

            // Un des deux est requis (mais prepareForValidation() unifie déjà)
            'eleve_id'  => ['required_without:eleve_ids', 'nullable', 'integer', 'exists:eleves,id'],
            'eleve_ids' => ['required_without:eleve_id',  'nullable', 'array', 'min:1', 'max:100'],

            'eleve_ids.*' => [
                'required',
                'integer',
                'distinct',           // ← pas de doublons
                'exists:eleves,id',
            ],

            'montant_paye_usd' => [
                'required',
                'numeric',
                'min:0.01',
                'max:999999.99',
            ],

            'montant_paye_fc' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.99',
            ],

            'commentaire' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Messages personnalisés.
     */
    public function messages(): array
    {
        return [
            'frais_supplementaire_id.required' => 'Veuillez sélectionner un frais supplémentaire.',
            'frais_supplementaire_id.exists'   => 'Le frais sélectionné n\'existe pas.',

            'salle_classe_id.required' => 'Veuillez sélectionner une salle de classe.',
            'salle_classe_id.exists'   => 'La salle sélectionnée n\'existe pas.',

            'eleve_id.required_without'  => 'Veuillez sélectionner au moins un élève.',
            'eleve_id.exists'            => 'L\'élève sélectionné n\'existe pas.',

            'eleve_ids.required_without' => 'Veuillez sélectionner au moins un élève.',
            'eleve_ids.array'            => 'La liste des élèves est invalide.',
            'eleve_ids.min'              => 'Sélectionnez au moins un élève.',
            'eleve_ids.max'              => 'Vous ne pouvez pas traiter plus de :max élèves à la fois.',
            'eleve_ids.*.distinct'       => 'Un élève est sélectionné plusieurs fois.',
            'eleve_ids.*.exists'         => 'Un des élèves sélectionnés n\'existe pas.',

            'montant_paye_usd.required' => 'Le montant payé est obligatoire.',
            'montant_paye_usd.numeric'  => 'Le montant payé doit être un nombre.',
            'montant_paye_usd.min'      => 'Le montant payé doit être supérieur à zéro.',
            'montant_paye_usd.max'      => 'Le montant payé est trop élevé.',

            'montant_paye_fc.numeric' => 'Le montant en FC doit être un nombre.',
            'montant_paye_fc.min'     => 'Le montant en FC ne peut pas être négatif.',
            'montant_paye_fc.max'     => 'Le montant en FC est trop élevé.',

            'commentaire.max' => 'Le commentaire ne doit pas dépasser :max caractères.',
        ];
    }

    /**
     * Noms lisibles des champs.
     */
    public function attributes(): array
    {
        return [
            'frais_supplementaire_id' => 'frais supplémentaire',
            'salle_classe_id'         => 'salle de classe',
            'eleve_id'                => 'élève',
            'eleve_ids'               => 'élèves',
            'montant_paye_usd'        => 'montant payé (USD)',
            'montant_paye_fc'         => 'montant payé (FC)',
            'commentaire'             => 'commentaire',
        ];
    }
}