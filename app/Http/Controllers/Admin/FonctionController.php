<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fonction;
use App\Models\Section;
use App\Http\Requests\StoreFonctionRequest;
use App\Http\Requests\UpdateFonctionRequest;
use Illuminate\Http\Request;

class FonctionController extends Controller
{
    public function index(Request $request)
    {
        $query = Fonction::with('section');

        // Recherche par nom
        if ($search = $request->input('search')) {
            $query->where('nom', 'like', "%{$search}%");
        }

        // Filtre par section
        if ($sectionId = $request->input('section_id')) {
            $query->where('section_id', $sectionId);
        }

        $fonctions = $query->orderBy('nom')->paginate(15)->appends($request->query());

        // Pour le filtre dans la vue
        $sections = Section::orderBy('nom')->get();

        return view('admin.fonctions.index', compact('fonctions', 'sections'));
    }

    public function create()
    {
        $sections = Section::orderBy('nom')->get();
        return view('admin.fonctions.create', compact('sections'));
    }

    public function store(StoreFonctionRequest $request)
    {
        Fonction::create($request->validated());
        return redirect()->route('admin.fonctions.index')->with('success', 'Fonction créée.');
    }

    public function edit(Fonction $fonction)
    {
        $sections = Section::orderBy('nom')->get();
        return view('admin.fonctions.edit', compact('fonction', 'sections'));
    }

    public function update(UpdateFonctionRequest $request, Fonction $fonction)
    {
        $fonction->update($request->validated());
        return redirect()->route('admin.fonctions.index')->with('success', 'Fonction mise à jour.');
    }

    public function destroy(Fonction $fonction)
    {
        $fonction->delete();
        return redirect()->route('admin.fonctions.index')->with('success', 'Fonction supprimée.');
    }
}