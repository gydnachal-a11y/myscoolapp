<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ContactController extends Controller
{
    /**
     * Affiche la liste des abonnés.
     */
    public function index(Request $request)
    {
        $query = Contact::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('telephone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('est_responsable')) {
            $query->where('est_responsable', $request->est_responsable == '1');
        }

        $contacts = $query->orderByDesc('created_at')->paginate(20);

        $totalAbonnes = Contact::count();
        $responsablesCount = Contact::where('est_responsable', true)->count();
        $nonResponsablesCount = Contact::where('est_responsable', false)->count();
        $inscritsCeMois = Contact::where('created_at', '>=', now()->startOfMonth())->count();

        return view('admin.contacts.index', compact(
            'contacts',
            'totalAbonnes',
            'responsablesCount',
            'nonResponsablesCount',
            'inscritsCeMois'
        ));
    }

    /**
     * Affiche le formulaire de création d'un abonné.
     */
    public function create()
    {
        return view('admin.contacts.create');
    }

    /**
     * Enregistre un nouvel abonné.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'email' => 'required|email|unique:contacts,email',
            'telephone' => 'nullable|string|max:20|unique:contacts,telephone',
            'password' => 'required|string|min:8|confirmed',
            'est_responsable' => 'sometimes|boolean',
        ]);

        Contact::create([
            'nom' => $validated['nom'],
            'email' => $validated['email'],
            'telephone' => $validated['telephone'] ?? null,
            'password' => Hash::make($validated['password']),
            'est_responsable' => $request->boolean('est_responsable'),
        ]);

        return redirect()->route('admin.contacts.index')->with('success', 'Abonné créé avec succès.');
    }

    /**
     * Affiche les détails d'un abonné.
     */
    public function show(Contact $contact)
    {
        return view('admin.contacts.show', compact('contact'));
    }

    /**
     * Affiche le formulaire d'édition d'un abonné.
     */
    public function edit(Contact $contact)
    {
        return view('admin.contacts.edit', compact('contact'));
    }

    /**
     * Met à jour un abonné.
     */
    public function update(Request $request, Contact $contact)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'email' => 'required|email|unique:contacts,email,' . $contact->id,
            'telephone' => 'nullable|string|max:20|unique:contacts,telephone,' . $contact->id,
            'est_responsable' => 'sometimes|boolean',
        ]);

        $contact->update($validated);

        return redirect()->route('admin.contacts.index')->with('success', 'Abonné mis à jour.');
    }

    /**
     * Supprime un abonné.
     */
    public function destroy(Contact $contact)
    {
        $contact->delete();
        return redirect()->route('admin.contacts.index')->with('success', 'Abonné supprimé.');
    }
}