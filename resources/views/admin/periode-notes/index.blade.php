@extends('layouts.admin')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold">Périodes de notation</h1>
            <p class="text-sm text-gray-500 mt-1">Gérez les périodes de saisie des notes</p>
        </div>
        <a href="{{ route('admin.periode-notes.create') }}" class="btn-primary">
            <i class="fa-solid fa-plus"></i> Nouvelle période
        </a>
    </div>

    {{-- Statistiques --}}
    <div class="stats-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="stat-card bg-white rounded-xl shadow p-4 border-l-4 border-indigo-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total périodes</p>
                    <p class="text-2xl font-bold">{{ $totalPeriodes ?? $periodes->total() }}</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">
                    <i class="fa-regular fa-calendar"></i>
                </div>
            </div>
        </div>
        <div class="stat-card bg-white rounded-xl shadow p-4 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Actives</p>
                    <p class="text-2xl font-bold">{{ $totalActives ?? 0 }}</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-green-600">
                    <i class="fa-regular fa-circle-check"></i>
                </div>
            </div>
        </div>
        <div class="stat-card bg-white rounded-xl shadow p-4 border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Inactives</p>
                    <p class="text-2xl font-bold">{{ $totalInactives ?? 0 }}</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600">
                    <i class="fa-regular fa-circle-xmark"></i>
                </div>
            </div>
        </div>
        <div class="stat-card bg-white rounded-xl shadow p-4 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">En cours</p>
                    <p class="text-2xl font-bold">{{ $totalEnCours ?? 0 }}</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                    <i class="fa-regular fa-clock"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtres --}}
    <form method="GET" class="bg-white p-4 rounded-xl shadow mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Année scolaire</label>
            <select name="annee_scolaire_id" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Toutes</option>
                @foreach($annees as $annee)
                    <option value="{{ $annee->id }}" {{ request('annee_scolaire_id') == $annee->id ? 'selected' : '' }}>
                        {{ $annee->libelle }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Statut</label>
            <select name="est_active" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Tous</option>
                <option value="1" {{ request('est_active') == '1' ? 'selected' : '' }}>Active</option>
                <option value="0" {{ request('est_active') == '0' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Recherche</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Nom de la période..."
                   class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div class="flex items-end">
            <button type="submit" class="btn-primary w-full">Filtrer</button>
        </div>
    </form>

    {{-- Tableau --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Nom</th>
                        <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Année</th>
                        <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Date début</th>
                        <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Date fin</th>
                        <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                        <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">En cours</th>
                        <th class="px-4 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($periodes as $periode)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $periode->nom }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $periode->anneeScolaire->libelle ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $periode->date_debut ? $periode->date_debut->format('d/m/Y') : '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $periode->date_fin ? $periode->date_fin->format('d/m/Y') : '—' }}</td>
                            <td class="px-4 py-3">
                                @if($periode->est_active)
                                    <span class="badge badge-green"><i class="fa-regular fa-circle-check"></i> Active</span>
                                @else
                                    <span class="badge badge-gray"><i class="fa-regular fa-circle-xmark"></i> Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($periode->estEnCours())
                                    <span class="badge badge-blue"><i class="fa-regular fa-clock"></i> En cours</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    {{-- Toggle activation --}}
                                    <form action="{{ route('admin.periode-notes.toggle', $periode) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-sm {{ $periode->est_active ? 'text-yellow-600 hover:text-yellow-800' : 'text-green-600 hover:text-green-800' }} transition"
                                                title="{{ $periode->est_active ? 'Désactiver' : 'Activer' }}">
                                            <i class="fa-solid {{ $periode->est_active ? 'fa-pause' : 'fa-play' }}"></i>
                                            {{ $periode->est_active ? 'Désactiver' : 'Activer' }}
                                        </button>
                                    </form>
                                    <span class="text-gray-300">|</span>
                                    <a href="{{ route('admin.periode-notes.edit', $periode) }}" class="text-indigo-600 hover:text-indigo-800 transition" title="Modifier">
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </a>
                                    <form action="{{ route('admin.periode-notes.destroy', $periode) }}" method="POST" class="inline" onsubmit="return confirm('Supprimer définitivement cette période ?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 transition" title="Supprimer">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">Aucune période de notation trouvée.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-center gap-2">
            <div class="text-sm text-gray-500">
                Affichage de <strong>{{ $periodes->firstItem() ?? 0 }}</strong> à <strong>{{ $periodes->lastItem() ?? 0 }}</strong> sur <strong>{{ $periodes->total() }}</strong> périodes
            </div>
            {{ $periodes->appends(request()->query())->links() }}
        </div>
    </div>
</div>

<style>
    .badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .badge-green { background: #dcfce7; color: #166534; }
    .badge-gray { background: #f1f5f9; color: #475569; }
    .badge-blue { background: #dbeafe; color: #1e40af; }

    .stat-card {
        transition: all 0.2s ease;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);
    }

    .btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 0.6rem 1.2rem;
        background: #1e293b;
        color: white;
        border-radius: 10px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
    }
    .btn-primary:hover {
        background: #667eea;
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(102,126,234,0.3);
    }

    @media (max-width: 640px) {
        .stats-grid {
            grid-template-columns: 1fr 1fr;
        }
        .stat-card {
            padding: 0.75rem;
        }
        .stat-card .text-2xl {
            font-size: 1.25rem;
        }
        .table-responsive {
            overflow-x: auto;
        }
    }
</style>
@endsection