<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    // ============================================================
    // AUTORISATION
    // ============================================================

    public function authorize(): bool
    {
        // L'accès est déjà filtré par le middleware `role:super_admin,admin`
        // + middleware `auth:web`. On laisse passer.
        return true;
    }

    // ============================================================
    // PRÉPARATION DES DONNÉES
    // ============================================================

    protected function prepareForValidation(): void
    {
        $normalized = [];

        // Email → minuscules + trim
        if ($this->has('email')) {
            $normalized['email'] = mb_strtolower(trim((string) $this->input('email')));
        }

        // Téléphone → garde uniquement chiffres, +, espaces, tirets
        if ($this->has('telephone')) {
            $telephone = preg_replace('/[^0-9+\s\-]/', '', trim((string) $this->input('telephone')));
            $normalized['telephone'] = $telephone ?: null;
        }

        // Nom → trim + espaces multiples réduits
        if ($this->has('name')) {
            $normalized['name'] = trim(preg_replace('/\s+/', ' ', (string) $this->input('name')));
        }

        // Adresse → trim
        if ($this->has('adresse')) {
            $normalized['adresse'] = trim((string) $this->input('adresse')) ?: null;
        }

        // contact_id → cast int (évite "42" string → null si vide)
        if ($this->has('contact_id')) {
            $contactId = $this->input('contact_id');
            $normalized['contact_id'] = ($contactId === '' || $contactId === null)
                ? null
                : (int) $contactId;
        }

        if (! empty($normalized)) {
            $this->merge($normalized);
        }
    }

    // ============================================================
    // RÈGLES
    // ============================================================

    public function rules(): array
    {
        return [
            // ---------- Identité ----------
            'name' => ['required', 'string', 'min:2', 'max:255'],

            // ---------- Compte ----------
            'email' => [
                'required',
                'email:rfc,dns',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'password' => ['required', 'string', 'min:6', 'confirmed'],

            // Rôle principal : doit correspondre à une constante User::ROLES
            'role' => [
                'required',
                'string',
                'max:30',
                Rule::in(User::ROLES),
            ],

            // Rôles additionnels (many-to-many)
            'roles'   => ['nullable', 'array'],
            'roles.*' => ['integer', 'exists:roles,id'],

            // ---------- Coordonnées ----------
            'sexe'           => ['nullable', Rule::in(['M', 'F'])],
            'adresse'        => ['nullable', 'string', 'max:500'],
            'telephone'      => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\s\-]+$/'],
            'date_naissance' => ['nullable', 'date', 'before:today'],

            // ---------- Photo ----------
            'photo' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:2048',
            ],

            // ---------- Professionnel ----------
            'fonction_id' => ['nullable', 'integer', 'exists:fonctions,id'],
            'section_id'  => ['nullable', 'integer', 'exists:sections,id'],

            // ---------- ✅ Lien avec un compte abonné ----------
            'contact_id' => [
                'nullable',
                'integer',
                'exists:contacts,id',
                Rule::unique('users', 'contact_id'),   // 1 contact = 1 user
            ],
        ];
    }

    // ============================================================
    // MESSAGES PERSONNALISÉS
    // ============================================================

    public function messages(): array
    {
        return [
            // Identité
            'name.required' => 'Le nom complet est obligatoire.',
            'name.min'      => 'Le nom doit contenir au moins :min caractères.',
            'name.max'      => 'Le nom ne doit pas dépasser :max caractères.',

            // Compte
            'email.required' => 'L\'adresse email est obligatoire.',
            'email.email'    => 'L\'adresse email doit être valide.',
            'email.max'      => 'L\'adresse email ne doit pas dépasser :max caractères.',
            'email.unique'   => 'Cette adresse email est déjà utilisée par un autre compte.',

            'password.required'  => 'Le mot de passe est obligatoire.',
            'password.min'       => 'Le mot de passe doit comporter au moins :min caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',

            'role.required' => 'Le rôle principal est obligatoire.',
            'role.in'       => 'Le rôle sélectionné est invalide.',

            'roles.array'   => 'Le format des rôles additionnels est invalide.',
            'roles.*.exists' => 'Un des rôles additionnels sélectionnés n\'existe pas.',

            // Coordonnées
            'sexe.in'           => 'Le sexe doit être « Masculin » ou « Féminin ».',
            'adresse.max'       => 'L\'adresse ne doit pas dépasser :max caractères.',
            'telephone.max'     => 'Le numéro de téléphone ne doit pas dépasser :max caractères.',
            'telephone.regex'   => 'Le numéro de téléphone contient des caractères invalides.',
            'date_naissance.date'   => 'La date de naissance doit être une date valide.',
            'date_naissance.before' => 'La date de naissance doit être dans le passé.',

            // Photo
            'photo.image' => 'Le fichier doit être une image.',
            'photo.mimes' => 'La photo doit être au format :values.',
            'photo.max'   => 'La photo ne doit pas dépasser 2 Mo.',

            // Professionnel
            'fonction_id.exists' => 'La fonction sélectionnée n\'existe pas.',
            'section_id.exists'  => 'La section sélectionnée n\'existe pas.',

            // ✅ Lien abonné
            'contact_id.integer' => 'L\'identifiant du contact est invalide.',
            'contact_id.exists'  => 'Le compte abonné sélectionné n\'existe pas.',
            'contact_id.unique'  => 'Ce compte abonné est déjà lié à un autre membre du personnel.',
        ];
    }

    // ============================================================
    // NOMS D'ATTRIBUTS POUR LES MESSAGES
    // ============================================================

    public function attributes(): array
    {
        return [
            'name'                  => 'nom complet',
            'email'                 => 'adresse email',
            'password'              => 'mot de passe',
            'password_confirmation' => 'confirmation du mot de passe',
            'role'                  => 'rôle principal',
            'roles'                 => 'rôles additionnels',
            'sexe'                  => 'sexe',
            'adresse'               => 'adresse',
            'telephone'             => 'numéro de téléphone',
            'date_naissance'        => 'date de naissance',
            'photo'                 => 'photo',
            'fonction_id'           => 'fonction',
            'section_id'            => 'section',
            'contact_id'            => 'compte abonné',   // ✅ NOUVEAU
        ];
    }
}