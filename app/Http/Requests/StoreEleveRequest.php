<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEleveRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette requête.
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
        // Normaliser le téléphone (supprimer espaces, tirets, etc.)
        if ($this->has('responsables')) {
            $responsables = $this->input('responsables');
            foreach ($responsables as $index => $resp) {
                if (isset($resp['telephone'])) {
                    $responsables[$index]['telephone'] = preg_replace('/[^0-9+]/', '', $resp['telephone']);
                }
                // S'assurer que 'vivant' est un booléen
                if (isset($resp['vivant'])) {
                    $responsables[$index]['vivant'] = filter_var($resp['vivant'], FILTER_VALIDATE_BOOLEAN);
                }
            }
            $this->merge(['responsables' => $responsables]);
        }

        // Normaliser le sexe (majuscule)
        if ($this->has('sexe')) {
            $this->merge(['sexe' => strtoupper(trim($this->input('sexe')))]);
        }
    }

    /**
     * Règles de validation.
     */
    public function rules(): array
    {
        return [
            // Identité
            'nom' => ['required', 'string', 'max:255'],
            'postnom' => ['nullable', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'sexe' => ['required', Rule::in(['M', 'F'])],
            'date_naissance' => ['required', 'date', 'before:today'],
            'lieu_naissance' => ['required', 'string', 'max:255'],
            'adresse' => ['required', 'string', 'max:500'],

            // Photo
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],

            // Santé
            'maladie_chronique' => ['nullable', 'string', 'max:500'],
            'allergies' => ['nullable', 'string', 'max:500'],

            // Responsables
            'responsables' => ['required', 'array', 'min:1'],
            'responsables.*.type' => ['required', Rule::in(['pere', 'mere', 'tuteur'])],
            'responsables.*.nom' => ['required', 'string', 'max:255'],
            'responsables.*.profession' => ['nullable', 'string', 'max:255'],
            'responsables.*.telephone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\s\-]+$/'],
            'responsables.*.vivant' => ['required', 'boolean'],
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom de l\'élève est obligatoire.',
            'nom.max' => 'Le nom ne doit pas dépasser 255 caractères.',

            'prenom.required' => 'Le prénom de l\'élève est obligatoire.',
            'prenom.max' => 'Le prénom ne doit pas dépasser 255 caractères.',

            'postnom.max' => 'Le post-nom ne doit pas dépasser 255 caractères.',

            'sexe.required' => 'Le sexe est obligatoire.',
            'sexe.in' => 'Le sexe doit être "M" (Masculin) ou "F" (Féminin).',

            'date_naissance.required' => 'La date de naissance est obligatoire.',
            'date_naissance.date' => 'La date de naissance doit être une date valide.',
            'date_naissance.before' => 'La date de naissance doit être dans le passé.',

            'lieu_naissance.required' => 'Le lieu de naissance est obligatoire.',
            'lieu_naissance.max' => 'Le lieu de naissance ne doit pas dépasser 255 caractères.',

            'adresse.required' => 'L\'adresse est obligatoire.',
            'adresse.max' => 'L\'adresse ne doit pas dépasser 500 caractères.',

            'photo.image' => 'Le fichier doit être une image.',
            'photo.mimes' => 'La photo doit être au format :values.',
            'photo.max' => 'La photo ne doit pas dépasser 2 Mo.',

            'maladie_chronique.max' => 'La description de la maladie chronique ne doit pas dépasser 500 caractères.',
            'allergies.max' => 'La description des allergies ne doit pas dépasser 500 caractères.',

            'responsables.required' => 'Au moins un responsable est requis.',
            'responsables.min' => 'Au moins un responsable est requis.',
            'responsables.*.type.required' => 'Le type du responsable est obligatoire.',
            'responsables.*.type.in' => 'Le type de responsable doit être "pere", "mere" ou "tuteur".',
            'responsables.*.nom.required' => 'Le nom du responsable est obligatoire.',
            'responsables.*.nom.max' => 'Le nom du responsable ne doit pas dépasser 255 caractères.',
            'responsables.*.profession.max' => 'La profession du responsable ne doit pas dépasser 255 caractères.',
            'responsables.*.telephone.required' => 'Le téléphone du responsable est obligatoire.',
            'responsables.*.telephone.max' => 'Le téléphone du responsable ne doit pas dépasser 20 caractères.',
            'responsables.*.telephone.regex' => 'Le téléphone du responsable contient des caractères invalides.',
            'responsables.*.vivant.required' => 'Vous devez indiquer si le responsable est vivant.',
            'responsables.*.vivant.boolean' => 'Le champ "vivant" doit être vrai ou faux.',
        ];
    }

    /**
     * Renomme les attributs pour les messages d'erreur.
     */
    public function attributes(): array
    {
        return [
            'nom' => 'nom',
            'postnom' => 'post-nom',
            'prenom' => 'prénom',
            'sexe' => 'sexe',
            'date_naissance' => 'date de naissance',
            'lieu_naissance' => 'lieu de naissance',
            'adresse' => 'adresse',
            'photo' => 'photo',
            'maladie_chronique' => 'maladie chronique',
            'allergies' => 'allergies',
            'responsables' => 'responsables',
            'responsables.*.type' => 'type de responsable',
            'responsables.*.nom' => 'nom du responsable',
            'responsables.*.profession' => 'profession du responsable',
            'responsables.*.telephone' => 'téléphone du responsable',
            'responsables.*.vivant' => 'vivant',
        ];
    }
}