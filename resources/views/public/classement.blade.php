<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#4f46e5">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Proclamation – {{ config('app.name') }}</title>

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
            --gold:          #d4a017;
            --gold-soft:     #fef9c3;
            --silver:        #94a3b8;
            --silver-soft:   #f1f5f9;
            --bronze:        #c2703e;
            --bronze-soft:   #fed7aa;
            --slate-50:      #f8fafc;
            --slate-100:     #f1f5f9;
            --slate-200:     #e2e8f0;
            --slate-400:     #94a3b8;
            --slate-500:     #64748b;
            --slate-600:     #475569;
            --slate-700:     #334155;
            --slate-800:     #1e293b;
            --slate-900:     #0f172a;

            --radius-sm:     10px;
            --radius-md:     14px;
            --radius-lg:     18px;
            --radius-xl:     24px;

            --shadow-sm:     0 1px 2px rgba(15,23,42,.04), 0 1px 3px rgba(15,23,42,.06);
            --shadow-md:     0 4px 12px rgba(15,23,42,.06), 0 2px 4px rgba(15,23,42,.04);
            --shadow-lg:     0 12px 32px rgba(79,70,229,.10), 0 4px 8px rgba(15,23,42,.04);
            --shadow-hover:  0 20px 40px rgba(79,70,229,.15), 0 8px 16px rgba(15,23,42,.06);

            --transition:    250ms cubic-bezier(.4,0,.2,1);
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

        @keyframes crown-bounce {
            0%, 100% { transform: translateY(0) rotate(-3deg); }
            50%      { transform: translateY(-4px) rotate(3deg); }
        }

        .animate-in { animation: fade-in .35s ease-out both; }

        .classement-card { animation: fade-in .3s ease-out both; }
        .classement-card:nth-child(1)  { animation-delay: .02s; }
        .classement-card:nth-child(2)  { animation-delay: .04s; }
        .classement-card:nth-child(3)  { animation-delay: .06s; }
        .classement-card:nth-child(4)  { animation-delay: .08s; }
        .classement-card:nth-child(5)  { animation-delay: .10s; }
        .classement-card:nth-child(n+6){ animation-delay: .12s; }

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
           PODIUM
           ============================================================ */
        .podium {
            display: grid;
            grid-template-columns: 1fr 1.15fr 1fr;
            gap: .75rem;
            align-items: end;
            max-width: 720px;
            margin: 0 auto;
        }

        .podium-item {
            border-radius: var(--radius-lg);
            padding: 1.25rem .75rem 1rem;
            text-align: center;
            border: 1px solid var(--slate-200);
            background: #fff;
            position: relative;
            transition: all var(--transition);
        }
        .podium-item:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); }

        .podium-1 {
            background: linear-gradient(180deg, #fffbeb 0%, #ffffff 100%);
            border-color: #fde68a;
            padding-bottom: 1.75rem;
        }
        .podium-2 {
            background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
            border-color: #cbd5e1;
        }
        .podium-3 {
            background: linear-gradient(180deg, #fff7ed 0%, #ffffff 100%);
            border-color: #fed7aa;
        }

        .podium-medal {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border-radius: 9999px;
            font-weight: 900;
            font-size: 1.05rem;
            color: #fff;
            margin-bottom: .75rem;
            box-shadow: 0 6px 14px rgba(0,0,0,.12);
        }
        .podium-1 .podium-medal { background: linear-gradient(135deg, #facc15, #d4a017); }
        .podium-2 .podium-medal { background: linear-gradient(135deg, #cbd5e1, #94a3b8); }
        .podium-3 .podium-medal { background: linear-gradient(135deg, #fb923c, #c2703e); }

        .podium-1 .podium-medal { animation: crown-bounce 2.4s ease-in-out infinite; }

        .podium-name {
            font-weight: 700;
            color: var(--slate-900);
            font-size: .9rem;
            line-height: 1.25;
            margin-bottom: .35rem;
            word-break: break-word;
        }
        .podium-score {
            font-weight: 900;
            font-size: 1.05rem;
            color: var(--primary);
            letter-spacing: -.02em;
        }
        .podium-label {
            font-size: .65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--slate-500);
            margin-bottom: .35rem;
        }

        .podium-crown {
            position: absolute;
            top: -14px;
            left: 50%;
            transform: translateX(-50%);
            color: var(--gold);
            font-size: 1.25rem;
            filter: drop-shadow(0 3px 6px rgba(212,160,23,.35));
        }

        @media (max-width: 480px) {
            .podium { grid-template-columns: 1fr; }
            .podium-1 { order: -1; }
        }

        /* ============================================================
           RANG BADGE
           ============================================================ */
        .rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 9999px;
            font-weight: 800;
            font-size: .85rem;
            border: 1.5px solid var(--slate-200);
        }
        .rank-1 { background: linear-gradient(135deg, #fef9c3, #fde68a); color: #92400e; border-color: #fcd34d; }
        .rank-2 { background: linear-gradient(135deg, #f1f5f9, #e2e8f0); color: #334155; border-color: #cbd5e1; }
        .rank-3 { background: linear-gradient(135deg, #fed7aa, #fdba74); color: #9a3412; border-color: #fdba74; }
        .rank-n { background: var(--slate-50); color: var(--slate-600); }

        /* ============================================================
           PROGRESS BAR
           ============================================================ */
        .bar {
            height: 6px;
            border-radius: 9999px;
            background: var(--slate-100);
            overflow: hidden;
            width: 100%;
            max-width: 120px;
            margin-left: auto;
        }
        .bar > span {
            display: block;
            height: 100%;
            border-radius: 9999px;
            background: linear-gradient(90deg, #818cf8, #4f46e5);
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
            .proclamation-container { max-width: 1200px; }
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
            header .no-print,
            footer { display: none !important; }
            .bg-white { box-shadow: none !important; border: 1px solid #ddd !important; }
            .avatar { background: #ddd !important; color: #333 !important; box-shadow: none !important; }
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
            .podium-item:hover { transform: none; }
        }
    </style>
</head>

<body class="text-slate-800">

<div class="proclamation-container max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10">

    {{-- ============================================================
         EN-TÊTE
    ============================================================ --}}
    <header class="mb-8 sm:mb-10 animate-in">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-5">
            <div class="text-center sm:text-left">
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-50 text-amber-700 text-xs font-bold tracking-wider mb-3 border border-amber-100">
                    <i class="fa-solid fa-trophy" aria-hidden="true"></i>
                    PROCLAMATION DES RÉSULTATS
                </span>
                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black text-slate-900 tracking-tight leading-tight">
                    Proclamation
                </h1>
                <p class="mt-2.5 text-sm sm:text-base text-slate-600 max-w-xl leading-relaxed">
                    Sélectionnez une salle et une période pour découvrir le classement des élèves.
                </p>
            </div>

            <div class="flex items-center justify-center sm:justify-end gap-2 no-print">
                <a href="{{ route('public.inscriptions') }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 active:scale-[.98] text-white text-sm font-bold shadow-md hover:shadow-lg transition-all">
                    <i class="fa-solid fa-clipboard-list" aria-hidden="true"></i>
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
         FORMULAIRE : SALLE + PÉRIODE
    ============================================================ --}}
    <form method="GET" action="{{ route('public.classement') }}"
          class="bg-white rounded-2xl border border-slate-100 p-4 sm:p-6 mb-6 no-print"
          style="box-shadow: var(--shadow-md);">
        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">

            {{-- Salle --}}
            <div class="sm:col-span-6 lg:col-span-5">
                <label for="salle_id" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
                    <i class="fa-solid fa-chalkboard-user mr-1.5 text-indigo-500" aria-hidden="true"></i>
                    Salle de classe
                </label>
                <select name="salle_id" id="salle_id" required
                        class="w-full px-4 py-3 rounded-xl border-2 border-slate-200 bg-slate-50 text-sm font-medium text-slate-800 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all cursor-pointer">
                    <option value="">— Choisir une salle —</option>
                    @foreach($salles as $s)
                        <option value="{{ $s->id }}" @selected($salleId == $s->id)>
                            {{ $s->nom }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Période --}}
            <div class="sm:col-span-6 lg:col-span-5">
                <label for="periode_note_id" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
                    <i class="fa-regular fa-calendar mr-1.5 text-indigo-500" aria-hidden="true"></i>
                    Période
                </label>
                <select name="periode_note_id" id="periode_note_id" required
                        class="w-full px-4 py-3 rounded-xl border-2 border-slate-200 bg-slate-50 text-sm font-medium text-slate-800 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all cursor-pointer">
                    <option value="">— Choisir une période —</option>
                    @foreach($periodes as $p)
                        <option value="{{ $p->id }}" @selected($periodeId == $p->id)>
                            {{ $p->nom }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Bouton --}}
            <div class="sm:col-span-12 lg:col-span-2 flex items-end">
                <button type="submit"
                        class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 active:scale-[.98] text-white text-sm font-bold shadow-md hover:shadow-lg transition-all">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <span>Afficher</span>
                </button>
            </div>
        </div>
    </form>

    {{-- ============================================================
         CONTENU
    ============================================================ --}}
    @if($classement)
        @php
            // ── Calculs statistiques ─────────────────────────────
            $total            = count($classement);
            $moyennes         = collect($classement)->pluck('moyenne')->filter(fn ($m) => $m !== null);
            $moyenneClasse    = $moyennes->isNotEmpty() ? round($moyennes->avg(), 2) : null;
            $meilleureMoyenne = $moyennes->isNotEmpty() ? round($moyennes->max(), 2) : null;
            $reussite         = $moyennes->isNotEmpty()
                ? round($moyennes->filter(fn ($m) => $m >= 10)->count() / $moyennes->count() * 100)
                : 0;

            // ── Podium (top 3) ──────────────────────────────────
            $top3 = array_slice($classement, 0, 3);
            // Ordre visuel : 2 – 1 – 3
            $podiumOrder = [];
            if (isset($top3[1])) $podiumOrder[] = ['item' => $top3[1], 'rank' => 2];
            if (isset($top3[0])) $podiumOrder[] = ['item' => $top3[0], 'rank' => 1];
            if (isset($top3[2])) $podiumOrder[] = ['item' => $top3[2], 'rank' => 3];
        @endphp

        {{-- ============================================================
             STATISTIQUES
        ============================================================ --}}
        <section class="stats-grid grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6 animate-in">
            {{-- Total --}}
            <div class="stat-card bg-white rounded-2xl border border-slate-100 p-4 sm:p-5" style="box-shadow: var(--shadow-sm);">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-600 flex items-center justify-center text-white shadow-md">
                        <i class="fa-solid fa-users text-sm" aria-hidden="true"></i>
                    </div>
                    <span class="text-[10px] font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-full uppercase tracking-wider border border-indigo-100">Élèves</span>
                </div>
                <p class="stat-value text-2xl sm:text-3xl font-black text-slate-900 tabular-nums leading-none">{{ $total }}</p>
                <p class="text-xs text-slate-500 mt-1.5 font-medium">Élève(s) classé(s)</p>
            </div>

            {{-- Moyenne de la classe --}}
            <div class="stat-card bg-white rounded-2xl border border-slate-100 p-4 sm:p-5" style="box-shadow: var(--shadow-sm);">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-500 to-violet-600 flex items-center justify-center text-white shadow-md">
                        <i class="fa-solid fa-chart-line text-sm" aria-hidden="true"></i>
                    </div>
                    <span class="text-[10px] font-bold text-violet-700 bg-violet-50 px-2 py-0.5 rounded-full uppercase tracking-wider border border-violet-100">Moyenne</span>
                </div>
                <p class="stat-value text-2xl sm:text-3xl font-black text-slate-900 tabular-nums leading-none">
                    {{ $moyenneClasse !== null ? number_format($moyenneClasse, 2) : '—' }}
                </p>
                <p class="text-xs text-slate-500 mt-1.5 font-medium">Moyenne de la classe / 20</p>
            </div>

            {{-- Meilleure moyenne --}}
            <div class="stat-card bg-white rounded-2xl border border-slate-100 p-4 sm:p-5" style="box-shadow: var(--shadow-sm);">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center text-white shadow-md">
                        <i class="fa-solid fa-crown text-sm" aria-hidden="true"></i>
                    </div>
                    <span class="text-[10px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full uppercase tracking-wider border border-amber-100">Top</span>
                </div>
                <p class="stat-value text-2xl sm:text-3xl font-black text-slate-900 tabular-nums leading-none">
                    {{ $meilleureMoyenne !== null ? number_format($meilleureMoyenne, 2) : '—' }}
                </p>
                <p class="text-xs text-slate-500 mt-1.5 font-medium">Meilleure moyenne / 20</p>
            </div>

            {{-- Réussite --}}
            <div class="stat-card bg-white rounded-2xl border border-slate-100 p-4 sm:p-5 col-span-2 lg:col-span-1" style="box-shadow: var(--shadow-sm);">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center text-white shadow-md">
                        <i class="fa-solid fa-circle-check text-sm" aria-hidden="true"></i>
                    </div>
                    <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full uppercase tracking-wider border border-emerald-100">Réussite</span>
                </div>
                <p class="stat-value text-2xl sm:text-3xl font-black text-slate-900 tabular-nums leading-none">{{ $reussite }}<span class="text-sm font-bold text-emerald-500">%</span></p>
                <p class="text-xs text-slate-500 mt-1.5 font-medium">Moyenne ≥ 10 / 20</p>
            </div>
        </section>

        {{-- ============================================================
             PODIUM TOP 3
        ============================================================ --}}
        @if(!empty($podiumOrder))
            <section class="bg-white rounded-2xl border border-slate-100 p-5 sm:p-8 mb-6 animate-in"
                     style="box-shadow: var(--shadow-md);">
                <div class="text-center mb-6">
                    <h2 class="text-base sm:text-lg font-bold text-slate-900 flex items-center justify-center gap-2">
                        <i class="fa-solid fa-medal text-amber-500" aria-hidden="true"></i>
                        Podium
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">Les 3 premiers de la période</p>
                </div>

                <div class="podium">
                    @foreach($podiumOrder as $podium)
                        @php
                            $item        = $podium['item'];
                            $rank        = $podium['rank'];
                            $nomComplet  = $item['eleve']->nom_complet ?? '—';
                            $initiales   = collect(explode(' ', $nomComplet))
                                            ->filter()
                                            ->map(fn ($w) => mb_substr($w, 0, 1))
                                            ->take(2)
                                            ->implode('');
                            $moyenne     = $item['moyenne'] !== null ? number_format($item['moyenne'], 2) : '—';
                        @endphp
                        <div class="podium-item podium-{{ $rank }}">
                            @if($rank === 1)
                                <i class="fa-solid fa-crown podium-crown" aria-hidden="true"></i>
                            @endif
                            <div class="podium-medal">{{ $rank }}</div>
                            <p class="podium-label">{{ $rank === 1 ? 'Vainqueur' : ($rank === 2 ? '2ᵉ place' : '3ᵉ place') }}</p>
                            <p class="podium-name">{{ $nomComplet }}</p>
                            <p class="podium-score">{{ $moyenne }}<span class="text-xs font-semibold text-slate-500">/20</span></p>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

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
                       placeholder="Rechercher par nom, moyenne, rang…"
                       autocomplete="off"
                       class="w-full pl-11 pr-28 py-3 rounded-xl border-2 border-slate-200 bg-slate-50 text-sm font-medium placeholder:text-slate-400 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all">
                <button type="button" id="clearSearch"
                        class="hidden absolute right-2.5 top-1/2 -translate-y-1/2 px-3 py-1.5 rounded-lg text-xs font-bold text-slate-500 bg-slate-100 hover:bg-slate-200 hover:text-slate-700 transition-colors">
                    <i class="fa-solid fa-xmark mr-1" aria-hidden="true"></i> Effacer
                </button>
            </div>
        </div>

        {{-- ============================================================
             CLASSEMENT — TABLEAU / CARTES
        ============================================================ --}}
        <section class="bg-white rounded-2xl border border-slate-100 overflow-hidden mb-8"
                 style="box-shadow: var(--shadow-md);">

            {{-- En-tête salle/période --}}
            <div class="flex flex-wrap items-center justify-between gap-3 px-4 sm:px-6 py-4 bg-gradient-to-r from-indigo-50 via-white to-amber-50 border-b border-slate-100">
                <div class="min-w-0 flex-1">
                    <h2 class="text-base sm:text-lg font-bold text-slate-900 truncate flex items-center gap-2.5 flex-wrap">
                        <span class="w-1.5 h-6 rounded-full bg-gradient-to-b from-indigo-500 to-amber-500 shrink-0" aria-hidden="true"></span>
                        <span class="truncate">{{ $salle->nom }}</span>
                        <span class="text-slate-300 font-normal hidden sm:inline">·</span>
                        <span class="text-indigo-600 font-bold truncate">{{ $periode->nom }}</span>
                    </h2>
                </div>
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white border border-indigo-100 shrink-0"
                     style="box-shadow: var(--shadow-sm);">
                    <span class="live-dot" aria-hidden="true"></span>
                    <span class="text-xs sm:text-sm font-bold text-slate-900 tabular-nums" id="totalCount">
                        {{ $total }}
                    </span>
                    <span class="text-xs sm:text-sm text-slate-500" id="totalCountLabel">élève(s)</span>
                </div>
            </div>

            {{-- TABLEAU — Tablette+ --}}
            <div class="view-table overflow-x-auto">
                <table class="min-w-full" id="classementTable">
                    <caption class="sr-only">Classement des élèves — {{ $salle->nom }} — {{ $periode->nom }}</caption>
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100">
                            <th scope="col" class="px-4 lg:px-6 py-4 text-center text-[11px] font-bold text-slate-500 uppercase tracking-wider">Rang</th>
                            <th scope="col" class="px-4 lg:px-6 py-4 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Élève</th>
                            <th scope="col" class="px-4 lg:px-6 py-4 text-right text-[11px] font-bold text-slate-500 uppercase tracking-wider">Moyenne / 20</th>
                            <th scope="col" class="px-4 lg:px-6 py-4 text-right text-[11px] font-bold text-slate-500 uppercase tracking-wider">Pourcentage</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($classement as $item)
                            @php
                                $rang        = $item['rang'];
                                $nomComplet  = $item['eleve']->nom_complet ?? '—';
                                $initiales   = collect(explode(' ', $nomComplet))
                                                ->filter()
                                                ->map(fn ($w) => mb_substr($w, 0, 1))
                                                ->take(2)
                                                ->implode('');
                                $moyenne     = $item['moyenne'] !== null ? number_format($item['moyenne'], 2) : null;
                                $pourcentage = $item['pourcentage'];
                                $rangClass   = $rang === 1 ? 'rank-1' : ($rang === 2 ? 'rank-2' : ($rang === 3 ? 'rank-3' : 'rank-n'));
                            @endphp
                            <tr class="classement-row row-hover">
                                <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-center">
                                    <span class="rank-badge {{ $rangClass }}">{{ $rang }}</span>
                                </td>
                                <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="avatar w-10 h-10 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0"
                                             aria-hidden="true">
                                            {{ $initiales }}
                                        </div>
                                        <p class="classement-name font-semibold text-slate-900 text-sm truncate">{{ $nomComplet }}</p>
                                    </div>
                                </td>
                                <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-right">
                                    @if($moyenne !== null)
                                        <span class="text-sm font-bold text-slate-800 tabular-nums">{{ $moyenne }}</span>
                                    @else
                                        <span class="text-xs text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-right">
                                    @if($pourcentage !== null)
                                        <div class="inline-flex items-center gap-2 justify-end">
                                            <div class="bar"><span style="width: {{ min(100, $pourcentage) }}%"></span></div>
                                            <span class="text-sm font-bold text-emerald-600 tabular-nums">{{ $pourcentage }}%</span>
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- CARTES — Mobile --}}
            <div class="view-cards divide-y divide-slate-100" id="classementCards">
                @foreach($classement as $item)
                    @php
                        $rang        = $item['rang'];
                        $nomComplet  = $item['eleve']->nom_complet ?? '—';
                        $initiales   = collect(explode(' ', $nomComplet))
                                        ->filter()
                                        ->map(fn ($w) => mb_substr($w, 0, 1))
                                        ->take(2)
                                        ->implode('');
                        $moyenne     = $item['moyenne'] !== null ? number_format($item['moyenne'], 2) : null;
                        $pourcentage = $item['pourcentage'];
                        $rangClass   = $rang === 1 ? 'rank-1' : ($rang === 2 ? 'rank-2' : ($rang === 3 ? 'rank-3' : 'rank-n'));
                    @endphp
                    <article class="classement-card card-hover p-4 bg-white">
                        <div class="flex items-start gap-3 mb-3.5">
                            <span class="rank-badge {{ $rangClass }} shrink-0">{{ $rang }}</span>
                            <div class="avatar w-12 h-12 rounded-full flex items-center justify-center text-white text-sm font-bold shrink-0"
                                 aria-hidden="true">
                                {{ $initiales }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-bold text-slate-900 text-sm classement-name leading-snug">
                                    {{ $nomComplet }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2.5">
                            <div class="flex items-center gap-2 px-3 py-2.5 rounded-xl bg-indigo-50 border border-indigo-100">
                                <i class="fa-solid fa-chart-line text-indigo-600 text-sm shrink-0" aria-hidden="true"></i>
                                <div class="min-w-0">
                                    <p class="text-[9px] font-bold text-indigo-600 uppercase tracking-wider">Moyenne</p>
                                    <p class="text-xs font-bold text-slate-800 tabular-nums truncate mt-0.5">
                                        {{ $moyenne !== null ? $moyenne . ' / 20' : '—' }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 px-3 py-2.5 rounded-xl bg-emerald-50 border border-emerald-100">
                                <i class="fa-solid fa-percent text-emerald-600 text-sm shrink-0" aria-hidden="true"></i>
                                <div class="min-w-0">
                                    <p class="text-[9px] font-bold text-emerald-600 uppercase tracking-wider">Pourcentage</p>
                                    <p class="text-xs font-black text-emerald-700 tabular-nums truncate mt-0.5">
                                        {{ $pourcentage !== null ? $pourcentage . '%' : '—' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
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

    @else
        {{-- ============================================================
             ÉTAT VIDE — AUCUNE SÉLECTION
        ============================================================ --}}
        <div class="bg-white rounded-2xl border border-slate-100 px-6 py-20 text-center animate-in"
             style="box-shadow: var(--shadow-md);">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gradient-to-br from-indigo-50 to-violet-50 mb-5 border border-indigo-100">
                <i class="fa-solid fa-trophy text-3xl text-indigo-500" aria-hidden="true"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-900">Sélectionnez une salle et une période</h3>
            <p class="text-sm text-slate-500 mt-2 max-w-md mx-auto leading-relaxed">
                Choisissez une salle de classe et une période dans le menu ci-dessus pour afficher la proclamation.
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
            const searchInput = document.getElementById('searchInput');
            const searchBar   = document.getElementById('searchBar');

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
               ÉLÉMENTS DE FILTRE
               ============================================================ */
            const rows       = Array.from(document.querySelectorAll('.classement-row'));
            const cards      = Array.from(document.querySelectorAll('.classement-card'));
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

                if (totalCount) totalCount.textContent = visibleCount;
                if (totalLabel) totalLabel.textContent = visibleCount <= 1 ? 'élève' : 'élèves';

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