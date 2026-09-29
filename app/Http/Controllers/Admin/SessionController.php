<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Session;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function index()
    {
        $sessions = Session::orderBy('nom')->paginate(10);
        return view('admin.sessions.index', compact('sessions'));
    }

    public function create()
    {
        return view('admin.sessions.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nom' => 'required|string|max:255|unique:sessions,nom',
        ]);
        Session::create($data);
        return redirect()->route('admin.sessions.index')->with('success', 'Session créée.');
    }

    public function edit(Session $session)
    {
        return view('admin.sessions.edit', compact('session'));
    }

    public function update(Request $request, Session $session)
    {
        $data = $request->validate([
            'nom' => 'required|string|max:255|unique:sessions,nom,'.$session->id,
        ]);
        $session->update($data);
        return redirect()->route('admin.sessions.index')->with('success', 'Session mise à jour.');
    }

    public function destroy(Session $session)
    {
        $session->delete();
        return redirect()->route('admin.sessions.index')->with('success', 'Session supprimée.');
    }
}