@extends('layouts.admin')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<div class="max-w-4xl mx-auto px-4 py-8" x-data="{ mode: 'accepter' }">

    {{-- ============================================================
         BARRE D'ACTIONS (non imprimée)
         ============================================================ --}}
    <div class="flex flex-wrap justify-between items-center gap-3 mb-6 no-print">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Fiche de la demande d'avance</h1>
            <p class="text-sm text-gray-500">Détail et traitement de la demande</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.demandes-avance.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                <i class="fa-solid fa-arrow-left"></i> Retour
            </a>
            <button onclick="window.print()"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                <i class="fa-solid fa-print"></i> Imprimer
            </button>
        </div>
    </div>

    {{-- Message d'erreur --}}
    @if(session('error'))
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl flex items-center gap-3 no-print">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- ============================================================
         FICHE (document)
         ============================================================ --}}
    <div class="bg-white rounded-2xl shadow-2xl border border-gray-200 overflow-hidden print:shadow-none print:border-0">

        {{-- En-tête : logo + coordonnées + référence --}}
        <div class="p-8 border-b border-gray-200">
            <div class="flex flex-col sm:flex-row justify-between items-start gap-6">
                <div class="flex items-center gap-4">
                    @if($siteSettings->site_logo ?? null)
                        <img src="{{ asset('storage/' . $siteSettings->site_logo) }}"
                             alt="{{ $siteSettings->site_name }}"
                             class="w-14 h-14 rounded-xl object-cover shadow-lg">
                    @else
                        <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 text-white flex items-center justify-center text-xl font-bold shadow-lg">
                            <i class="fa-solid fa-school"></i>
                        </div>
                    @endif
                    <div>
                        <h2 class="text-xl font-extrabold text-gray-900">
                            {{ $siteSettings->site_name ?? config('app.name', 'Mon École') }}
                        </h2>
                        @if($siteSettings->site_address ?? null)
                            <p class="text-sm text-gray-500">{{ $siteSettings->site_address }}</p>
                        @endif
                        @if($siteSettings->site_email ?? null)
                            <p class="text-sm text-gray-500">{{ $siteSettings->site_email }}</p>
                        @endif
                        @if($siteSettings->site_phone ?? null)
                            <p class="text-sm text-gray-500">{{ $siteSettings->site_phone }}</p>
                        @endif
                    </div>
                </div>
                <div class="text-right">
                    <h3 class="text-lg font-bold text-indigo-600 uppercase tracking-wider">
                        Demande d'avance
                    </h3>
                    <p class="text-sm text-gray-600">Réf : #{{ $demande->id }}</p>
                    <p class="text-sm text-gray-600">
                        Date : {{ $demande->created_at?->translatedFormat('d/m/Y') }}
                    </p>

                    {{-- Badge statut --}}
                    <div class="mt-3">
                        @php
                            $badgeDoc = match($demande->statut) {
                                'en_attente' => 'bg-amber-100 text-amber-700',
                                'validee'    => 'bg-emerald-100 text-emerald-700',
                                'refusee'    => 'bg-red-100 text-red-700',
                                default      => 'bg-gray-200 text-gray-700',
                            };
                            $badgeIcon = match($demande->statut) {
                                'en_attente' => 'fa-hourglass-half',
                                'validee'    => 'fa-circle-check',
                                'refusee'    => 'fa-circle-xmark',
                                default      => 'fa-circle',
                            };
                        @endphp
                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider {{ $badgeDoc }}">
                            <i class="fa-solid {{ $badgeIcon }}"></i>
                            {{ $demande->statut_label }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section : Demandeur --}}
        <div class="px-8 py-6 border-b border-gray-200 bg-gray-50/50">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- Demandeur --}}
                <div>
                    <p class="text-xs text-gray-500 uppercase font-semibold tracking-wider">Demandeur</p>
                    <p class="text-lg font-semibold text-gray-900 mt-1">
                        {{ $demande->user->name ?? 'Utilisateur supprimé' }}
                    </p>
                    @if($demande->user?->section)
                        <p class="text-sm text-gray-600">{{ $demande->user->section->nom }}</p>
                    @endif
                </div>

                {{-- Coordonnées --}}
                <div>
                    <p class="text-xs text-gray-500 uppercase font-semibold tracking-wider">Matricule</p>
                    <p class="font-semibold text-gray-900 mt-1">
                        {{ $demande->user->matricule ?? 'Non défini' }}
                    </p>
                    <p class="text-sm text-gray-600 mt-1">{{ $demande->user->email ?? '—' }}</p>
                    <p class="text-sm text-gray-600">{{ $demande->user->telephone ?? '—' }}</p>
                </div>

                {{-- Session --}}
                <div class="md:text-right">
                    <p class="text-xs text-gray-500 uppercase font-semibold tracking-wider">Session</p>
                    <p class="text-lg font-semibold text-gray-900 mt-1">
                        {{ $demande->session->libelle ?? '—' }}
                    </p>
                    <p class="text-sm text-gray-600">
                        Traitée par : <strong>{{ $demande->traitePar->name ?? 'En attente' }}</strong>
                    </p>
                </div>
            </div>
        </div>

        {{-- Section : Montant demandé (mise en avant) --}}
        <div class="px-8 py-6 border-b border-gray-200">
            <p class="text-xs text-gray-500 uppercase font-semibold tracking-wider mb-3">
                Montant demandé
            </p>
            <div class="flex flex-wrap items-baseline gap-3">
                <span class="text-4xl font-extrabold text-indigo-600">
                    {{ number_format($demande->montant_demande_usd, 0, ',', ' ') }}
                </span>
                <span class="text-2xl font-bold text-indigo-400">$ USD</span>
                <span class="text-sm text-gray-500 ml-2">
                    ≈ {{ number_format($demande->montant_demande_fc, 0, ',', ' ') }} FC
                </span>
            </div>
            <p class="text-xs text-gray-500 mt-2">
                Taux appliqué : <strong>{{ number_format($demande->taux_applique, 2, ',', ' ') }}</strong> FC/USD
            </p>
        </div>

        {{-- Section : Motif --}}
        <div class="px-8 py-6 border-b border-gray-200">
            <p class="text-xs text-gray-500 uppercase font-semibold tracking-wider mb-3">
                Motif de la demande
            </p>
            <div class="bg-gray-50 border-l-4 border-indigo-400 rounded-r-lg p-4">
                <p class="text-gray-700 leading-relaxed whitespace-pre-wrap">{{ $demande->motif }}</p>
            </div>
        </div>

        {{-- Section : Situation financière --}}
        <div class="px-8 py-6 border-b border-gray-200">
            <p class="text-xs text-gray-500 uppercase font-semibold tracking-wider mb-4">
                Situation financière du demandeur
            </p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <p class="text-sm text-gray-500">Salaire mensuel</p>
                    <p class="text-lg font-bold text-gray-900">
                        {{ number_format($salaire ?? 0, 0, ',', ' ') }} $
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Dette actuelle</p>
                    <p class="text-lg font-bold text-red-600">
                        {{ number_format($detteTotale ?? 0, 0, ',', ' ') }} $
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Limite d'emprunt</p>
                    <p class="text-lg font-bold text-gray-900">
                        {{ number_format($limite ?? 0, 0, ',', ' ') }} $
                    </p>
                </div>
            </div>
        </div>

        {{-- Section : Bloc refus (si déjà refusée) --}}
        @if($demande->isRefusee() && $demande->motif_refus)
            <div class="px-8 py-6 border-b border-gray-200 bg-red-50/50">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-circle-xmark text-red-600"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-bold text-red-700 mb-1">Motif du refus</p>
                        <p class="text-sm text-red-800 leading-relaxed">{{ $demande->motif_refus }}</p>
                        <p class="text-xs text-red-500 mt-2">
                            Traitée par <strong>{{ $demande->traitePar->name ?? 'N/A' }}</strong>
                            le {{ $demande->traite_le?->translatedFormat('d/m/Y à H:i') }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Section : Bloc validation (si déjà validée) --}}
        @if($demande->isValidee() && $demande->avance)
            <div class="px-8 py-6 border-b border-gray-200 bg-emerald-50/50">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-circle-check text-emerald-600"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-bold text-emerald-700 mb-1">Avance créée automatiquement</p>
                        <p class="text-sm text-emerald-800">
                            Avance <strong>#{{ $demande->avance->id }}</strong> d'un montant de
                            <strong>{{ number_format($demande->avance->montant_avance_usd, 0, ',', ' ') }} $</strong>
                            (≈ {{ number_format($demande->avance->montant_avance_fc, 0, ',', ' ') }} FC)
                        </p>
                        <p class="text-xs text-emerald-600 mt-2">
                            Traitée par <strong>{{ $demande->traitePar->name ?? 'N/A' }}</strong>
                            le {{ $demande->traite_le?->translatedFormat('d/m/Y à H:i') }}
                        </p>
                        <a href="{{ route('admin.avances.index') }}"
                           class="inline-flex items-center gap-1 mt-3 text-emerald-700 hover:text-emerald-800 font-semibold text-sm no-print">
                            Voir dans la liste des avances
                            <i class="fa-solid fa-arrow-right text-xs"></i>
                        </a>
                    </div>
                </div>
            </div>
        @endif

        {{-- Pied de document --}}
        <div class="px-8 py-6 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="text-sm text-gray-500">
                <p>Document généré le {{ now()->translatedFormat('d F Y à H:i') }}</p>
                <p class="text-xs text-gray-400 mt-0.5">
                    Réf. demande #{{ $demande->id }} — Session {{ $demande->session->libelle ?? '—' }}
                </p>
            </div>
            <div class="text-sm text-gray-500 text-center sm:text-right">
                <p class="font-semibold">Signature autorisée</p>
                <div class="mt-2 h-10 w-40 border-b border-gray-400"></div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         ACTIONS : SÉLECTEUR DYNAMIQUE + FORMULAIRES
         ============================================================ --}}
    @if($demande->isEnAttente())
        <div class="mt-6 bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden no-print">

            {{-- Sélecteur (tabs) --}}
            <div class="grid grid-cols-2 border-b border-gray-200">
                <button type="button"
                        @click="mode = 'accepter'"
                        :class="mode === 'accepter'
                            ? 'bg-emerald-50 text-emerald-700 border-b-2 border-emerald-600'
                            : 'bg-white text-gray-500 hover:bg-gray-50 hover:text-gray-700'"
                        class="flex items-center justify-center gap-2 py-4 font-bold text-sm transition-all">
                    <i class="fa-solid fa-circle-check"></i>
                    Accepter la demande
                </button>
                <button type="button"
                        @click="mode = 'refuser'"
                        :class="mode === 'refuser'
                            ? 'bg-red-50 text-red-700 border-b-2 border-red-600'
                            : 'bg-white text-gray-500 hover:bg-gray-50 hover:text-gray-700'"
                        class="flex items-center justify-center gap-2 py-4 font-bold text-sm transition-all">
                    <i class="fa-solid fa-circle-xmark"></i>
                    Refuser la demande
                </button>
            </div>

            {{-- ============================================
                 FORMULAIRE : ACCEPTER
                 ============================================ --}}
            <form x-show="mode === 'accepter'"
                  x-transition:enter="transition ease-out duration-200"
                  x-transition:enter-start="opacity-0 -translate-y-1"
                  x-transition:enter-end="opacity-100 translate-y-0"
                  action="{{ route('admin.demandes-avance.valider', $demande) }}"
                  method="POST"
                  class="p-8 space-y-5">
                @csrf

                <div class="flex items-start gap-3 p-4 bg-emerald-50 rounded-xl border border-emerald-100">
                    <i class="fa-solid fa-circle-info text-emerald-600 text-lg mt-0.5"></i>
                    <div class="text-sm text-emerald-800">
                        En validant cette demande, une <strong>avance sera créée automatiquement</strong>
                        dans la table des avances et la dette du membre sera mise à jour.
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Mois scolaire <span class="text-red-500">*</span>
                    </label>
                    <select name="mois_scolaire_id" required
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 outline-none transition">
                        @foreach($moisScolaires as $mois)
                            <option value="{{ $mois->id }}">{{ $mois->nom_mois ?? $mois->mois }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Date de l'avance <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="date_avance"
                           value="{{ old('date_avance', now()->toDateString()) }}" required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 outline-none transition">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Commentaire (optionnel)
                    </label>
                    <input type="text" name="commentaire" value="{{ old('commentaire') }}"
                           placeholder="Ex : validé par la direction..."
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 outline-none transition">
                </div>

                <button type="submit"
                        class="w-full py-3 bg-gradient-to-r from-emerald-500 to-teal-500 text-white rounded-lg font-semibold hover:from-emerald-600 hover:to-teal-600 shadow-lg shadow-emerald-500/25 transition-all flex items-center justify-center gap-2">
                    <i class="fa-solid fa-check"></i>
                    Confirmer et créer l'avance
                </button>
            </form>

            {{-- ============================================
                 FORMULAIRE : REFUSER
                 ============================================ --}}
            <form x-show="mode === 'refuser'"
                  x-transition:enter="transition ease-out duration-200"
                  x-transition:enter-start="opacity-0 -translate-y-1"
                  x-transition:enter-end="opacity-100 translate-y-0"
                  action="{{ route('admin.demandes-avance.refuser', $demande) }}"
                  method="POST"
                  onsubmit="return confirm('Confirmer le refus de cette demande ?');"
                  class="p-8 space-y-5">
                @csrf

                <div class="flex items-start gap-3 p-4 bg-red-50 rounded-xl border border-red-100">
                    <i class="fa-solid fa-triangle-exclamation text-red-600 text-lg mt-0.5"></i>
                    <div class="text-sm text-red-800">
                        Le membre recevra une notification contenant le motif de refus.
                        <strong>Aucune avance ne sera créée.</strong>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Motif du refus <span class="text-red-500">*</span>
                    </label>
                    <textarea name="motif_refus" rows="6" required minlength="10" maxlength="1000"
                              placeholder="Expliquez clairement au membre la raison du refus (min. 10 caractères)..."
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500/30 focus:border-red-500 outline-none resize-none transition">{{ old('motif_refus') }}</textarea>
                    @error('motif_refus')
                        <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                        class="w-full py-3 bg-gradient-to-r from-red-500 to-rose-500 text-white rounded-lg font-semibold hover:from-red-600 hover:to-rose-600 shadow-lg shadow-red-500/25 transition-all flex items-center justify-center gap-2">
                    <i class="fa-solid fa-xmark"></i>
                    Confirmer le refus
                </button>
            </form>
        </div>
    @endif
</div>

<style>
    @media print {
        .no-print { display: none !important; }
        body { background: white !important; }
        .shadow-2xl, .shadow-xl { box-shadow: none !important; }
        .rounded-2xl { border-radius: 0 !important; }
        .border { border-color: #ddd !important; }
        .bg-gray-50\/50 { background: #fafafa !important; }
        .bg-emerald-50\/50 { background: #f0fdf4 !important; }
        .bg-red-50\/50 { background: #fef2f2 !important; }
        .max-w-4xl { max-width: 100% !important; }
    }
</style>

@endsection