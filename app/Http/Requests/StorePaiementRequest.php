<?php

namespace App\Http\Requests;

use App\Models\AnneeScolaire;
use App\Models\Inscription;
use App\Models\Paiement;
use App\Models\SalleDeClasse;
use App\Models\SessionPaiement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaiementRequest extends FormRequest
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
        if (method_exists($user, 'hasPermission') && $user->hasPermission('paiements.create')) {
            return true;
        }

        // 3. Policy Laravel (si elle existe)
        try {
            if ($user->can('create', Paiement::class)) {
                return true;
            }
        } catch (\Throwable) {
            // Policy non définie — on ignore
        }

        return false;
    }

    // ============================================================
    // NORMALISATION AVANT VALIDATION
    // ============================================================

    protected function prepareForValidation(): void
    {
        // Unifie : `eleve_id` seul → `eleve_ids[]`
        if ($this->filled('eleve_id') && !$this->has('eleve_ids')) {
            $this->merge(['eleve_ids' => [(int) $this->input('eleve_id')]]);
        }

        // Cast explicite des champs numériques
        $this->merge([
            'salle_classe_id' => $this->filled('salle_classe_id') ? (int) $this->input('salle_classe_id') : null,
            'periode'         => $this->filled('periode')         ? (int) $this->input('periode')         : null,
        ]);
    }

    // ============================================================
    // RÈGLES DE VALIDATION
    // ============================================================

    public function rules(): array
    {
        return [
            'eleve_ids'    => ['required', 'array', 'min:1', 'max:50'],
            'eleve_ids.*'  => ['required', 'integer', 'exists:eleves,id'],

            'salle_classe_id' => ['required', 'integer', 'exists:salles_de_classe,id'],

            'type_periode' => ['required', Rule::in(Paiement::MODES)],

            'periode' => ['required', 'integer', 'min:1'],

            'montant_attendu_usd' => ['required', 'numeric', 'min:0'],
            'montant_attendu_fc'  => ['required', 'numeric', 'min:0'],
            'montant_paye_usd'    => ['required', 'numeric', 'min:0'],
            'montant_paye_fc'     => ['nullable', 'numeric', 'min:0'],

            'commentaire' => ['nullable', 'string', 'max:500'],
        ];
    }

    // ============================================================
    // VÉRIFICATIONS MÉTIER
    // ============================================================

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Ne rien faire si la base a déjà échoué
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $anneeActive = $this->getAnneeActive();
            if (!$anneeActive) {
                $validator->errors()->add('salle_classe_id', 'Aucune année scolaire active.');
                return;
            }

            // ✅ NOUVEAU : vérifie que la session de paiement est ouverte AVANT tout
            if (!$anneeActive->paiement_ouvert) {
                $validator->errors()->add('paiement', "La session de paiement est fermée pour cette année.");
                return;
            }

            $salle = SalleDeClasse::find($this->input('salle_classe_id'));
            if (!$salle) {
                $validator->errors()->add('salle_classe_id', 'La salle sélectionnée est invalide.');
                return;
            }

            // Cohérence type de période / mode de paiement
            if ($salle->mode_paiement !== $this->input('type_periode')) {
                $validator->errors()->add(
                    'type_periode',
                    'Le type de période ne correspond pas au mode de paiement de la salle.'
                );
                return;
            }

            // Session planifiée existante
            $sessionExiste = SessionPaiement::where([
                'salle_classe_id' => $salle->id,
                'type_periode'    => $this->input('type_periode'),
                'periode'         => (int) $this->input('periode'),
            ])->exists();

            if (!$sessionExiste) {
                $validator->errors()->add(
                    'periode',
                    "Aucune session de paiement n'est planifiée pour cette salle et cette période."
                );
                return;
            }

            // Élèves inscrits
            $eleveIds = array_map('intval', $this->input('eleve_ids', []));

            $inscrits = Inscription::where('annee_scolaire_id', $anneeActive->id)
                ->where('salle_classe_id', $salle->id)
                ->whereIn('eleve_id', $eleveIds)
                ->pluck('eleve_id')
                ->all();

            $nonInscrits = array_diff($eleveIds, $inscrits);
            if (!empty($nonInscrits)) {
                $validator->errors()->add(
                    'eleve_ids',
                    sprintf(
                        "Les élèves suivants ne sont pas inscrits dans cette salle : %s.",
                        implode(', ', $nonInscrits)
                    )
                );
                return;
            }

            // Doublons
            $doublons = Paiement::where('annee_scolaire_id', $anneeActive->id)
                ->where('salle_classe_id', $salle->id)
                ->where('periode', (int) $this->input('periode'))
                ->whereIn('eleve_id', $eleveIds)
                ->pluck('eleve_id')
                ->all();

            if (!empty($doublons)) {
                $validator->errors()->add(
                    'eleve_ids',
                    sprintf(
                        "Un paiement existe déjà pour ces élèves pour cette période : %s.",
                        implode(', ', $doublons)
                    )
                );
                return;
            }

            // Vérification des montants
            $this->verifierMontants($validator, count($eleveIds));
        });
    }

    /**
     * Vérifie la cohérence des montants selon le mode (simple vs groupé).
     */
    private function verifierMontants($validator, int $nbEleves): void
    {
        $montantAttendu = (float) $this->input('montant_attendu_usd');
        $montantPaye    = (float) $this->input('montant_paye_usd');

        if ($nbEleves > 1) {
            // Paiement groupé : montant payé == montant attendu
            if (abs($montantPaye - $montantAttendu) > 0.01) {
                $validator->errors()->add(
                    'montant_paye_usd',
                    sprintf(
                        'Pour un paiement groupé, le montant payé doit être exactement égal au total attendu (%s USD).',
                        number_format($montantAttendu, 2, ',', ' ')
                    )
                );
            }

            return;
        }

        // Paiement simple : montant payé ≤ montant attendu
        if ($montantPaye > $montantAttendu) {
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

    /**
     * Cast final des données validées APRÈS validation réussie.
     * ✅ Utilise le hook officiel Laravel au lieu de surcharger `validated()`.
     */
    protected function passedValidation(): void
    {
        $eleveIds = array_values(
            array_unique(array_map('intval', $this->input('eleve_ids', [])))
        );

        $this->merge([
            'eleve_ids'           => $eleveIds,
            'salle_classe_id'     => (int) $this->input('salle_classe_id'),
            'periode'             => (int) $this->input('periode'),
            'montant_attendu_usd' => (float) $this->input('montant_attendu_usd'),
            'montant_attendu_fc'  => (float) $this->input('montant_attendu_fc'),
            'montant_paye_usd'    => (float) $this->input('montant_paye_usd'),
            'montant_paye_fc'     => $this->filled('montant_paye_fc')
                ? (float) $this->input('montant_paye_fc')
                : null,
        ]);
    }

    // ============================================================
    // MESSAGES & ATTRIBUTS
    // ============================================================

    public function messages(): array
    {
        return [
            'eleve_ids.required'  => 'Veuillez sélectionner au moins un élève.',
            'eleve_ids.array'     => 'La liste des élèves est invalide.',
            'eleve_ids.min'       => 'Veuillez sélectionner au moins un élève.',
            'eleve_ids.max'       => 'Vous ne pouvez pas traiter plus de :max élèves à la fois.',
            'eleve_ids.*.integer' => "Chaque identifiant d'élève doit être un entier.",
            'eleve_ids.*.exists'  => 'Un des élèves sélectionnés est invalide.',

            'salle_classe_id.required' => 'La salle de classe est obligatoire.',
            'salle_classe_id.exists'   => "La salle de classe sélectionnée n'existe pas.",

            'type_periode.required' => 'Le type de période est obligatoire.',
            'type_periode.in'       => 'Le type de période doit être "mensuel" ou "tranche".',

            'periode.required' => 'La période est obligatoire.',
            'periode.integer'  => 'La période doit être un identifiant valide.',
            'periode.min'      => 'La période doit être supérieure à 0.',

            'montant_attendu_usd.required' => 'Le montant attendu en USD est obligatoire.',
            'montant_attendu_usd.numeric'  => 'Le montant attendu en USD doit être numérique.',
            'montant_attendu_usd.min'      => 'Le montant attendu en USD ne peut pas être négatif.',
            'montant_attendu_fc.required'  => 'Le montant attendu en FC est obligatoire.',
            'montant_attendu_fc.numeric'   => 'Le montant attendu en FC doit être numérique.',
            'montant_attendu_fc.min'       => 'Le montant attendu en FC ne peut pas être négatif.',
            'montant_paye_usd.required'    => 'Le montant payé en USD est obligatoire.',
            'montant_paye_usd.numeric'     => 'Le montant payé en USD doit être numérique.',
            'montant_paye_usd.min'         => 'Le montant payé en USD ne peut pas être négatif.',
            'montant_paye_fc.numeric'      => 'Le montant payé en FC doit être numérique.',
            'montant_paye_fc.min'          => 'Le montant payé en FC ne peut pas être négatif.',

            'commentaire.max' => 'Le commentaire ne peut pas dépasser 500 caractères.',
        ];
    }

    public function attributes(): array
    {
        return [
            'eleve_ids'           => 'élèves',
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