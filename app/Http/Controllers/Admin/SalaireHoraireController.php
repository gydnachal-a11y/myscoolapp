<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalaireHoraire;
use Illuminate\Http\Request;

class SalaireHoraireController extends Controller
{
    public function index()
    {
        $tauxHoraires = SalaireHoraire::orderBy('created_at', 'desc')->get();
        return view('admin.salaire-horaires.index', compact('tauxHoraires'));
    }

    public function create()
    {
        return view('admin.salaire-horaires.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'taux_usd' => 'required|numeric|min:0',
        ]);

        // Si le nouveau taux est marqué actif, désactiver les autres
        if ($request->has('actif')) {
            SalaireHoraire::where('actif', true)->update(['actif' => false]);
            $data['actif'] = true;
        } else {
            $data['actif'] = false;
        }

        SalaireHoraire::create($data);

        return redirect()->route('admin.salaire-horaires.index')
            ->with('success', 'Taux horaire ajouté.');
    }

    public function edit(SalaireHoraire $salaireHoraire)
    {
        return view('admin.salaire-horaires.edit', compact('salaireHoraire'));
    }

    public function update(Request $request, SalaireHoraire $salaireHoraire)
    {
        $data = $request->validate([
            'taux_usd' => 'required|numeric|min:0',
        ]);

        // Si on active ce taux, désactiver les autres
        if ($request->has('actif')) {
            SalaireHoraire::where('actif', true)->where('id', '!=', $salaireHoraire->id)->update(['actif' => false]);
            $data['actif'] = true;
        } else {
            $data['actif'] = false;
        }

        $salaireHoraire->update($data);

        return redirect()->route('admin.salaire-horaires.index')
            ->with('success', 'Taux horaire mis à jour.');
    }

    public function destroy(SalaireHoraire $salaireHoraire)
    {
        $salaireHoraire->delete();
        return redirect()->route('admin.salaire-horaires.index')
            ->with('success', 'Taux horaire supprimé.');
    }

    // Optionnel : méthode pour activer rapidement
    public function activer(SalaireHoraire $salaireHoraire)
    {
        SalaireHoraire::where('actif', true)->update(['actif' => false]);
        $salaireHoraire->update(['actif' => true]);
        return back()->with('success', 'Taux horaire activé.');
    }
}