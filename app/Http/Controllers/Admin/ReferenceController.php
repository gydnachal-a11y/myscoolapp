<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreneauHoraire;
use App\Models\Libelle;
use App\Models\NombreHeure;
use App\Models\Ponderation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReferenceController extends Controller
{
    private const MODELS = [
        'libelles'          => Libelle::class,
        'ponderations'      => Ponderation::class,
        'nombre-heures'     => NombreHeure::class,
        'creneaux-horaires' => CreneauHoraire::class,
    ];

    private const LABELS = [
        'libelles'          => 'libellé',
        'ponderations'      => 'pondération',
        'nombre-heures'     => 'nombre d\'heures',
        'creneaux-horaires' => 'créneau horaire',
    ];

    private const ORDER_COLUMNS = [
        'libelles'          => 'nom',
        'ponderations'      => 'valeur',
        'nombre-heures'     => 'valeur',
        'creneaux-horaires' => 'heure_debut',
    ];

    public function index(string $type)
    {
        $items = $this->newQuery($type)
            ->orderBy(self::ORDER_COLUMNS[$type] ?? 'id')
            ->paginate(15);

        return view('admin.references.index', compact('items', 'type'));
    }

    public function create(string $type)
    {
        return view('admin.references.create', compact('type'));
    }

    public function store(Request $request, string $type)
    {
        $data = $request->validate($this->rules($type));
        $this->newQuery($type)->create($data);

        return redirect()
            ->route('admin.references.index', $type)
            ->with('success', ucfirst(self::LABELS[$type] ?? 'élément') . ' créé.');
    }

    public function edit(string $type, int $id)
    {
        $item = $this->newQuery($type)->findOrFail($id);

        return view('admin.references.edit', compact('item', 'type'));
    }

    public function update(Request $request, string $type, int $id)
    {
        $item = $this->newQuery($type)->findOrFail($id);
        $data = $request->validate($this->rules($type, $item->id));
        $item->update($data);

        return redirect()
            ->route('admin.references.index', $type)
            ->with('success', ucfirst(self::LABELS[$type] ?? 'élément') . ' mis à jour.');
    }

    public function destroy(string $type, int $id)
    {
        $this->newQuery($type)->findOrFail($id)->delete();

        return redirect()
            ->route('admin.references.index', $type)
            ->with('success', ucfirst(self::LABELS[$type] ?? 'élément') . ' supprimé.');
    }

    private function newQuery(string $type)
    {
        $model = self::MODELS[$type] ?? abort(404);
        return (new $model)->newQuery();
    }

    private function rules(string $type, ?int $ignoreId = null): array
    {
        return match ($type) {
            'libelles' => [
                'nom'         => ['required', 'string', 'max:255', Rule::unique('libelles', 'nom')->ignore($ignoreId)],
                'description' => 'nullable|string|max:500',
            ],
            'ponderations' => [
                'nom'    => 'required|string|max:255',
                'valeur' => 'required|integer|min:0',
            ],
            'nombre-heures' => [
                'valeur'  => ['required', 'integer', 'min:1', Rule::unique('nombre_heures', 'valeur')->ignore($ignoreId)],
                'libelle' => 'nullable|string|max:50',
            ],
            'creneaux-horaires' => [
                'libelle'     => ['required', 'string', 'max:255'],
                'heure_debut' => ['required', 'date_format:H:i'],
                'heure_fin'   => ['required', 'date_format:H:i', 'after:heure_debut'],
                'ordre'       => ['nullable', 'integer', 'min:0'],
            ],
            default => abort(404),
        };
    }
}