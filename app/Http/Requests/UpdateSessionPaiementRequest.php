<?php

namespace App\Http\Requests;

use App\Models\AnneeScolaire;
use App\Models\MoisScolaire;
use App\Models\Paiement;
use App\Models\SessionPaiement;
use App\Models\TrancheScolaire;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSessionPaiementRequest extends FormRequest
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

        // Permission custom
        if (method_exists($user, 'hasPermission') && $user->hasPermission('planification-paiements.edit')) {
            return true;
        }

        // Policy Laravel (si elle existe)
        $session = $this->route('session');

        if ($session instanceof SessionPaiement) {
            try {
                return $user->can('update', $session);
            } catch (\Throwable) {
                // Policy non définie
            }
        }

        // Rôle admin en fallback
        return method_exists($user, 'hasRole') && $user->hasRole('admin');
    }

    // ============================================================
    // NORMALISATION
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
            // Le type et la période sont modifiables (mais vérifiés plus bas)
            'type_periode' => [
                'sometimes',
                Rule::in([Paiement::MODE_MENSUEL, Paiement::MODE_TRANCHE]),
            ],

            'periode' => [
                'sometimes',
                'integer',
                'min:1',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $this->validerPeriode($attribute, $value, $fail);
                },
            ],

            // Les dates sont obligatoires (le formulaire d'édition les envoie toujours)
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

            $session = $this->route('session');

            if (!$session instanceof SessionPaiement) {
                return;
            }

            // 1. Période appartient à l'année active
            if (!$this->verifierPeriodeDansAnneeActive($validator)) {
                return;
            }

            // 2. Unicité (hors session en cours)
            $this->verifierUnicite($validator, $session);
        });
    }

    /**
     * Vérifie que la période existe pour le type sélectionné.
     */
    private function validerPeriode(string $attribute, mixed $value, \Closure $fail): void
    {
        $type = $this->input('type_periode', $this->route('session')?->type_periode);

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
     * Vérifie que la période (nouvelle ou actuelle) appartient à l'année scolaire active.
     *
     * @return bool True si la période est valide, false si une erreur a été ajoutée.
     */
    private function verifierPeriodeDansAnneeActive($validator): bool
    {
        $session = $this->route('session');

        // Si type_periode ou periode n'est pas dans l'input, on garde ceux de la session
        $type    = $this->input('type_periode', $session->type_periode);
        $periode = (int) $this->input('periode', $session->periode);

        $anneeActiveId = AnneeScolaire::where('cloturee', false)
            ->latest('date_debut')
            ->value('id');

        if (!$anneeActiveId) {
            $validator->errors()->add('periode', 'Aucune année scolaire active.');
            return false;
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
            return false;
        }

        return true;
    }

    /**
     * Vérifie qu'aucune autre session n'existe pour la même (salle, type, période).
     *
     * @return bool True si unique, false si un doublon existe.
     */
    private function verifierUnicite($validator, SessionPaiement $session): bool
    {
        // Si ni type_periode ni periode ne sont modifiés, la combinaison reste identique → OK
        $typePeriodeModifie = $this->filled('type_periode');
        $periodeModifiee    = $this->filled('periode');

        if (!$typePeriodeModifie && !$periodeModifiee) {
            return true;
        }

        $type    = $this->input('type_periode', $session->type_periode);
        $periode = (int) $this->input('periode', $session->periode);

        $doublon = SessionPaiement::where('salle_classe_id', $session->salle_classe_id)
            ->where('type_periode', $type)
            ->where('periode', $periode)
            ->whereKeyNot($session->getKey())
            ->exists();

        if ($doublon) {
            $validator->errors()->add(
                'periode',
                "Une autre session existe déjà pour cette salle et cette période."
            );
            return false;
        }

        return true;
    }

    // ============================================================
    // MESSAGES PERSONNALISÉS
    // ============================================================

    public function messages(): array
    {
        return [
            'type_periode.in' => 'Le type de période doit être "mensuel" ou "tranche".',

            'periode.integer' => 'La période doit être un identifiant valide.',
            'periode.min'     => 'La période doit être supérieure à 0.',

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