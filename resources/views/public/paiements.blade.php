<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#4f46e5">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Paiements par classe – {{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        /* ============================================================
           DESIGN TOKENS
           ============================================================ */
        :root {
            --primary:       #4f46e5;
            --primary-hover: #4338ca;
            --primary-soft:  #eef2ff;
            --violet:        #8b5cf6;
            --violet-soft:   #f5f3ff;
            --emerald:       #059669;
            --emerald-soft:  #ecfdf5;
            --amber:         #d97706;
            --amber-soft:    #fffbeb;
            --blue:          #2563eb;
            --blue-soft:     #eff6ff;
            --pink:          #db2777;
            --pink-soft:     #fdf2f8;
            --red:           #dc2626;
            --red-soft:      #fef2f2;
            --slate-50:      #f8fafc;
            --slate-100:     #f1f5f9;
            --slate-200:     #e2e8f0;
            --slate-400:     #94a3b8;
            --slate-500:     #64748b;
            --slate-600:     #475569;
            --slate-700:     #334155;
            --slate-800:     #1e293b;
            --slate-900:     #0f172a;

            --radius-sm: 10px;
            --radius-md: 14px;
            --radius-lg: 18px;
            --radius-xl: 24px;

            --shadow-sm:    0 1px 2px rgba(15,23,42,.04), 0 1px 3px rgba(15,23,42,.06);
            --shadow-md:    0 4px 12px rgba(15,23,42,.06), 0 2px 4px rgba(15,23,42,.04);
            --shadow-lg:    0 12px 32px rgba(79,70,229,.10), 0 4px 8px rgba(15,23,42,.04);
            --shadow-hover: 0 20px 40px rgba(79,70,229,.15), 0 8px 16px rgba(15,23,42,.06);

            --transition: 250ms cubic-bezier(.4,0,.2,1);
        }

        html {
            -webkit-text-size-adjust: 100%;
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            font-feature-settings: 'cv02', 'cv03', 'cv04', 'cv11', 'ss01';
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            background: linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
            min-height: 100vh;
            min-height: 100dvh;
        }

        /* ============================================================
           RESPONSIVE — TABLEAU vs CARTES
           ============================================================ */
        .view-table { display: none !important; }
        .view-cards { display: block !important; }

        @media (min-width: 768px) {
            .view-table { display: block !important; }
            .view-cards { display: none !important; }
        }

        /* ============================================================
           CHIFFRES TABULAIRES
           ============================================================ */
        .tabular-nums { font-variant-numeric: tabular-nums; }

        /* ============================================================
           ANIMATIONS
           ============================================================ */
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulse-ring {
            0%   { transform: scale(.8); opacity: .7; }
            80%  { transform: scale(1.6); opacity: 0; }
            100% { transform: scale(1.6); opacity: 0; }
        }

        @keyframes progress-fill {
            from { width: 0; }
        }

        .animate-in { animation: fade-in .35s ease-out both; }

        /* Stagger sur les cards mobile */
        .paiement-card { animation: fade-in .3s ease-out both; }
        .paiement-card:nth-child(1)  { animation-delay: .02s; }
        .paiement-card:nth-child(2)  { animation-delay: .04s; }
        .paiement-card:nth-child(3)  { animation-delay: .06s; }
        .paiement-card:nth-child(4)  { animation-delay: .08s; }
        .paiement-card:nth-child(5)  { animation-delay: .10s; }
        .paiement-card:nth-child(n+6){ animation-delay: .12s; }

        .nonpayant-item { animation: fade-in .3s ease-out both; }
        .nonpayant-item:nth-child(1)  { animation-delay: .02s; }
        .nonpayant-item:nth-child(2)  { animation-delay: .04s; }
        .nonpayant-item:nth-child(3)  { animation-delay: .06s; }
        .nonpayant-item:nth-child(n+4){ animation-delay: .08s; }

        /* ============================================================
           HOVER EFFECTS
           ============================================================ */
        .row-hover { transition: background var(--transition); }
        .row-hover:hover {
            background: linear-gradient(90deg, #f5f3ff 0%, #faf5ff 50%, #fefbff 100%);
        }

        .card-hover { transition: all var(--transition); }
        .card-hover:hover {
            background: #fafaff;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .card-hover:active { transform: translateY(0); }

        .stat-card { transition: all var(--transition); }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .nonpayant-item { transition: all var(--transition); }
        .nonpayant-item:hover {
            background: var(--amber-soft);
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm);
        }

        /* ============================================================
           FOCUS ACCESSIBLE
           ============================================================ */
        a:focus-visible,
        button:focus-visible,
        input:focus-visible,
        select:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
            border-radius: 4px;
        }

        /* ============================================================
           AVATAR
           ============================================================ */
        .avatar {
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            box-shadow: 0 4px 10px rgba(99,102,241,.25);
        }

        /* ============================================================
           STICKY SEARCH
           ============================================================ */
        .search-bar {
            position: sticky;
            top: 1rem;
            z-index: 20;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
        .search-bar.is-stuck {
            box-shadow: var(--shadow-lg);
            border-color: rgba(79,70,229,.15);
        }

        /* ============================================================
           EMPTY STATES
           ============================================================ */
        .empty-state { display: none; }
        .empty-state.is-visible { display: block; }

        /* ============================================================
           LIVE DOT
           ============================================================ */
        .live-dot { position: relative; display: inline-flex; width: 8px; height: 8px; }
        .live-dot::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 9999px;
            background: #10b981;
            animation: pulse-ring 1.8s ease-out infinite;
        }
        .live-dot::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 9999px;
            background: #10b981;
        }

        /* ============================================================
           PROGRESS BAR
           ============================================================ */
        .progress-bar-fill {
            animation: progress-fill 1.2s cubic-bezier(.4,0,.2,1) both;
        }

        /* ============================================================
           RESPONSIVE — 360px
           ============================================================ */
        @media (max-width: 360px) {
            .stats-grid { grid-template-columns: 1fr !important; }
            .stat-value { font-size: 1.5rem !important; }
        }

        /* ============================================================
           RESPONSIVE — 1280px+
           ============================================================ */
        @media (min-width: 1280px) {
            .paiements-container { max-width: 1200px; }
        }

        /* ============================================================
           IMPRESSION
           ============================================================ */
        @media print {
            body { background: white !important; }
            .no-print { display: none !important; }
            .view-cards { display: none !important; }
            .view-table { display: block !important; }
            .search-bar,
            .progress-bar-wrapper,
            header .no-print,
            footer { display: none !important; }
            .bg-white { box-shadow: none !important; border: 1px solid #ddd !important; }
            .avatar { background: #ddd !important; color: #333 !important; box-shadow: none !important; }
            .animate-in, .paiement-card, .nonpayant-item { animation: none !important; opacity: 1 !important; }
            @page { margin: 1cm; }
        }

        /* ============================================================
           ACCESSIBILITÉ
           ============================================================ */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: .01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: .01ms !important;
                scroll-behavior: auto !important;
            }
            .stat-card:hover,
            .card-hover:hover,
            .nonpayant-item:hover { transform: none; }
        }
    </style>
</head>

<body class="text-slate-800">

<div class="paiements-container max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10">

    {{-- ============================================================
         EN-TÊTE
    ============================================================ --}}
    <header class="mb-8 sm:mb-10 animate-in">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-5">
            <div class="text-center sm:text-left">
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold tracking-wider mb-3 border border-emerald-100">
                    <i class="fa-solid fa-coins" aria-hidden="true"></i>
                    SUIVI DES PAIEMENTS
                </span>
                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black text-slate-900 tracking-tight leading-tight">
                    Paiements par classe
                </h1>
                <p class="mt-2.5 text-sm sm:text-base text-slate-600 max-w-xl leading-relaxed">
                    Sélectionnez une salle de classe. Seules les périodes déjà encaissées s'afficheront automatiquement.
                </p>
            </div>

            <div class="flex items-center justify-center sm:justify-end gap-2 no-print">
                <a href="{{ route('public.inscriptions') }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 active:scale-[.98] text-white text-sm font-bold shadow-md hover:shadow-lg transition-all">
                    <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                    <span class="hidden xs:inline">Inscriptions</span>
                </a>
                <a href="{{ route('external.dashboard') }}"
                   class="inline-flex items-center justify-center w-11 h-11 rounded-xl bg-white border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50 text-slate-600 hover:text-indigo-600 active:scale-95 transition-all"
                   aria-label="Retour à l'espace contact">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </header>

    {{-- ============================================================
         FORMULAIRE : SALLE → PÉRIODE
    ============================================================ --}}
    <form method="GET" action="{{ route('public.paiements') }}" id="filtresForm"
          class="bg-white rounded-2xl border border-slate-100 p-4 sm:p-6 mb-6 no-print"
          style="box-shadow: var(--shadow-md);">
        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">

            {{-- Salle --}}
            <div class="{{ $salle ? 'sm:col-span-6 lg:col-span-5' : 'sm:col-span-12' }}">
                <label for="salle_id" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
                    <i class="fa-solid fa-chalkboard-user mr-1.5 text-indigo-500" aria-hidden="true"></i>
                    Salle de classe
                </label>
                <select name="salle_id" id="salle_id" required
                        class="w-full px-4 py-3 rounded-xl border-2 border-slate-200 bg-slate-50 text-sm font-medium text-slate-800 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all cursor-pointer">
                    <option value="">— Choisir une salle —</option>
                    @foreach($salles as $s)
                        <option value="{{ $s->id }}" @selected($salleId == $s->id)>
                            {{ $s->nom }}@if($s->section) · {{ $s->section->nom }}@endif
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Période --}}
            @if($salle)
                <div class="sm:col-span-6 lg:col-span-5">
                    <label for="periode" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
                        <i class="fa-regular fa-calendar mr-1.5 text-indigo-500" aria-hidden="true"></i>
                        {{ $typePeriode === 'tranche' ? 'Tranche' : 'Mois' }} déjà encaissé
                    </label>
                    <select name="periode" id="periode"
                            @if($periodesDisponibles->isEmpty()) disabled @endif
                            class="w-full px-4 py-3 rounded-xl border-2 border-slate-200 bg-slate-50 text-sm font-medium text-slate-800 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                        @if($periodesDisponibles->isEmpty())
                            <option value="">— Aucune période avec paiement —</option>
                        @else
                            <option value="">— Choisir —</option>
                            @foreach($periodesDisponibles as $p)
                                <option value="{{ $p }}" @selected($periode == $p)>
                                    @if($typePeriode === 'tranche')
                                        Tranche {{ $p }}
                                    @else
                                        {{ mois_libelle((int) $p) }}
                                    @endif
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>
            @endif

            {{-- Bouton --}}
            @if($salle && $periodesDisponibles->isNotEmpty())
                <div class="sm:col-span-12 lg:col-span-2 flex items-end">
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 active:scale-[.98] text-white text-sm font-bold shadow-md hover:shadow-lg transition-all">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        <span>Afficher</span>
                    </button>
                </div>
            @endif
        </div>

        {{-- Badge type de période --}}
        @if($salle && $typePeriode)
            <div class="mt-4 pt-4 border-t border-slate-100 flex flex-wrap items-center gap-3 text-xs sm:text-sm">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full font-bold
                    {{ $typePeriode === 'tranche'
                        ? 'bg-purple-50 text-purple-700 border border-purple-100'
                        : 'bg-indigo-50 text-indigo-700 border border-indigo-100' }}">
                    <i class="fa-solid {{ $typePeriode === 'tranche' ? 'fa-layer-group' : 'fa-calendar-check' }}" aria-hidden="true"></i>
                    Mode : {{ $typePeriode === 'tranche' ? 'Tranches' : 'Mensuel' }}
                </span>
                <span class="text-slate-500 font-medium">
                    <i class="fa-solid fa-circle-info mr-1 text-slate-400" aria-hidden="true"></i>
                    {{ $periodesDisponibles->count() }} période(s) avec paiement enregistré
                </span>
            </div>
        @endif
    </form>

    {{-- ============================================================
         CONTENU PRINCIPAL
    ============================================================ --}}
    @if($salle && $periode && $anneeActive)
        @php
            $totalInscrits     = $inscrits->count();
            $totalPayes        = $paiements->count();
            $totalAttendu      = $paiements->sum('montant_attendu_usd');
            $totalEncaisse     = $paiements->sum('montant_paye_usd');
            $totalReste        = max(0, $totalAttendu - $totalEncaisse);
            $tauxRecouvrement  = $totalAttendu > 0 ? round($totalEncaisse / $totalAttendu * 100) : 0;
            $tauxParticipation = $totalInscrits > 0 ? round($totalPayes / $totalInscrits * 100) : 0;
        @endphp

        {{-- ============================================================
             STATISTIQUES
        ============================================================ --}}
        <section class="stats-grid grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6 animate-in">
            {{-- Inscrits --}}
            <div class="stat-card bg-white rounded-2xl border border-slate-100 p-4 sm:p-5" style="box-shadow: var(--shadow-sm);">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-600 flex items-center justify-center text-white shadow-md">
                        <i class="fa-solid fa-users text-sm" aria-hidden="true"></i>
                    </div>
                    <span class="text-[10px] font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-full uppercase tracking-wider border border-indigo-100">Inscrits</span>
                </div>
                <p class="stat-value text-2xl sm:text-3xl font-black text-slate-900 tabular-nums leading-none">{{ $totalInscrits }}</p>
                <p class="text-xs text-slate-500 mt-1.5 font-medium">Élèves dans la salle</p>
            </div>

            {{-- Payé --}}
            <div class="stat-card bg-white rounded-2xl border border-slate-100 p-4 sm:p-5" style="box-shadow: var(--shadow-sm);">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center text-white shadow-md">
                        <i class="fa-solid fa-circle-check text-sm" aria-hidden="true"></i>
                    </div>
                    <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full uppercase tracking-wider border border-emerald-100">Payé</span>
                </div>
                <p class="stat-value text-2xl sm:text-3xl font-black text-slate-900 tabular-nums leading-none">{{ $totalPayes }}</p>
                <p class="text-xs text-slate-500 mt-1.5 font-medium">{{ $tauxParticipation }}% de participation</p>
            </div>

            {{-- Encaissé --}}
            <div class="stat-card bg-white rounded-2xl border border-slate-100 p-4 sm:p-5" style="box-shadow: var(--shadow-sm);">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-500 to-violet-600 flex items-center justify-center text-white shadow-md">
                        <i class="fa-solid fa-sack-dollar text-sm" aria-hidden="true"></i>
                    </div>
                    <span class="text-[10px] font-bold text-violet-700 bg-violet-50 px-2 py-0.5 rounded-full uppercase tracking-wider border border-violet-100">Encaissé</span>
                </div>
                <p class="stat-value text-2xl sm:text-3xl font-black text-slate-900 tabular-nums leading-none">
                    {{ number_format((float) $totalEncaisse, 0, ',', ' ') }}
                </p>
                <p class="text-xs text-slate-500 mt-1.5 font-medium">USD — {{ $tauxRecouvrement }}% du attendu</p>
            </div>

            {{-- Reste --}}
            <div class="stat-card bg-white rounded-2xl border border-slate-100 p-4 sm:p-5 col-span-2 lg:col-span-1" style="box-shadow: var(--shadow-sm);">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center text-white shadow-md">
                        <i class="fa-solid fa-hourglass-half text-sm" aria-hidden="true"></i>
                    </div>
                    <span class="text-[10px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full uppercase tracking-wider border border-amber-100">Reste</span>
                </div>
                <p class="stat-value text-2xl sm:text-3xl font-black text-slate-900 tabular-nums leading-none">
                    {{ number_format((float) $totalReste, 0, ',', ' ') }}
                </p>
                <p class="text-xs text-slate-500 mt-1.5 font-medium">USD à recouvrer</p>
            </div>
        </section>

        {{-- ============================================================
             BARRE DE PROGRESSION
        ============================================================ --}}
        <div class="progress-bar-wrapper bg-white rounded-2xl border border-slate-100 p-4 sm:p-5 mb-6"
             style="box-shadow: var(--shadow-sm);">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs sm:text-sm font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-chart-line text-emerald-500" aria-hidden="true"></i>
                    Taux de recouvrement
                </p>
                <p class="text-base sm:text-lg font-black text-emerald-600 tabular-nums leading-none">
                    {{ $tauxRecouvrement }}<span class="text-xs font-bold text-emerald-500 ml-0.5">%</span>
                </p>
            </div>
            <div class="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden"
                 role="progressbar"
                 aria-valuenow="{{ $tauxRecouvrement }}"
                 aria-valuemin="0"
                 aria-valuemax="100"
                 aria-label="Taux de recouvrement">
                <div class="progress-bar-fill h-full bg-gradient-to-r from-emerald-400 via-emerald-500 to-emerald-600 rounded-full"
                     style="width: {{ $tauxRecouvrement }}%"></div>
            </div>
        </div>

        {{-- ============================================================
             BARRE DE RECHERCHE (sticky)
        ============================================================ --}}
        <div class="search-bar bg-white/95 rounded-2xl border border-slate-100 p-3 sm:p-4 mb-6 transition-all no-print"
             id="searchBar"
             style="box-shadow: var(--shadow-sm);">
            <label for="searchInput" class="sr-only">Rechercher un élève</label>
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"
                   aria-hidden="true"></i>
                <input type="search" id="searchInput"
                       placeholder="Rechercher par nom, sexe, statut…"
                       autocomplete="off"
                       class="w-full pl-11 pr-28 py-3 rounded-xl border-2 border-slate-200 bg-slate-50 text-sm font-medium placeholder:text-slate-400 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all">
                <button type="button" id="clearSearch"
                        class="hidden absolute right-2.5 top-1/2 -translate-y-1/2 px-3 py-1.5 rounded-lg text-xs font-bold text-slate-500 bg-slate-100 hover:bg-slate-200 hover:text-slate-700 transition-colors">
                    <i class="fa-solid fa-xmark mr-1" aria-hidden="true"></i> Effacer
                </button>
            </div>
        </div>

        {{-- ============================================================
             TABLEAU / CARTES PAIEMENTS
        ============================================================ --}}
        <section class="bg-white rounded-2xl border border-slate-100 overflow-hidden mb-8"
                 style="box-shadow: var(--shadow-md);">

            {{-- En-tête --}}
            <div class="flex flex-wrap items-center justify-between gap-3 px-4 sm:px-6 py-4 bg-gradient-to-r from-indigo-50 via-white to-emerald-50 border-b border-slate-100">
                <div class="min-w-0 flex-1">
                    <h2 class="text-base sm:text-lg font-bold text-slate-900 truncate flex items-center gap-2.5 flex-wrap">
                        <span class="w-1.5 h-6 rounded-full bg-gradient-to-b from-indigo-500 to-emerald-500 shrink-0" aria-hidden="true"></span>
                        <span class="truncate">{{ $salle->nom }}</span>
                        <span class="text-slate-300 font-normal hidden sm:inline">·</span>
                        <span class="text-indigo-600 font-bold truncate">
                            {{ $typePeriode === 'tranche' ? 'Tranche ' . $periode : mois_libelle((int) $periode) }}
                        </span>
                    </h2>
                    @if($salle->section)
                        <p class="text-xs sm:text-sm text-slate-500 mt-1 ml-4 flex items-center gap-1.5">
                            <i class="fa-solid fa-layer-group text-indigo-500 text-xs" aria-hidden="true"></i>
                            {{ $salle->section->nom }} — Année {{ $anneeActive->libelle ?? '' }}
                        </p>
                    @endif
                </div>
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white border border-emerald-100 shrink-0"
                     style="box-shadow: var(--shadow-sm);">
                    <span class="live-dot" aria-hidden="true"></span>
                    <span class="text-xs sm:text-sm font-bold text-slate-900 tabular-nums" id="totalCount">
                        {{ $paiements->count() }}
                    </span>
                    <span class="text-xs sm:text-sm text-slate-500" id="totalCountLabel">payé(s)</span>
                </div>
            </div>

            {{-- TABLEAU — Tablette+ --}}
            <div class="view-table overflow-x-auto">
                <table class="min-w-full">
                    <caption class="sr-only">Paiements — {{ $salle->nom }}</caption>
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100">
                            <th scope="col" class="px-4 lg:px-6 py-4 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Élève</th>
                            <th scope="col" class="px-4 lg:px-6 py-4 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Sexe</th>
                            <th scope="col" class="px-4 lg:px-6 py-4 text-right text-[11px] font-bold text-slate-500 uppercase tracking-wider">Attendu</th>
                            <th scope="col" class="px-4 lg:px-6 py-4 text-right text-[11px] font-bold text-slate-500 uppercase tracking-wider">Payé</th>
                            <th scope="col" class="px-4 lg:px-6 py-4 text-right text-[11px] font-bold text-slate-500 uppercase tracking-wider">Reste</th>
                            <th scope="col" class="px-4 lg:px-6 py-4 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Date</th>
                            <th scope="col" class="px-4 lg:px-6 py-4 text-center text-[11px] font-bold text-slate-500 uppercase tracking-wider">Statut</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($paiements as $paiement)
                            @php $p = paiement_row_data($paiement); @endphp
                            <tr class="paiement-row row-hover">
                                <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="avatar w-10 h-10 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0" aria-hidden="true">
                                            {{ $p['initiales'] }}
                                        </div>
                                        <p class="paiement-name font-semibold text-slate-900 text-sm truncate">{{ $p['nomComplet'] }}</p>
                                    </div>
                                </td>
                                <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                    @if($p['sexeCode'] === 'M')
                                        <span class="inline-flex items-center gap-1.5 text-[11px] font-bold px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-100">
                                            <i class="fa-solid fa-mars text-[10px]" aria-hidden="true"></i> {{ $p['sexeLibelle'] }}
                                        </span>
                                    @elseif($p['sexeCode'] === 'F')
                                        <span class="inline-flex items-center gap-1.5 text-[11px] font-bold px-2.5 py-1 rounded-full bg-pink-50 text-pink-700 border border-pink-100">
                                            <i class="fa-solid fa-venus text-[10px]" aria-hidden="true"></i> {{ $p['sexeLibelle'] }}
                                        </span>
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                                <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-right">
                                    <span class="text-sm font-semibold text-slate-700 tabular-nums">{{ $p['montantAttenduFormate'] }}</span>
                                </td>
                                <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-right">
                                    <span class="text-sm font-bold text-emerald-600 tabular-nums">{{ $p['montantPayeFormate'] }}</span>
                                </td>
                                <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-right">
                                    @if($p['reste'] > 0)
                                        <span class="text-sm font-bold text-amber-600 tabular-nums">{{ $p['resteFormate'] }}</span>
                                    @else
                                        <span class="text-xs text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-slate-700 tabular-nums font-medium">{{ $p['datePaiement'] }}</span>
                                </td>
                                <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-center">
                                    @if($p['statut'] === 'complet')
                                        <span class="inline-flex items-center gap-1.5 text-[11px] font-bold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <i class="fa-solid fa-circle-check" aria-hidden="true"></i> {{ $p['statutLibelle'] }}
                                        </span>
                                    @elseif($p['statut'] === 'partiel')
                                        <span class="inline-flex items-center gap-1.5 text-[11px] font-bold px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200">
                                            <i class="fa-solid fa-circle-half-stroke" aria-hidden="true"></i> {{ $p['statutLibelle'] }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 text-[11px] font-bold px-2.5 py-1 rounded-full bg-red-50 text-red-700 border border-red-200">
                                            <i class="fa-solid fa-circle-xmark" aria-hidden="true"></i> {{ $p['statutLibelle'] }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 mb-4">
                                        <i class="fa-regular fa-folder-open text-2xl text-slate-400" aria-hidden="true"></i>
                                    </div>
                                    <p class="text-sm font-bold text-slate-700">Aucun paiement</p>
                                    <p class="text-xs text-slate-500 mt-1">Aucun élève n'a payé pour cette période.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- CARTES — Mobile --}}
            <div class="view-cards divide-y divide-slate-100" id="paiementsCards">
                @forelse($paiements as $paiement)
                    @php $p = paiement_row_data($paiement); @endphp
                    <article class="paiement-card card-hover p-4 bg-white">
                        <div class="flex items-start gap-3 mb-3.5">
                            <div class="avatar w-12 h-12 rounded-full flex items-center justify-center text-white text-sm font-bold shrink-0" aria-hidden="true">
                                {{ $p['initiales'] }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-bold text-slate-900 text-sm paiement-name leading-snug">
                                    {{ $p['nomComplet'] }}
                                </p>
                                <div class="flex items-center gap-1.5 mt-2 flex-wrap">
                                    @if($p['sexeCode'] === 'M')
                                        <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-100">
                                            <i class="fa-solid fa-mars text-[10px]" aria-hidden="true"></i> {{ $p['sexeLibelle'] }}
                                        </span>
                                    @elseif($p['sexeCode'] === 'F')
                                        <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-full bg-pink-50 text-pink-700 border border-pink-100">
                                            <i class="fa-solid fa-venus text-[10px]" aria-hidden="true"></i> {{ $p['sexeLibelle'] }}
                                        </span>
                                    @endif
                                    @if($p['statut'] === 'complet')
                                        <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <i class="fa-solid fa-circle-check" aria-hidden="true"></i> {{ $p['statutLibelle'] }}
                                        </span>
                                    @elseif($p['statut'] === 'partiel')
                                        <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200">
                                            <i class="fa-solid fa-circle-half-stroke" aria-hidden="true"></i> {{ $p['statutLibelle'] }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-full bg-red-50 text-red-700 border border-red-200">
                                            <i class="fa-solid fa-circle-xmark" aria-hidden="true"></i> {{ $p['statutLibelle'] }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2.5">
                            <div class="flex items-center gap-2 px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-100">
                                <i class="fa-solid fa-receipt text-slate-500 text-sm shrink-0" aria-hidden="true"></i>
                                <div class="min-w-0">
                                    <p class="text-[9px] font-bold text-slate-500 uppercase tracking-wider">Attendu</p>
                                    <p class="text-xs font-bold text-slate-700 tabular-nums truncate mt-0.5">{{ $p['montantAttenduFormate'] }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 px-3 py-2.5 rounded-xl bg-emerald-50 border border-emerald-100">
                                <i class="fa-solid fa-money-bill-wave text-emerald-600 text-sm shrink-0" aria-hidden="true"></i>
                                <div class="min-w-0">
                                    <p class="text-[9px] font-bold text-emerald-600 uppercase tracking-wider">Payé</p>
                                    <p class="text-xs font-black text-emerald-700 tabular-nums truncate mt-0.5">{{ $p['montantPayeFormate'] }}</p>
                                </div>
                            </div>

                            @if($p['reste'] > 0)
                                <div class="flex items-center gap-2 px-3 py-2.5 rounded-xl bg-amber-50 border border-amber-100">
                                    <i class="fa-solid fa-hourglass-half text-amber-600 text-sm shrink-0" aria-hidden="true"></i>
                                    <div class="min-w-0">
                                        <p class="text-[9px] font-bold text-amber-600 uppercase tracking-wider">Reste</p>
                                        <p class="text-xs font-black text-amber-700 tabular-nums truncate mt-0.5">{{ $p['resteFormate'] }}</p>
                                    </div>
                                </div>
                            @endif

                            <div class="flex items-center gap-2 px-3 py-2.5 rounded-xl bg-indigo-50 border border-indigo-100 {{ $p['reste'] > 0 ? '' : 'col-span-2' }}">
                                <i class="fa-regular fa-calendar text-indigo-600 text-sm shrink-0" aria-hidden="true"></i>
                                <div class="min-w-0">
                                    <p class="text-[9px] font-bold text-indigo-600 uppercase tracking-wider">Date</p>
                                    <p class="text-xs font-bold text-slate-800 tabular-nums truncate mt-0.5">{{ $p['datePaiement'] }}</p>
                                </div>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="px-6 py-16 text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 mb-4">
                            <i class="fa-regular fa-folder-open text-2xl text-slate-400" aria-hidden="true"></i>
                        </div>
                        <p class="text-sm font-bold text-slate-700">Aucun paiement</p>
                    </div>
                @endforelse
            </div>

            {{-- Message vide recherche --}}
            <div id="noResults" class="empty-state px-6 py-16 text-center border-t border-slate-100 bg-slate-50">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-white border border-slate-200 mb-4">
                    <i class="fa-solid fa-magnifying-glass text-2xl text-slate-400" aria-hidden="true"></i>
                </div>
                <p class="text-sm font-bold text-slate-700">Aucun résultat</p>
                <p class="text-xs text-slate-500 mt-1">Aucun élève ne correspond à votre recherche.</p>
                <button type="button" id="resetSearch"
                        class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white text-xs font-bold shadow-md transition-all">
                    <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                    Réinitialiser
                </button>
            </div>
        </section>

        {{-- ============================================================
             SECTION NON-PAYANTS
        ============================================================ --}}
        @if($nonPayants->count() > 0)
            <section class="bg-white rounded-2xl border border-amber-200 overflow-hidden mb-8"
                     style="box-shadow: var(--shadow-md);">
                <div class="flex flex-wrap items-center justify-between gap-3 px-4 sm:px-6 py-4 bg-gradient-to-r from-amber-50 via-white to-amber-50 border-b border-amber-100">
                    <div class="min-w-0 flex-1">
                        <h2 class="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2.5">
                            <span class="w-1.5 h-6 rounded-full bg-gradient-to-b from-amber-400 to-amber-600 shrink-0" aria-hidden="true"></span>
                            <i class="fa-solid fa-triangle-exclamation text-amber-500" aria-hidden="true"></i>
                            <span class="truncate">Élèves n'ayant pas payé</span>
                        </h2>
                        <p class="text-xs text-slate-500 mt-1 ml-6">
                            {{ $nonPayants->count() }} élève(s) sans paiement pour
                            {{ $typePeriode === 'tranche' ? 'la tranche ' . $periode : mois_libelle((int) $periode) }}
                        </p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-amber-100 text-amber-800 text-xs font-black tracking-wide shrink-0">
                        <i class="fa-solid fa-user-clock text-[10px]" aria-hidden="true"></i>
                        {{ $nonPayants->count() }}
                    </span>
                </div>

                <div class="p-4 sm:p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($nonPayants as $inscription)
                            @php
                                $el   = $inscription->eleve;
                                $nom  = _eleve_nom_complet($el);
                                $init = _eleve_initiales($el);
                            @endphp
                            <div class="nonpayant-item flex items-center gap-3 p-3 rounded-xl bg-amber-50 border border-amber-100">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-amber-400 to-amber-500 flex items-center justify-center text-white font-bold text-xs shrink-0 shadow-sm">
                                    {{ $init }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="nonpayant-name text-sm font-bold text-slate-800 truncate">{{ $nom }}</p>
                                    <p class="text-[11px] text-amber-700 font-semibold mt-0.5">
                                        <i class="fa-solid fa-circle-xmark mr-1" aria-hidden="true"></i>
                                        Aucun paiement
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

    @elseif($salle && $periodesDisponibles->isEmpty())
        {{-- Salle choisie, aucun paiement --}}
        <div class="bg-white rounded-2xl border border-slate-100 px-6 py-20 text-center animate-in"
             style="box-shadow: var(--shadow-md);">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gradient-to-br from-amber-50 to-orange-50 mb-5 border border-amber-100">
                <i class="fa-solid fa-inbox text-3xl text-amber-500" aria-hidden="true"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-900">Aucun paiement enregistré</h3>
            <p class="text-sm text-slate-500 mt-2 max-w-md mx-auto leading-relaxed">
                Aucun paiement n'a encore été effectué pour la salle <strong class="text-slate-700">{{ $salle->nom }}</strong>.
                <br>
                Mode de paiement : <strong class="text-slate-700">{{ $typePeriode === 'tranche' ? 'Tranches' : 'Mensuel' }}</strong>.
            </p>
        </div>

    @elseif($salle)
        {{-- Salle choisie, choisir période --}}
        <div class="bg-white rounded-2xl border border-slate-100 px-6 py-20 text-center animate-in"
             style="box-shadow: var(--shadow-md);">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gradient-to-br from-indigo-50 to-violet-50 mb-5 border border-indigo-100">
                <i class="fa-regular fa-calendar-check text-3xl text-indigo-500" aria-hidden="true"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-900">Choisissez une période</h3>
            <p class="text-sm text-slate-500 mt-2 max-w-md mx-auto leading-relaxed">
                Sélectionnez une
                <strong class="text-slate-700">{{ $typePeriode === 'tranche' ? 'tranche' : 'période' }}</strong>
                dans le menu ci-dessus pour afficher les paiements correspondants.
            </p>
        </div>

    @else
        {{-- Aucune salle --}}
        <div class="bg-white rounded-2xl border border-slate-100 px-6 py-20 text-center animate-in"
             style="box-shadow: var(--shadow-md);">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gradient-to-br from-indigo-50 to-violet-50 mb-5 border border-indigo-100">
                <i class="fa-regular fa-hand-pointer text-3xl text-indigo-500" aria-hidden="true"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-900">Sélectionnez une salle de classe</h3>
            <p class="text-sm text-slate-500 mt-2 max-w-md mx-auto leading-relaxed">
                Choisissez une salle pour afficher les périodes déjà encaissées.
            </p>
        </div>
    @endif

    <footer class="text-center text-xs text-slate-400 py-6 no-print">
        &copy; {{ date('Y') }} {{ config('app.name') }} — Tous droits réservés.
    </footer>
</div>

<script>
    (function () {
        'use strict';

        document.addEventListener('DOMContentLoaded', function () {
            const form        = document.getElementById('filtresForm');
            const salleSel    = document.getElementById('salle_id');
            const periodeSel  = document.getElementById('periode');
            const searchInput = document.getElementById('searchInput');
            const searchBar   = document.getElementById('searchBar');

            /* ============================================================
               AUTO-SUBMIT AU CHANGEMENT DE SALLE
               ============================================================ */
            if (salleSel && form) {
                salleSel.addEventListener('change', function () {
                    if (this.value !== '') {
                        form.submit();
                    }
                });
            }

            /* ============================================================
               AUTO-SUBMIT AU CHANGEMENT DE PÉRIODE
               ============================================================ */
            if (periodeSel && form) {
                periodeSel.addEventListener('change', function () {
                    if (this.value !== '') {
                        form.submit();
                    }
                });
            }

            /* ============================================================
               STICKY SEARCH — effet au scroll
               ============================================================ */
            if (searchBar) {
                const observer = new IntersectionObserver(
                    ([entry]) => {
                        searchBar.classList.toggle('is-stuck', !entry.isIntersecting);
                    },
                    { threshold: [1], rootMargin: '-20px 0px 0px 0px' }
                );

                const sentinel = document.createElement('div');
                sentinel.style.height = '1px';
                sentinel.style.visibility = 'hidden';
                searchBar.parentNode.insertBefore(sentinel, searchBar);
                observer.observe(sentinel);
            }

            if (!searchInput) return;

            /* ============================================================
               FILTRE
               ============================================================ */
            const rows       = Array.from(document.querySelectorAll('.paiement-row'));
            const cards      = Array.from(document.querySelectorAll('.paiement-card'));
            const nonPayants = Array.from(document.querySelectorAll('.nonpayant-item'));
            const totalCount = document.getElementById('totalCount');
            const totalLabel = document.getElementById('totalCountLabel');
            const noResults  = document.getElementById('noResults');
            const clearBtn   = document.getElementById('clearSearch');
            const resetBtn   = document.getElementById('resetSearch');

            let debounceTimer = null;

            const normalize = (str) => (str || '')
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '');

            function filter() {
                const query = normalize(searchInput.value.trim());
                let visibleCount = 0;

                rows.forEach(function (row) {
                    const match = query === '' || normalize(row.textContent).includes(query);
                    row.style.display = match ? '' : 'none';
                    if (match) visibleCount++;
                });

                cards.forEach(function (card) {
                    const match = query === '' || normalize(card.textContent).includes(query);
                    card.style.display = match ? '' : 'none';
                });

                nonPayants.forEach(function (item) {
                    const match = query === '' || normalize(item.textContent).includes(query);
                    item.style.display = match ? '' : 'none';
                });

                if (totalCount) totalCount.textContent = visibleCount;
                if (totalLabel) totalLabel.textContent = visibleCount <= 1 ? 'payé' : 'payés';

                if (clearBtn) clearBtn.classList.toggle('hidden', query === '');

                if (noResults) {
                    const hasData = rows.length > 0 || cards.length > 0;
                    noResults.classList.toggle('is-visible', hasData && visibleCount === 0);
                }
            }

            function resetSearch() {
                searchInput.value = '';
                filter();
                searchInput.focus();
            }

            searchInput.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(filter, 120);
            });

            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    resetSearch();
                    searchInput.blur();
                }
            });

            if (clearBtn) clearBtn.addEventListener('click', resetSearch);
            if (resetBtn) resetBtn.addEventListener('click', resetSearch);

            /* ============================================================
               RACCOURCI CLAVIER — "/" pour focus la recherche
               ============================================================ */
            document.addEventListener('keydown', function (e) {
                if (e.key === '/' && document.activeElement !== searchInput) {
                    const tag = document.activeElement.tagName;
                    if (tag !== 'INPUT' && tag !== 'TEXTAREA' && tag !== 'SELECT') {
                        e.preventDefault();
                        searchInput.focus();
                    }
                }
            });
        });
    })();
</script>
</body>
</html>