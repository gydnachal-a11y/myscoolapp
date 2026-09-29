<?php

namespace App\Http\Requests;

use App\Models\PaiementSalaire;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaiementSalaireRequest extends FormRequest
{
    // ============================================================
    // AUTORISATION
    // ============================================================

    public function authorize(): bool
    {
        $user = $this->user();

        if (!$user) {
            return false;
        }

        // 1. Super admin
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        // 2. Permission custom
        if (method_exists($user, 'hasPermission') && $user->hasPermission('paiement-salaires.edit')) {
            return true;
        }

        // 3. Policy Laravel (si elle existe)
        $paiement = $this->route('paiementSalaire')
            ?? $this->route('paiement_salaire')
            ?? $this->route('paiement');

        if ($paiement instanceof PaiementSalaire) {
            try {
                if ($user->can('update', $paiement)) {
                    return true;
                }
            } catch (\Throwable) {
                // Policy non définie — on ignore
            }
        }

        return false;
    }

    // ============================================================
    // NORMALISATION AVANT VALIDATION
    // ============================================================

    protected function prepareForValidation(): void
    {
        $this->merge([
            'user_id'          => $this->filled('user_id')          ? (int) $this->input('user_id')          : null,
            'mois_scolaire_id' => $this->filled('mois_scolaire_id') ? (int) $this->input('mois_scolaire_id') : null,
            'montant_paye_usd' => $this->filled('montant_paye_usd') ? (float) $this->input('montant_paye_usd') : null,
        ]);
    }

    // ============================================================
    // RÈGLES DE VALIDATION
    // ============================================================

    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
            'mois_scolaire_id' => [
                'required',
                'integer',
                'exists:mois_scolaires,id',
            ],
            'montant_paye_usd' => [
                'required',
                'numeric',
                'min:0',
            ],
            'motif_ecart' => [
                'nullable',
                'string',
                'max:255',
            ],
            'motif_ecart_type' => [
                'nullable',
                Rule::in(['avance', 'absence', 'erreur', 'sanction', 'prime', 'autre']),
            ],
            'date_paiement' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
        ];
    }

    // ============================================================
    // VÉRIFICATIONS MÉTIER
    // ============================================================

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $paiement = $this->route('paiementSalaire')
                ?? $this->route('paiement_salaire')
                ?? $this->route('paiement');

            if (!$paiement instanceof PaiementSalaire) {
                return;
            }

            // Unicité (hors paiement courant)
            $existe = PaiementSalaire::query()
                ->where('user_id', $this->input('user_id'))
                ->where('mois_scolaire_id', $this->input('mois_scolaire_id'))
                ->whereKeyNot($paiement->getKey())
                ->exists();

            if ($existe) {
                $validator->errors()->add(
                    'user_id',
                    'Un autre paiement existe déjà pour cet employé et ce mois.'
                );
                return;
            }

            // Si un motif d'écart est requis (le service validera aussi côté serveur)
            $motifType = $this->input('motif_ecart_type');
            $motifText = $this->input('motif_ecart');

            if ($motifType === 'autre' && blank($motifText)) {
                $validator->errors()->add(
                    'motif_ecart',
                    "Veuillez préciser le motif d'écart."
                );
            }
        });
    }

    // ============================================================
    // MESSAGES PERSONNALISÉS
    // ============================================================

    public function messages(): array
    {
        return [
            'user_id.required' => 'Veuillez sélectionner un employé.',
            'user_id.exists'   => "L'employé sélectionné est invalide.",

            'mois_scolaire_id.required' => 'Veuillez sélectionner un mois de paiement.',
            'mois_scolaire_id.exists'   => 'Le mois sélectionné est invalide.',

            'montant_paye_usd.required' => 'Le montant payé est obligatoire.',
            'montant_paye_usd.numeric'  => 'Le montant payé doit être un nombre.',
            'montant_paye_usd.min'      => 'Le montant payé doit être positif ou nul.',

            'motif_ecart.max' => "Le motif d'écart ne peut pas dépasser 255 caractères.",

            'motif_ecart_type.in' => 'Le type de motif sélectionné est invalide.',

            'date_paiement.required'          => 'La date de paiement est obligatoire.',
            'date_paiement.date'              => 'La date de paiement doit être une date valide.',
            'date_paiement.before_or_equal'   => 'La date de paiement ne peut pas être dans le futur.',
        ];
    }

    public function attributes(): array
    {
        return [
            'user_id'          => 'employé',
            'mois_scolaire_id' => 'mois scolaire',
            'montant_paye_usd' => 'montant payé',
            'motif_ecart'      => "motif d'écart",
            'motif_ecart_type' => 'type de motif',
            'date_paiement'    => 'date de paiement',
        ];
    }
}