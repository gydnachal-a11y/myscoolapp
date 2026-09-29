<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNoteRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette requête.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation.
     */
    public function rules(): array
    {
        return [
            'eleve_id' => 'required|exists:eleves,id',
            'cour_salle_id' => 'required|exists:cours_salle,id',
            'periode_note_id' => 'required|exists:periode_notes,id',
            'note' => 'nullable|numeric|min:0',
            'appreciation' => 'nullable|string|max:255',
            'statut' => 'required|in:brouillon,publie',
        ];
    }

    /**
     * Messages personnalisés (optionnel).
     */
    public function messages(): array
    {
        return [
            'eleve_id.required' => 'Veuillez sélectionner un élève.',
            'eleve_id.exists' => 'L\'élève sélectionné est invalide.',
            'cour_salle_id.required' => 'Veuillez sélectionner un cours.',
            'cour_salle_id.exists' => 'Le cours sélectionné est invalide.',
            'periode_note_id.required' => 'Veuillez sélectionner une période.',
            'periode_note_id.exists' => 'La période sélectionnée est invalide.',
            'note.numeric' => 'La note doit être un nombre.',
            'note.min' => 'La note ne peut pas être négative.',
            'appreciation.string' => 'L\'appréciation doit être une chaîne de caractères.',
            'appreciation.max' => 'L\'appréciation ne doit pas dépasser :max caractères.',
            'statut.required' => 'Le statut est obligatoire.',
            'statut.in' => 'Le statut doit être "brouillon" ou "publie".',
        ];
    }
}