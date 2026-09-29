<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\FraisSupplementaire;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Throwable;

class FraisSupplementaireRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (!$user) {
            return false;
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        if (method_exists($user, 'hasPermission') && $user->hasPermission('frais-supplementaires.manage')) {
            return true;
        }

        try {
            return $user->can('manage', FraisSupplementaire::class);
        } catch (Throwable) {
            return false;
        }
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'est_ouvert'             => $this->boolean('est_ouvert'),
            'est_pour_toutes_salles' => $this->boolean('est_pour_toutes_salles'),
        ]);
    }

    public function rules(): array
    {
        $ignoreId = $this->route('fraisSupplementaire')?->id;

        return [
            'libelle' => [
                'required', 'string', 'max:255',
                Rule::unique('frais_supplementaires', 'libelle')->ignore($ignoreId),
            ],
            'montant'                => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'date_debut'             => ['required', 'date'],
            'date_fin'               => ['required', 'date', 'after_or_equal:date_debut'],
            'description'            => ['nullable', 'string', 'max:1000'],
            'est_ouvert'             => ['boolean'],
            'est_pour_toutes_salles' => ['boolean'],
            'salles' => [
                'nullable', 'array', 'min:1',
                Rule::requiredIf(fn (): bool => !$this->boolean('est_pour_toutes_salles')),
            ],
            'salles.*' => ['integer', 'exists:salles_de_classe,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.required'        => 'Le libellé est obligatoire.',
            'libelle.unique'          => 'Un frais avec ce libellé existe déjà.',
            'montant.required'        => 'Le montant est obligatoire.',
            'montant.numeric'         => 'Le montant doit être numérique.',
            'montant.min'             => 'Le montant ne peut pas être négatif.',
            'date_debut.required'     => 'La date de début est obligatoire.',
            'date_fin.required'       => 'La date de fin est obligatoire.',
            'date_fin.after_or_equal' => 'La date de fin doit être ≥ date de début.',
            'salles.required'         => 'Sélectionnez au moins une salle.',
            'salles.min'              => 'Sélectionnez au moins une salle.',
            'salles.*.exists'         => 'Une ou plusieurs salles sont invalides.',
        ];
    }
}