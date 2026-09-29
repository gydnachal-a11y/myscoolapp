<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Option;
use Illuminate\Http\Request;

class OptionController extends Controller
{
    public function index()
    {
        $options = Option::orderBy('nom')->paginate(10);
        return view('admin.options.index', compact('options'));
    }

    public function create()
    {
        return view('admin.options.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nom' => 'required|string|max:255|unique:options,nom',
        ]);
        Option::create($data);
        return redirect()->route('admin.options.index')->with('success', 'Option créée.');
    }

    public function edit(Option $option)
    {
        return view('admin.options.edit', compact('option'));
    }

    public function update(Request $request, Option $option)
    {
        $data = $request->validate([
            'nom' => 'required|string|max:255|unique:options,nom,'.$option->id,
        ]);
        $option->update($data);
        return redirect()->route('admin.options.index')->with('success', 'Option mise à jour.');
    }

    public function destroy(Option $option)
    {
        $option->delete();
        return redirect()->route('admin.options.index')->with('success', 'Option supprimée.');
    }
}