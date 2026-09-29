<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    /**
     * Clés autorisées pour les paramètres simples.
     */
    private const TEXT_KEYS = ['ecole_nom', 'ecole_adresse', 'ecole_telephone', 'ecole_email'];

    /**
     * Formulaire simple (texte + logo).
     */
    public function edit()
    {
        $settings = Setting::pluck('value', 'key')->toArray();
        return view('admin.settings.edit', compact('settings'));
    }

    /**
     * Sauvegarde des paramètres simples.
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'ecole_nom' => 'required|string|max:255',
            'ecole_adresse' => 'nullable|string|max:255',
            'ecole_telephone' => 'nullable|string|max:50',
            'ecole_email' => 'nullable|email|max:100',
            'ecole_logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // Mise à jour des champs texte
        foreach (self::TEXT_KEYS as $key) {
            Setting::updateOrCreate(['key' => $key], ['value' => $data[$key]]);
        }

        // Gestion du logo
        if ($request->hasFile('ecole_logo')) {
            $path = $request->file('ecole_logo')->store('logos', 'public');
            Setting::updateOrCreate(['key' => 'ecole_logo'], ['value' => $path]);
        }

        return back()->with('success', 'Paramètres mis à jour.');
    }

    /**
     * Éditeur canvas (interface visuelle).
     */
    public function canvas()
    {
        return view('admin.settings.canvas');
    }

    /**
     * Sauvegarde du canvas (JSON).
     */
    public function storeCanvas(Request $request)
    {
        $data = $request->validate(['canvas_data' => 'required|json']);
        Setting::updateOrCreate(['key' => 'canvas_header'], ['value' => $data['canvas_data']]);
        return back()->with('success', 'Mise en page enregistrée.');
    }

    /**
     * Chargement du canvas (JSON).
     */
    public function loadCanvas()
    {
        $json = Setting::getValue('canvas_header', '{}');
        return response()->json(json_decode($json));
    }
}