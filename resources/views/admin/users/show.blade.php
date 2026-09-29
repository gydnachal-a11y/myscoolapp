@extends('layouts.admin')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<div class="max-w-5xl mx-auto px-4 py-6">
    {{-- Barre d'actions --}}
    <div class="flex flex-wrap justify-between items-center gap-3 mb-6 no-print">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Fiche de l’employé</h1>
            <p class="text-sm text-gray-500">Informations personnelles et professionnelles</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
            <button onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                <i class="bi bi-printer"></i> Imprimer
            </button>
        </div>
    </div>

    {{-- Carte principale --}}
    <div class="bg-white rounded-2xl shadow-2xl border border-gray-200 overflow-hidden print:shadow-none print:border-0">
        {{-- En-tête de fiche --}}
        <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-indigo-50 to-violet-50 print:bg-none">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="flex items-center gap-4">
                    @if($user->photo)
                        <img src="{{ asset('storage/' . $user->photo) }}" alt="Photo" class="w-20 h-20 rounded-full object-cover shadow-lg">
                    @else
                        <div class="w-20 h-20 rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white text-3xl font-bold shadow-lg">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                    @endif
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">{{ $user->name }}</h2>
                        <p class="text-gray-600">{{ optional($user->fonction)->nom ?? 'Fonction non définie' }}</p>
                        <div class="flex flex-wrap gap-2 mt-2">
                            @if($user->section)
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-sm font-medium">
                                    <i class="bi bi-building"></i> {{ $user->section->nom }}
                                </span>
                            @endif
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-amber-100 text-amber-700 text-sm font-medium">
                                <i class="bi bi-shield-lock"></i> {{ ucfirst($user->role) }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-500 uppercase">Matricule</p>
                    <p class="font-semibold text-gray-800">{{ $user->matricule ?? 'Non défini' }}</p>
                </div>
            </div>
        </div>

        {{-- Informations personnelles --}}
        <div class="px-6 py-5 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-700 mb-3 flex items-center gap-2">
                <i class="bi bi-person-vcard text-indigo-600"></i> Informations personnelles
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                <div>
                    <p class="text-sm text-gray-500">Sexe</p>
                    <p class="font-medium text-gray-800">{{ $user->sexe === 'M' ? 'Masculin' : ($user->sexe === 'F' ? 'Féminin' : '—') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Email</p>
                    <p class="font-medium text-gray-800">{{ $user->email }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Téléphone</p>
                    <p class="font-medium text-gray-800">{{ $user->telephone ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Date de naissance</p>
                    <p class="font-medium text-gray-800">{{ $user->date_naissance ? $user->date_naissance->format('d/m/Y') : '—' }}</p>
                </div>
                <div class="md:col-span-2">
                    <p class="text-sm text-gray-500">Adresse</p>
                    <p class="font-medium text-gray-800">{{ $user->adresse ?? '—' }}</p>
                </div>
            </div>
        </div>

        {{-- Section Cours et salaire --}}
        <div class="px-6 py-5 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2">
                <i class="bi bi-book text-indigo-600"></i> Cours et salaire
            </h3>

            @if(isset($assignations) && $assignations->isNotEmpty())
                <div class="overflow-x-auto rounded-xl border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Salle</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Cours</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Jours</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Créneau</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Durée/séance</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Séances/sem.</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Total/sem.</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @foreach($assignations as $assign)
                                @php
                                    $dureeSeance = 0;
                                    if ($assign->creneauHoraire) {
                                        $dureeSeance = $assign->creneauHoraire->heure_debut->diffInMinutes($assign->creneauHoraire->heure_fin) / 60;
                                    } elseif ($assign->nombreHeure && $assign->nombreHeure->valeur) {
                                        $dureeSeance = (float)$assign->nombreHeure->valeur;
                                    }
                                    $joursArray = $assign->jours ? explode(',', $assign->jours) : [];
                                    $nbSeances = $assign->nombre_seances ?? count($joursArray);
                                    $totalHebdo = $dureeSeance * $nbSeances;
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">{{ optional($assign->salle)->nom ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ optional($assign->cour)->nom ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        @if($joursArray)
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($joursArray as $jour)
                                                    <span class="px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 text-xs">{{ $jour }}</span>
                                                @endforeach
                                            </div>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($assign->creneauHoraire)
                                            {{ $assign->creneauHoraire->libelle }} ({{ $assign->creneauHoraire->heure_debut->format('H:i') }} - {{ $assign->creneauHoraire->heure_fin->format('H:i') }})
                                        @else
                                            {{ $assign->duree ?? '—' }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">{{ $dureeSeance > 0 ? $dureeSeance . ' h' : '—' }}</td>
                                    <td class="px-4 py-3">{{ $nbSeances }}</td>
                                    <td class="px-4 py-3 font-medium">{{ $totalHebdo > 0 ? $totalHebdo . ' h' : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="6" class="px-4 py-3 text-right font-semibold text-gray-600">Total hebdomadaire</td>
                                <td class="px-4 py-3 font-semibold text-gray-800">{{ $heuresTotales ?? 0 }} h</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="mt-4 bg-indigo-50 rounded-xl p-4">
                    <p class="text-sm text-gray-700">
                        Salaire de base calculé : <strong>{{ number_format($salaireAutoBaseUsd ?? 0, 0) }} $</strong>
                        (≈ {{ number_format($salaireAutoBaseFc ?? 0, 0) }} FC)
                    </p>
                    <p class="text-sm text-gray-600 mt-1">Taux horaire actif : {{ $tauxHoraireUsd ?? '—' }} $/h</p>
                </div>
            @else
                <div class="bg-amber-50 border-l-4 border-amber-400 text-amber-800 p-4 rounded-lg flex items-start gap-3">
                    <i class="bi bi-exclamation-triangle-fill text-xl"></i>
                    <p>Ce personnel n'a aucun cours assigné. Son salaire est probablement fixé manuellement.</p>
                </div>
                @if($user->type_salaire === 'manuel' && $user->salaire_mensuel_usd)
                    <p class="mt-3 text-gray-700">Salaire manuel actuel : <strong>{{ number_format($user->salaire_mensuel_usd, 0) }} $</strong></p>
                @endif
            @endif
        </div>

        {{-- Pied de fiche --}}
        <div class="px-6 py-4 flex flex-wrap justify-between items-center gap-4 no-print">
            <div class="flex gap-2">
                <a href="{{ route('admin.salaires.edit', $user) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                    <i class="bi bi-cash-coin"></i> Fixer le salaire
                </a>
                <a href="{{ route('admin.users.edit', $user) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                    <i class="bi bi-pencil"></i> Modifier
                </a>
                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Supprimer ce personnel ?')" class="inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                        <i class="bi bi-trash"></i> Supprimer
                    </button>
                </form>
            </div>
            <p class="text-sm text-gray-400">Document généré le {{ now()->format('d/m/Y à H:i') }}</p>
        </div>
    </div>
</div>

<style>
    @media print {
        .no-print { display: none !important; }
        body { background: white !important; }
        .shadow-2xl { box-shadow: none !important; }
        .rounded-2xl { border-radius: 0 !important; }
        .border { border-color: #ddd !important; }
        .bg-gradient-to-r { background: #f9fafb !important; }
        .bg-indigo-50 { background: #f0f5ff !important; }
        .bg-amber-50 { background: #fffbeb !important; }
        .overflow-x-auto { overflow: visible !important; }
    }
</style>
@endsection