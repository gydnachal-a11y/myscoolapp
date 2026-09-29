@extends('layouts.admin')

@section('page_title', $session->libelle)
@section('page_subtitle', 'Détail de la session')

@section('content')
@php
    // Calculs uniques et réutilisables
    $totalDemandes   = $demandes->total();
    $enAttenteCount  = $demandes->where('statut', 'en_attente')->count();
    $valideesCount   = $demandes->where('statut', 'validee')->count();
    $refuseesCount   = $demandes->where('statut', 'refusee')->count();

    $badges = [
        'en_attente' => 'bg-amber-100 text-amber-700',
        'validee'    => 'bg-emerald-100 text-emerald-700',
        'refusee'    => 'bg-red-100 text-red-700',
    ];

    // Statut global de la session
    $statutSession = match(true) {
        !$session->est_active                            => ['label' => 'Fermée',   'class' => 'bg-slate-100 text-slate-600'],
        $session->date_debut && $session->date_debut->gt(today()) => ['label' => 'À venir',  'class' => 'bg-blue-100 text-blue-700'],
        $session->date_fin && $session->date_fin->lt(today())     => ['label' => 'Expirée',  'class' => 'bg-amber-100 text-amber-700'],
        default                                          => ['label' => 'Ouverte',  'class' => 'bg-emerald-100 text-emerald-700'],
    };
@endphp

<div class="max-w-7xl mx-auto space-y-6">

    {{-- En-tête --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <a href="{{ route('admin.session-avances.index') }}"
           class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-600 transition flex-shrink-0">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="text-2xl font-bold text-slate-800">{{ $session->libelle }}</h1>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold {{ $statutSession['class'] }}">
                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                    {{ $statutSession['label'] }}
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1">
                @if($session->date_debut && $session->date_fin)
                    Du <strong>{{ $session->date_debut->translatedFormat('d M Y') }}</strong>
                    au <strong>{{ $session->date_fin->translatedFormat('d M Y') }}</strong>
                @else
                    <span class="text-amber-600 inline-flex items-center gap-1.5">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        Dates non définies
                    </span>
                @endif
                @if($session->createur)
                    <span class="text-slate-400">• Créée par {{ $session->createur->name }}</span>
                @endif
            </p>
        </div>

        @if($session->est_active)
            <form action="{{ route('admin.session-avances.fermer', $session) }}" method="POST"
                  onsubmit="return confirm('Fermer cette session ? Les membres ne pourront plus faire de demande.');">
                @csrf
                @method('PATCH')
                <button type="submit"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-red-50 hover:bg-red-100 text-red-700 rounded-xl font-semibold border border-red-200 transition">
                    <i class="fa-solid fa-lock"></i> Fermer la session
                </button>
            </form>
        @endif
    </div>

    {{-- Cartes de synthèse --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <div class="flex items-center justify-between">
                <div class="text-xs uppercase font-semibold text-slate-500 tracking-wider">Total</div>
                <i class="fa-solid fa-list-check text-slate-300"></i>
            </div>
            <div class="text-3xl font-bold text-slate-800 mt-2">{{ $totalDemandes }}</div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <div class="flex items-center justify-between">
                <div class="text-xs uppercase font-semibold text-slate-500 tracking-wider">En attente</div>
                <i class="fa-solid fa-clock text-amber-400"></i>
            </div>
            <div class="text-3xl font-bold text-amber-600 mt-2">{{ $enAttenteCount }}</div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <div class="flex items-center justify-between">
                <div class="text-xs uppercase font-semibold text-slate-500 tracking-wider">Validées</div>
                <i class="fa-solid fa-circle-check text-emerald-400"></i>
            </div>
            <div class="text-3xl font-bold text-emerald-600 mt-2">{{ $valideesCount }}</div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <div class="flex items-center justify-between">
                <div class="text-xs uppercase font-semibold text-slate-500 tracking-wider">Refusées</div>
                <i class="fa-solid fa-circle-xmark text-red-400"></i>
            </div>
            <div class="text-3xl font-bold text-red-600 mt-2">{{ $refuseesCount }}</div>
        </div>
    </div>

    {{-- Tableau des demandes --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="font-semibold text-slate-800">Demandes de cette session</h2>
            <span class="text-xs text-slate-400">{{ $totalDemandes }} enregistrement(s)</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-600 uppercase text-xs font-semibold">
                    <tr>
                        <th class="px-5 py-3">Membre</th>
                        <th class="px-5 py-3">Montant</th>
                        <th class="px-5 py-3">Soumis le</th>
                        <th class="px-5 py-3">Statut</th>
                        <th class="px-5 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($demandes as $demande)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-xs flex-shrink-0">
                                        {{ strtoupper(substr($demande->user->name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-medium text-slate-800 truncate">{{ $demande->user->name }}</div>
                                        <div class="text-xs text-slate-400 truncate">{{ $demande->user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-bold text-slate-800">{{ number_format($demande->montant_demande_usd, 0, ',', ' ') }} $</div>
                                <div class="text-xs text-slate-400">≈ {{ number_format($demande->montant_demande_fc, 0, ',', ' ') }} FC</div>
                            </td>
                            <td class="px-5 py-4 text-slate-500 text-xs whitespace-nowrap">
                                {{ $demande->created_at->format('d/m/Y') }}
                                <div class="text-slate-400">{{ $demande->created_at->format('H:i') }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $badges[$demande->statut] ?? 'bg-slate-100 text-slate-600' }}">
                                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                    {{ $demande->statut_label }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('admin.demandes-avance.show', $demande) }}"
                                   class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800 font-semibold text-sm transition">
                                    Voir <i class="fa-solid fa-arrow-right text-xs"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-16 text-center">
                                <div class="w-16 h-16 mx-auto bg-slate-100 rounded-full flex items-center justify-center mb-3">
                                    <i class="fa-regular fa-inbox text-slate-400 text-2xl"></i>
                                </div>
                                <p class="text-slate-500 font-medium">Aucune demande dans cette session.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($demandes->hasPages())
        <div>{{ $demandes->links() }}</div>
    @endif
</div>
@endsection