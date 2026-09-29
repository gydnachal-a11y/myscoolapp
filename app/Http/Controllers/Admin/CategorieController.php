<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use Illuminate\Http\Request;

class CategorieController extends Controller
{
    public function index()
    {
        $categories = Categorie::orderBy('nom')->paginate(15);
        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate(['nom' => 'required|string|max:255|unique:categories,nom']);
        Categorie::create($data);
        return redirect()->route('admin.categories.index')->with('success', 'Catégorie créée.');
    }

    public function edit(Categorie $category)
    {
        // Le model binding attend "category" par défaut
        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, Categorie $category)
    {
        $data = $request->validate([
            'nom' => 'required|string|max:255|unique:categories,nom,' . $category->id,
        ]);
        $category->update($data);
        return redirect()->route('admin.categories.index')->with('success', 'Catégorie mise à jour.');
    }

    public function destroy(Categorie $category)
    {
        $category->delete();
        return redirect()->route('admin.categories.index')->with('success', 'Catégorie supprimée.');
    }
}