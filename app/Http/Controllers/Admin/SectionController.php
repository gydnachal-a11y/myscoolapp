<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\Session;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function index()
    {
        $sections = Section::with('session')->orderBy('nom')->paginate(10);
        return view('admin.sections.index', compact('sections'));
    }

    public function create()
    {
        $sessions = Session::orderBy('nom')->get();
        return view('admin.sections.create', compact('sessions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nom' => 'required|string|max:255|unique:sections,nom',
            'session_id' => 'required|exists:sessions,id',
        ]);
        Section::create($data);
        return redirect()->route('admin.sections.index')->with('success', 'Section créée.');
    }

    public function edit(Section $section)
    {
        $sessions = Session::orderBy('nom')->get();
        return view('admin.sections.edit', compact('section', 'sessions'));
    }

    public function update(Request $request, Section $section)
    {
        $data = $request->validate([
            'nom' => 'required|string|max:255|unique:sections,nom,'.$section->id,
            'session_id' => 'required|exists:sessions,id',
        ]);
        $section->update($data);
        return redirect()->route('admin.sections.index')->with('success', 'Section mise à jour.');
    }

    public function destroy(Section $section)
    {
        $section->delete();
        return redirect()->route('admin.sections.index')->with('success', 'Section supprimée.');
    }
}