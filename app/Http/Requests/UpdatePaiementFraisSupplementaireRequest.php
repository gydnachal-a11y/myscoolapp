<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\PaiementFraisSupplementaire;
use Illuminate\Foundation\Http\FormRequest;
use Throwable;

class UpdatePaiementFraisSupplementaireRequest extends FormRequest
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
            && $user->hasPermission('paiements-frais-supplementaires.update')) {
            return true;
        }

        // Policy Laravel sur le paiement
        try {
            $paiement = $this->route('paiement');
            if ($paiement instanceof PaiementFraisSupplementaire) {
                return $user->can('update', $paiement);
            }
        } catch (Throwable) {
            return false;
        }

        return false;
    }

    /**
     * Normalisation avant validation.
     */
    protected function prepareForValidation(): void
    {
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

            'eleve_id' => [
                'required',
                'integer',
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

            'eleve_id.required' => 'Veuillez sélectionner un élève.',
            'eleve_id.exists'   => 'L\'élève sélectionné n\'existe pas.',

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
            'montant_paye_usd'        => 'montant payé (USD)',
            'montant_paye_fc'         => 'montant payé (FC)',
            'commentaire'             => 'commentaire',
        ];
    }
}