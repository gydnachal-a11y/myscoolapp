<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Models\AnneeScolaire;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Models\SalleDeClasse;

class UpdateInscriptionRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prépare les données avant validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('date_inscription')) {
            $this->merge([
                'date_inscription' => Carbon::parse($this->date_inscription)->format('Y-m-d'),
            ]);
        }

        if ($this->has('redoublement')) {
            $this->merge([
                'redoublement' => filter_var($this->redoublement, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        if ($this->filled('reduction_frais') && is_numeric($this->reduction_frais)) {
            $this->merge([
                'reduction_frais' => round((float) $this->reduction_frais, 2),
            ]);
        }

        if (!$this->has('action')) {
            $this->merge(['action' => 'save']);
        }
    }

    /**
     * Règles de validation.
     */
    public function rules(): array
    {
        $inscriptionId = $this->route('inscription')->id;

        return [
            'eleve_id' => [
                'required',
                'exists:eleves,id',
            ],
            'annee_scolaire_id' => [
                'required',
                'exists:annees_scolaires,id',
                $this->anneeScolaireActiveRule(),
            ],
            'salle_classe_id' => [
                'required',
                'exists:salles_de_classe,id',
                $this->salleCompatibleWithAnneeRule(),
            ],
            'date_inscription' => [
                'required',
                'date',
                'before_or_equal:today',
                $this->dateInscriptionValidRule(),
            ],
            'reduction_frais' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99',
            ],
            'action' => [
                'nullable',
                'string',
                Rule::in(['save', 'force']),
            ],
            'redoublement' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    /**
     * Messages de validation.
     */
    public function messages(): array
    {
        return [
            'eleve_id.required' => 'Veuillez sélectionner un élève.',
            'eleve_id.exists' => "L'élève sélectionné n'existe pas.",

            'annee_scolaire_id.required' => "L'année scolaire est obligatoire.",
            'annee_scolaire_id.exists' => "L'année scolaire sélectionnée est invalide.",

            'salle_classe_id.required' => 'Veuillez sélectionner une salle de classe.',
            'salle_classe_id.exists' => "La salle de classe sélectionnée n'existe pas.",

            'date_inscription.required' => "La date d'inscription est obligatoire.",
            'date_inscription.date' => "La date d'inscription doit être valide.",
            'date_inscription.before_or_equal' => "La date d'inscription ne peut pas être dans le futur.",

            'reduction_frais.numeric' => 'La réduction doit être un nombre.',
            'reduction_frais.min' => 'La réduction ne peut pas être négative.',
            'reduction_frais.max' => 'La réduction ne peut pas dépasser 999 999,99 $.',

            'action.in' => "L'action spécifiée est invalide.",

            'redoublement.boolean' => 'Le champ redoublement doit être vrai ou faux.',
        ];
    }

    /**
     * Attributs personnalisés.
     */
    public function attributes(): array
    {
        return [
            'eleve_id' => 'élève',
            'annee_scolaire_id' => 'année scolaire',
            'salle_classe_id' => 'salle de classe',
            'date_inscription' => "date d'inscription",
            'reduction_frais' => 'réduction',
            'action' => 'action',
            'redoublement' => 'redoublement',
        ];
    }

    /**
     * Vérifications additionnelles.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $inscriptionId = $this->route('inscription')->id;
            $salle = SalleDeClasse::with(['section.session'])->find($this->salle_classe_id);
            $annee = AnneeScolaire::find($this->annee_scolaire_id);
            $eleve = Eleve::find($this->eleve_id);

            if (!$salle || !$annee || !$eleve) {
                return;
            }

            // Vérifier la capacité (en excluant l'inscription actuelle)
            $this->validateRoomCapacity($validator, $salle, $annee, $inscriptionId);

            // Vérifier le doublon (en excluant l'inscription actuelle)
            $this->validateUniqueInscription($validator, $eleve, $annee, $inscriptionId);

            // Vérifier l'âge
            $this->validateAge($validator, $eleve, $salle);

            // Vérifier la cohérence de la salle avec l'année
            $this->validateSalleAnneeConsistency($validator, $salle, $annee);
        });
    }

    /**
     * Vérifie la capacité de la salle (en excluant l'inscription actuelle).
     */
    private function validateRoomCapacity($validator, SalleDeClasse $salle, AnneeScolaire $annee, int $excludeId): void
    {
        $count = Inscription::where('salle_classe_id', $salle->id)
            ->where('annee_scolaire_id', $annee->id)
            ->where('id', '!=', $excludeId)
            ->count();

        if ($count >= $salle->capacite_max) {
            $validator->errors()->add(
                'salle_classe_id',
                sprintf(
                    "Capacité maximale atteinte (%d/%d).",
                    $salle->capacite_max,
                    $salle->capacite_max
                )
            );
        }
    }

    /**
     * Vérifie le doublon d'inscription (en excluant l'inscription actuelle).
     */
    private function validateUniqueInscription($validator, Eleve $eleve, AnneeScolaire $annee, int $excludeId): void
    {
        $exists = Inscription::where('eleve_id', $eleve->id)
            ->where('annee_scolaire_id', $annee->id)
            ->where('id', '!=', $excludeId)
            ->exists();

        if ($exists) {
            $validator->errors()->add(
                'eleve_id',
                "L'élève {$eleve->nom} {$eleve->prenom} est déjà inscrit pour cette année."
            );
        }
    }

    /**
     * Vérifie l'âge de l'élève par rapport à la salle.
     */
    private function validateAge($validator, Eleve $eleve, SalleDeClasse $salle): void
    {
        if (!$eleve->date_naissance) {
            $validator->errors()->add('eleve_id', "Date de naissance de l'élève manquante.");
            return;
        }

        $age = $eleve->date_naissance->age;
        $horsTranche = ($age < $salle->age_min || $age > $salle->age_max);
        $force = $this->action === 'force';
        $redoublement = (bool) $this->redoublement;

        if ($horsTranche && !$force && !$redoublement) {
            $validator->errors()->add(
                'alerte_age',
                sprintf(
                    "Âge de l'élève (%d ans) hors tranche (%d-%d ans).",
                    $age,
                    $salle->age_min,
                    $salle->age_max
                )
            );
        }
    }

    /**
     * Vérifie que la salle est compatible avec l'année scolaire.
     */
    private function validateSalleAnneeConsistency($validator, SalleDeClasse $salle, AnneeScolaire $annee): void
    {
        $sessionAnneeId = $salle->section?->session?->annee_scolaire_id ?? null;

        if ($sessionAnneeId && $sessionAnneeId != $annee->id) {
            $validator->errors()->add(
                'salle_classe_id',
                "Cette salle n'est pas rattachée à l'année scolaire sélectionnée."
            );
        }
    }

    /**
     * Règle : année scolaire active.
     */
    private function anneeScolaireActiveRule(): \Closure
    {
        return function ($attribute, $value, $fail) {
            $annee = AnneeScolaire::find($value);
            if ($annee && $annee->cloturee) {
                $fail("L'année scolaire est clôturée, les modifications ne sont plus possibles.");
            }
        };
    }

    /**
     * Règle : la salle doit être compatible avec l'année.
     */
    private function salleCompatibleWithAnneeRule(): \Closure
    {
        return function ($attribute, $value, $fail) {
            $salle = SalleDeClasse::with(['section.session'])->find($value);
            $anneeId = $this->input('annee_scolaire_id');

            if (!$salle || !$anneeId) {
                return;
            }

            $sessionAnneeId = $salle->section?->session?->annee_scolaire_id ?? null;

            if ($sessionAnneeId && $sessionAnneeId != $anneeId) {
                $fail("Cette salle n'est pas rattachée à l'année sélectionnée.");
            }
        };
    }

    /**
     * Règle : date d'inscription cohérente.
     */
    private function dateInscriptionValidRule(): \Closure
    {
        return function ($attribute, $value, $fail) {
            $date = Carbon::parse($value);
            if ($date->diffInYears(now()) > 2) {
                $fail("La date d'inscription ne peut pas dater de plus de 2 ans.");
            }

            if ($this->filled('annee_scolaire_id')) {
                $annee = AnneeScolaire::find($this->annee_scolaire_id);
                if ($annee && $date->diffInMonths(Carbon::parse($annee->date_debut)) > 12) {
                    $fail("La date d'inscription est trop éloignée du début de l'année scolaire.");
                }
            }
        };
    }

    /**
     * Données validées nettoyées.
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        if (isset($validated['reduction_frais'])) {
            $validated['reduction_frais'] = (float) $validated['reduction_frais'];
        }
        if (isset($validated['redoublement'])) {
            $validated['redoublement'] = (bool) $validated['redoublement'];
        }

        return $validated;
    }
}