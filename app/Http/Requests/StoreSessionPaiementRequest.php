<?php

namespace App\Http\Requests;

use App\Models\AnneeScolaire;
use App\Models\MoisScolaire;
use App\Models\Paiement;
use App\Models\TrancheScolaire;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSessionPaiementRequest extends FormRequest
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

        // Super admin
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        // Permission custom (optionnel)
        if (method_exists($user, 'hasPermission') && $user->hasPermission('planification-paiements.create')) {
            return true;
        }

        // Rôle admin
        return method_exists($user, 'hasRole') && $user->hasRole('admin');
    }

    // ============================================================
    // NORMALISATION AVANT VALIDATION
    // ============================================================

    protected function prepareForValidation(): void
    {
        $merged = [];

        if ($this->filled('type_periode')) {
            $merged['type_periode'] = trim((string) $this->input('type_periode'));
        }

        if ($this->filled('periode')) {
            $merged['periode'] = (int) $this->input('periode');
        }

        if (!empty($merged)) {
            $this->merge($merged);
        }
    }

    // ============================================================
    // RÈGLES DE VALIDATION
    // ============================================================

    public function rules(): array
    {
        return [
            'type_periode' => [
                'required',
                Rule::in([Paiement::MODE_MENSUEL, Paiement::MODE_TRANCHE]),
            ],

            'periode' => [
                'required',
                'integer',
                'min:1',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $this->validerPeriode($attribute, $value, $fail);
                },
            ],

            'date_debut_session' => ['required', 'date'],
            'date_fin_session'   => ['required', 'date', 'after_or_equal:date_debut_session'],
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

            $this->verifierPeriodeDansAnneeActive($validator);
        });
    }

    /**
     * Vérifie que la période existe pour le type sélectionné.
     */
    private function validerPeriode(string $attribute, mixed $value, \Closure $fail): void
    {
        $type = $this->input('type_periode');

        $existe = match ($type) {
            Paiement::MODE_MENSUEL => MoisScolaire::whereKey($value)->exists(),
            Paiement::MODE_TRANCHE => TrancheScolaire::whereKey($value)->exists(),
            default                => false,
        };

        if (!$existe) {
            $fail("La période sélectionnée n'existe pas pour ce type.");
        }
    }

    /**
     * Vérifie que la période appartient à l'année scolaire active.
     */
    private function verifierPeriodeDansAnneeActive($validator): void
    {
        $type    = $this->input('type_periode');
        $periode = (int) $this->input('periode');

        $anneeActiveId = AnneeScolaire::where('cloturee', false)
            ->latest('date_debut')
            ->value('id');

        if (!$anneeActiveId) {
            $validator->errors()->add('periode', 'Aucune année scolaire active.');
            return;
        }

        $appartient = match ($type) {
            Paiement::MODE_MENSUEL => MoisScolaire::whereKey($periode)
                                        ->where('annee_scolaire_id', $anneeActiveId)
                                        ->exists(),
            Paiement::MODE_TRANCHE => TrancheScolaire::whereKey($periode)
                                        ->where('annee_scolaire_id', $anneeActiveId)
                                        ->exists(),
            default                => false,
        };

        if (!$appartient) {
            $validator->errors()->add(
                'periode',
                "Cette période n'appartient pas à l'année scolaire active."
            );
        }
    }

    // ============================================================
    // MESSAGES PERSONNALISÉS
    // ============================================================

    public function messages(): array
    {
        return [
            'type_periode.required' => 'Le type de période est obligatoire.',
            'type_periode.in'       => 'Le type de période doit être "mensuel" ou "tranche".',

            'periode.required' => 'La période est obligatoire.',
            'periode.integer'  => 'La période doit être un identifiant valide.',
            'periode.min'      => 'La période doit être supérieure à 0.',

            'date_debut_session.required'     => 'La date de début de session est obligatoire.',
            'date_debut_session.date'         => 'La date de début doit être une date valide.',
            'date_fin_session.required'       => 'La date de fin de session est obligatoire.',
            'date_fin_session.date'           => 'La date de fin doit être une date valide.',
            'date_fin_session.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
        ];
    }

    public function attributes(): array
    {
        return [
            'type_periode'       => 'type de période',
            'periode'            => 'période',
            'date_debut_session' => 'date de début',
            'date_fin_session'   => 'date de fin',
        ];
    }
}