<?php

namespace App\Http\Requests;

use App\Models\AnneeScolaire;
use App\Models\Inscription;
use App\Models\Paiement;
use App\Models\SalleDeClasse;
use App\Models\SessionPaiement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaiementRequest extends FormRequest
{
    // ============================================================
    // CONSTANTES
    // ============================================================

    private const MONTANT_MAX = 999_999_999.99;

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
        if (method_exists($user, 'hasPermission') && $user->hasPermission('paiements.edit')) {
            return true;
        }

        // Policy Laravel (si définie)
        $paiement = $this->route('paiement');

        if ($paiement instanceof Paiement) {
            try {
                return $user->can('update', $paiement);
            } catch (\Throwable) {
                return false;
            }
        }

        return false;
    }

    // ============================================================
    // NORMALISATION
    // ============================================================

    protected function prepareForValidation(): void
    {
        $merged = [];

        // Unifie `eleve_id` → `eleve_ids[]` pour le contrôle d'intégrité
        if ($this->filled('eleve_id') && !$this->has('eleve_ids')) {
            $merged['eleve_ids'] = [(int) $this->input('eleve_id')];
        }

        // Cast explicite
        if ($this->filled('salle_classe_id')) {
            $merged['salle_classe_id'] = (int) $this->input('salle_classe_id');
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
            'eleve_ids'   => ['sometimes', 'array', 'size:1'],
            'eleve_ids.*' => ['sometimes', 'integer', 'exists:eleves,id'],

            'salle_classe_id' => ['sometimes', 'integer', 'exists:salles_de_classe,id'],

            'type_periode' => ['sometimes', Rule::in(Paiement::MODES)],

            'periode' => ['sometimes', 'integer', 'min:1'],

            'montant_attendu_usd' => ['sometimes', 'numeric', 'min:0', 'max:' . self::MONTANT_MAX],
            'montant_attendu_fc'  => ['sometimes', 'numeric', 'min:0', 'max:' . self::MONTANT_MAX],
            'montant_paye_usd'    => ['sometimes', 'numeric', 'min:0', 'max:' . self::MONTANT_MAX],
            'montant_paye_fc'     => ['nullable', 'numeric', 'min:0', 'max:' . self::MONTANT_MAX],

            'commentaire' => ['nullable', 'string', 'max:500'],
        ];
    }

    // ============================================================
    // VÉRIFICATIONS MÉTIER
    // ============================================================

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $paiement = $this->route('paiement');

            if (!$paiement instanceof Paiement) {
                return;
            }

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            // ---- 1) Année active + session ouverte ----
            $anneeActive = $this->getAnneeActive();
            if (!$anneeActive) {
                $validator->errors()->add('salle_classe_id', 'Aucune année scolaire active.');
                return;
            }

            if (!$anneeActive->paiement_ouvert) {
                $validator->errors()->add('paiement', 'La session de paiement est fermée pour cette année.');
                return;
            }

            // ---- 2) L'élève d'un paiement ne peut PAS changer ----
            if (!$this->verifierEleveImmutable($validator, $paiement)) {
                return;
            }

            // ---- 3) Valeurs effectives (input ou valeur existante) ----
            $eleveId     = (int) $paiement->eleve_id;
            $salleId     = (int) $this->input('salle_classe_id', $paiement->salle_classe_id);
            $periode     = (int) $this->input('periode', $paiement->periode);
            $typePeriode = (string) $this->input('type_periode', $paiement->type_periode);

            // ---- 4) Salle valide + cohérence type/mode ----
            $salle = SalleDeClasse::find($salleId);
            if (!$salle) {
                $validator->errors()->add('salle_classe_id', 'La salle sélectionnée est invalide.');
                return;
            }

            if ($salle->mode_paiement !== $typePeriode) {
                $validator->errors()->add(
                    'type_periode',
                    'Le type de période ne correspond pas au mode de paiement de la salle.'
                );
                return;
            }

            // ---- 5) Session planifiée ----
            $sessionExiste = SessionPaiement::where([
                'salle_classe_id' => $salle->id,
                'type_periode'    => $typePeriode,
                'periode'         => $periode,
            ])->exists();

            if (!$sessionExiste) {
                $validator->errors()->add(
                    'periode',
                    "Aucune session de paiement n'est planifiée pour cette salle et cette période."
                );
                return;
            }

            // ---- 6) Élève inscrit dans cette salle (année active) ----
            $inscrit = Inscription::where('eleve_id', $eleveId)
                ->where('annee_scolaire_id', $anneeActive->id)
                ->where('salle_classe_id', $salle->id)
                ->exists();

            if (!$inscrit) {
                $validator->errors()->add(
                    'salle_classe_id',
                    "L'élève n'est pas inscrit dans cette salle pour l'année active."
                );
                return;
            }

            // ---- 7) Doublon (hors paiement en cours) ----
            $doublon = Paiement::where('annee_scolaire_id', $anneeActive->id)
                ->where('salle_classe_id', $salle->id)
                ->where('periode', $periode)
                ->where('eleve_id', $eleveId)
                ->whereKeyNot($paiement->getKey())
                ->exists();

            if ($doublon) {
                $validator->errors()->add(
                    'periode',
                    'Un autre paiement existe déjà pour cet élève, cette salle et cette période.'
                );
                return;
            }

            // ---- 8) Montants ----
            $this->verifierMontants($validator, $paiement);
        });
    }

    /**
     * L'élève d'un paiement ne peut pas être modifié.
     * Retourne false si une erreur a été ajoutée (early return).
     */
    private function verifierEleveImmutable($validator, Paiement $paiement): bool
    {
        $eleveIdSoumis = (int) $this->input('eleve_id', $paiement->eleve_id);

        if ($eleveIdSoumis !== (int) $paiement->eleve_id) {
            $validator->errors()->add(
                'eleve_id',
                "L'élève d'un paiement ne peut pas être modifié. Supprimez et recréez le paiement si nécessaire."
            );
            return false;
        }

        return true;
    }

    /**
     * Vérifie que le montant payé ne dépasse pas le montant attendu.
     */
    private function verifierMontants($validator, Paiement $paiement): void
    {
        $attendu = $this->filled('montant_attendu_usd')
            ? (float) $this->input('montant_attendu_usd')
            : (float) $paiement->montant_attendu_usd;

        $paye = $this->filled('montant_paye_usd')
            ? (float) $this->input('montant_paye_usd')
            : (float) $paiement->montant_paye_usd;

        if ($paye > $attendu) {
            $validator->errors()->add(
                'montant_paye_usd',
                'Le montant payé ne peut pas dépasser le montant attendu.'
            );
        }
    }

    /**
     * Récupère l'année scolaire active.
     */
    private function getAnneeActive(): ?AnneeScolaire
    {
        return AnneeScolaire::where('cloturee', false)
            ->latest('date_debut')
            ->first();
    }

    // ============================================================
    // HOOK POST-VALIDATION — Cast final
    // ============================================================

    protected function passedValidation(): void
    {
        $merged = [];

        if ($this->filled('salle_classe_id')) {
            $merged['salle_classe_id'] = (int) $this->input('salle_classe_id');
        }

        if ($this->filled('periode')) {
            $merged['periode'] = (int) $this->input('periode');
        }

        foreach (['montant_attendu_usd', 'montant_attendu_fc', 'montant_paye_usd'] as $champ) {
            if ($this->filled($champ)) {
                $merged[$champ] = (float) $this->input($champ);
            }
        }

        if ($this->has('montant_paye_fc')) {
            $merged['montant_paye_fc'] = $this->filled('montant_paye_fc')
                ? (float) $this->input('montant_paye_fc')
                : null;
        }

        if (!empty($merged)) {
            $this->merge($merged);
        }
    }

    // ============================================================
    // MESSAGES & ATTRIBUTS
    // ============================================================

    public function messages(): array
    {
        return [
            'eleve_ids.size'      => 'Un paiement correspond à un seul élève.',
            'eleve_ids.*.integer' => "L'identifiant de l'élève doit être un entier.",
            'eleve_ids.*.exists'  => "L'élève sélectionné est invalide.",

            'salle_classe_id.exists' => "La salle de classe sélectionnée n'existe pas.",

            'type_periode.in' => 'Le type de période doit être "mensuel" ou "tranche".',

            'periode.integer' => 'La période doit être un identifiant valide.',
            'periode.min'     => 'La période doit être supérieure à 0.',

            'montant_attendu_usd.numeric' => 'Le montant attendu en USD doit être numérique.',
            'montant_attendu_usd.min'     => 'Le montant attendu en USD ne peut pas être négatif.',
            'montant_attendu_usd.max'     => 'Le montant attendu en USD dépasse la limite autorisée.',
            'montant_attendu_fc.numeric'  => 'Le montant attendu en FC doit être numérique.',
            'montant_attendu_fc.min'      => 'Le montant attendu en FC ne peut pas être négatif.',
            'montant_attendu_fc.max'      => 'Le montant attendu en FC dépasse la limite autorisée.',
            'montant_paye_usd.numeric'    => 'Le montant payé en USD doit être numérique.',
            'montant_paye_usd.min'        => 'Le montant payé en USD ne peut pas être négatif.',
            'montant_paye_usd.max'        => 'Le montant payé en USD dépasse la limite autorisée.',
            'montant_paye_fc.numeric'     => 'Le montant payé en FC doit être numérique.',
            'montant_paye_fc.min'         => 'Le montant payé en FC ne peut pas être négatif.',
            'montant_paye_fc.max'         => 'Le montant payé en FC dépasse la limite autorisée.',

            'commentaire.max' => 'Le commentaire ne peut pas dépasser 500 caractères.',
        ];
    }

    public function attributes(): array
    {
        return [
            'eleve_ids'           => 'élève',
            'salle_classe_id'     => 'salle de classe',
            'type_periode'        => 'type de période',
            'periode'             => 'période',
            'montant_attendu_usd' => 'montant attendu (USD)',
            'montant_attendu_fc'  => 'montant attendu (FC)',
            'montant_paye_usd'    => 'montant payé (USD)',
            'montant_paye_fc'     => 'montant payé (FC)',
        ];
    }
}