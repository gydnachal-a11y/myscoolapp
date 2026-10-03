@extends('layouts.admin')

@section('content')
<div class="index-container">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap">

    {{-- ============ EN-TÊTE ============ --}}
    <div class="index-header" data-reveal="auto">
        <div class="index-header-text">
            <h1 class="index-title"><strong>Salles de classe</strong></h1>
            <p class="index-subtitle">Gérez les salles et leurs paramètres financiers</p>
        </div>
        <a href="{{ route('admin.salles-de-classe.create') }}" class="btn-primary">
            <i class="fa-solid fa-plus"></i>
            <span>Nouvelle salle</span>
        </a>
    </div>

    {{-- ============ STATS ============ --}}
    <div class="stats-grid stats-grid-4">
        <div class="stat-card border-indigo" data-reveal="auto" data-delay="1">
            <div class="stat-icon"><i class="fa-solid fa-door-open"></i></div>
            <div class="stat-content">
                <p class="stat-label">Total salles</p>
                <p class="stat-value">{{ $salles->total() }}</p>
            </div>
        </div>
        <div class="stat-card border-purple" data-reveal="auto" data-delay="2">
            <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
            <div class="stat-content">
                <p class="stat-label">Capacité totale</p>
                <p class="stat-value">{{ $salles->sum('capacite_max') }}</p>
            </div>
        </div>
        <div class="stat-card border-green" data-reveal="auto" data-delay="3">
            <div class="stat-icon"><i class="fa-solid fa-calendar-check"></i></div>
            <div class="stat-content">
                <p class="stat-label">Mode mensuel</p>
                <p class="stat-value">{{ $salles->where('mode_paiement','mensuel')->count() }}</p>
            </div>
        </div>
        <div class="stat-card border-orange" data-reveal="auto" data-delay="4">
            <div class="stat-icon"><i class="fa-solid fa-layer-group"></i></div>
            <div class="stat-content">
                <p class="stat-label">Mode tranches</p>
                <p class="stat-value">{{ $salles->where('mode_paiement','tranche')->count() }}</p>
            </div>
        </div>
    </div>

    {{-- ============ FILTRES ============ --}}
    <div class="filter-card" data-reveal="auto" data-delay="1">
        <button type="button" class="filter-toggle" id="filterToggle" aria-expanded="false" aria-controls="filterForm">
            <span class="filter-toggle-label">
                <i class="fa-solid fa-sliders"></i>
                Filtres
                @php
                    $activeFilters = collect([request('search'), request('mode_paiement')])
                        ->filter(fn($v) => !empty($v))->count();
                @endphp
                @if($activeFilters > 0)
                    <span class="filter-badge">{{ $activeFilters }}</span>
                @endif
            </span>
            <i class="fa-solid fa-chevron-down filter-chevron"></i>
        </button>

        <form method="GET" action="{{ route('admin.salles-de-classe.index') }}" class="filter-form" id="filterForm">
            <div class="filter-field">
                <label class="filter-label" for="f-search">Recherche</label>
                <div class="filter-input-wrap">
                    <i class="fa-solid fa-magnifying-glass filter-input-icon"></i>
                    <input type="text" id="f-search" name="search" value="{{ request('search') }}"
                           placeholder="Nom de la salle..." class="filter-input">
                </div>
            </div>
            <div class="filter-field">
                <label class="filter-label" for="f-mode">Mode de paiement</label>
                <select name="mode_paiement" id="f-mode" class="filter-select">
                    <option value="">Tous</option>
                    <option value="mensuel" {{ request('mode_paiement') == 'mensuel' ? 'selected' : '' }}>Mensuel</option>
                    <option value="tranche" {{ request('mode_paiement') == 'tranche' ? 'selected' : '' }}>Par tranche</option>
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn-filter">
                    <i class="fa-solid fa-filter"></i> Filtrer
                </button>
                @if(request('search') || request('mode_paiement'))
                    <a href="{{ route('admin.salles-de-classe.index') }}" class="btn-reset">
                        <i class="fa-solid fa-rotate-left"></i> Réinitialiser
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- ============ TABLEAU / CARTES ============ --}}
    <div class="table-card" data-reveal="auto" data-delay="2">
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Section</th>
                        <th>Option</th>
                        <th>Capacité</th>
                        <th>Âge</th>
                        <th>Mode</th>
                        <th>Frais insc.</th>
                        <th>Frais périodiques</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($salles as $salle)
                    <tr>
                        <td data-label="Nom">
                            <div class="salle-cell">
                                <div class="avatar">{{ strtoupper(substr($salle->nom, 0, 2)) }}</div>
                                <span class="cell-primary">{{ $salle->nom }}</span>
                            </div>
                        </td>
                        <td data-label="Section">
                            <span class="cell-text">{{ $salle->section?->nom ?? '—' }}</span>
                        </td>
                        <td data-label="Option">
                            <span class="cell-text">{{ $salle->option?->nom ?? '—' }}</span>
                        </td>
                        <td data-label="Capacité">
                            <span class="capacity-badge">{{ $salle->capacite_max }} places</span>
                        </td>
                        <td data-label="Âge">
                            <span class="cell-text">{{ $salle->age_min }} - {{ $salle->age_max }} ans</span>
                        </td>
                        <td data-label="Mode">
                            @if($salle->mode_paiement === 'mensuel')
                                <span class="badge badge-blue" title="Paiement mensuel">
                                    <i class="fa-solid fa-calendar-check"></i> Mensuel
                                </span>
                            @else
                                <span class="badge badge-orange" title="Paiement par tranches">
                                    <i class="fa-solid fa-layer-group"></i> Tranche
                                </span>
                            @endif
                        </td>
                        <td data-label="Frais insc.">
                            <span class="cell-strong">{{ number_format($salle->frais_inscription, 0) }} $</span>
                        </td>
                        <td data-label="Frais périodiques">
                            @if($salle->mode_paiement === 'mensuel')
                                <div class="frais-detail">
                                    <span class="frais-montant">{{ number_format($salle->frais_scolarite_mensuel ?? 0, 0) }} $</span>
                                    <span class="frais-periode">/ mois</span>
                                    <small class="frais-annuel">(annuel : {{ number_format($salle->frais_annuel, 0) }} $)</small>
                                </div>
                            @else
                                <div class="frais-detail">
                                    <span class="frais-montant">{{ number_format($salle->frais_par_tranche ?? 0, 0) }} $</span>
                                    <span class="frais-periode">/ tranche</span>
                                    <small class="frais-tranches">{{ $salle->nombre_tranches ?? 1 }} tranche(s)</small>
                                </div>
                            @endif
                        </td>
                        <td data-label="Actions" class="text-right action-cell">
                            <div class="action-icons">
                                <a href="{{ route('admin.salles-de-classe.show', $salle) }}" class="action-icon" title="Voir détails" aria-label="Voir {{ $salle->nom }}">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.salles-de-classe.edit', $salle) }}" class="action-icon" title="Modifier" aria-label="Modifier {{ $salle->nom }}">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <form action="{{ route('admin.salles-de-classe.destroy', $salle) }}" method="POST" class="inline-form" onsubmit="return confirm('Supprimer cette salle ?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="action-icon danger" title="Supprimer" aria-label="Supprimer {{ $salle->nom }}">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr class="empty-row">
                        <td colspan="9" class="empty-cell">
                            <div class="empty-state">
                                <i class="fa-solid fa-inbox"></i>
                                <p>
                                    @if(request('search') || request('mode_paiement'))
                                        Aucune salle trouvée avec ces critères.
                                    @else
                                        Aucune salle de classe enregistrée.
                                    @endif
                                </p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ============ PAGINATION ============ --}}
    @if($salles->hasPages())
        <div class="pagination-wrapper" data-reveal="auto">
            {{ $salles->appends(request()->query())->links() }}
        </div>
    @endif
</div>

<style>
    /* ============================================================
       POLICES & TOKENS
    ============================================================ */
    .index-container {
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        --c-primary: #6366f1;
        --c-primary-dark: #4f46e5;
        --c-ink-900: #0f172a;
        --c-ink-700: #334155;
        --c-ink-600: #475569;
        --c-ink-500: #64748b;
        --c-ink-400: #94a3b8;
        --c-gray-100: #f1f5f9;
        --c-gray-200: #e2e8f0;
        --c-gray-50: #f8fafc;
        --c-white: #ffffff;

        --radius-md: 12px;
        --radius-lg: 16px;
        --radius-full: 9999px;

        --shadow-sm: 0 1px 3px rgba(15,23,42,.06), 0 1px 2px rgba(15,23,42,.04);
        --shadow-md: 0 8px 20px rgba(15,23,42,.06), 0 2px 4px rgba(15,23,42,.04);
        --shadow-lg: 0 16px 32px rgba(15,23,42,.08), 0 4px 8px rgba(15,23,42,.04);

        --ease-out-expo: cubic-bezier(.16, 1, .3, 1);
        --ease-soft: cubic-bezier(.4, 0, .2, 1);

        max-width: 1320px;
        margin: 0 auto;
        padding: clamp(1rem, 2.5vw, 2rem) clamp(.75rem, 2vw, 1.25rem);
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    .index-container *,
    .index-container *::before,
    .index-container *::after { box-sizing: border-box; }

    .index-container h1,
    .index-container h2 {
        font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
        letter-spacing: -0.02em;
    }

    /* ============================================================
       EN-TÊTE
    ============================================================ */
    .index-header {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
        margin-bottom: clamp(1.5rem, 3vw, 2.25rem);
    }

    .index-title {
        font-size: clamp(1.4rem, 3vw, 1.85rem);
        font-weight: 700;
        color: var(--c-ink-900);
        margin: 0 0 .25rem;
        line-height: 1.15;
    }
    .index-title strong { font-weight: 800; }

    .index-subtitle {
        color: var(--c-ink-400);
        font-size: clamp(.85rem, 1.4vw, .95rem);
        margin: 0;
        line-height: 1.5;
    }

    .btn-primary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: .8rem 1.4rem;
        min-height: 46px;
        background: var(--c-ink-900);
        color: #fff;
        border-radius: var(--radius-md);
        font-family: inherit;
        font-weight: 600;
        font-size: .92rem;
        text-decoration: none;
        transition: all 400ms var(--ease-out-expo);
        box-shadow: var(--shadow-sm);
        white-space: nowrap;
        border: none;
        cursor: pointer;
        text-align: center;
    }
    .btn-primary i { font-size: .95rem; }
    .btn-primary:hover {
        background: var(--c-primary);
        transform: translateY(-2px);
        box-shadow: 0 12px 28px rgba(99,102,241,.35);
    }
    .btn-primary:active { transform: translateY(0); }

    /* ============================================================
       STATS CARDS
    ============================================================ */
    .stats-grid {
        display: grid;
        gap: clamp(.75rem, 1.5vw, 1.25rem);
        margin-bottom: clamp(1.25rem, 2.5vw, 2rem);
    }
    .stats-grid-4 { grid-template-columns: 1fr; }

    .stat-card {
        background: var(--c-white);
        padding: clamp(1rem, 2vw, 1.35rem) clamp(1rem, 2vw, 1.4rem);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
        display: flex;
        align-items: center;
        gap: clamp(.75rem, 1.5vw, 1.1rem);
        border-left: 4px solid;
        transition: transform 400ms var(--ease-out-expo), box-shadow 400ms var(--ease-out-expo);
        min-width: 0;
    }
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-md);
    }

    .border-indigo { border-left-color: #667eea; }
    .border-purple { border-left-color: #a855f7; }
    .border-green  { border-left-color: #22c55e; }
    .border-orange { border-left-color: #f97316; }

    .stat-icon {
        width: clamp(42px, 5vw, 52px);
        height: clamp(42px, 5vw, 52px);
        border-radius: var(--radius-md);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: clamp(1.1rem, 2vw, 1.4rem);
        flex-shrink: 0;
        transition: transform 400ms var(--ease-out-expo);
    }
    .stat-card:hover .stat-icon { transform: scale(1.08) rotate(-5deg); }

    .border-indigo .stat-icon { background: #eef2ff; color: #667eea; }
    .border-purple .stat-icon { background: #faf5ff; color: #a855f7; }
    .border-green  .stat-icon { background: #f0fdf4; color: #22c55e; }
    .border-orange .stat-icon { background: #fff7ed; color: #f97316; }

    .stat-content { min-width: 0; flex: 1; }

    .stat-label {
        font-size: clamp(.7rem, 1.2vw, .78rem);
        text-transform: uppercase;
        letter-spacing: .06em;
        color: var(--c-ink-400);
        font-weight: 700;
        margin: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .stat-value {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: clamp(1.4rem, 3vw, 1.75rem);
        font-weight: 800;
        color: var(--c-ink-900);
        margin: .15rem 0 0;
        line-height: 1.1;
        letter-spacing: -0.02em;
    }

    /* ============================================================
       FILTRES
    ============================================================ */
    .filter-card {
        background: var(--c-white);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
        padding: clamp(1rem, 2vw, 1.5rem);
        margin-bottom: clamp(1.25rem, 2.5vw, 2rem);
        border: 1px solid var(--c-gray-100);
    }

    .filter-toggle {
        display: none;
        width: 100%;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        padding: .85rem 1.1rem;
        background: var(--c-gray-50);
        border: 1.5px solid var(--c-gray-200);
        border-radius: var(--radius-md);
        font-family: inherit;
        font-weight: 600;
        font-size: .95rem;
        color: var(--c-ink-700);
        cursor: pointer;
        transition: all 300ms var(--ease-soft);
        min-height: 48px;
    }
    .filter-toggle:hover { border-color: var(--c-primary); color: var(--c-primary); }
    .filter-toggle-label { display: inline-flex; align-items: center; gap: .6rem; }

    .filter-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 22px;
        height: 22px;
        padding: 0 7px;
        background: var(--c-primary);
        color: #fff;
        border-radius: var(--radius-full);
        font-size: .72rem;
        font-weight: 700;
    }

    .filter-chevron {
        font-size: .85rem;
        transition: transform 400ms var(--ease-out-expo);
        color: var(--c-ink-400);
    }
    .filter-toggle[aria-expanded="true"] .filter-chevron { transform: rotate(180deg); }

    .filter-form {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .filter-field {
        display: flex;
        flex-direction: column;
        gap: .45rem;
        min-width: 0;
    }

    .filter-label {
        font-size: .78rem;
        font-weight: 700;
        color: var(--c-ink-600);
        letter-spacing: .02em;
    }

    .filter-input-wrap {
        position: relative;
        display: flex;
        align-items: center;
    }
    .filter-input-icon {
        position: absolute;
        left: 1rem;
        color: var(--c-ink-400);
        font-size: .85rem;
        pointer-events: none;
    }

    .filter-input,
    .filter-select {
        width: 100%;
        padding: .8rem 1rem;
        min-height: 46px;
        border: 1.5px solid var(--c-gray-200);
        border-radius: var(--radius-md);
        background: var(--c-gray-50);
        font-family: inherit;
        font-size: .92rem;
        color: var(--c-ink-900);
        transition: all 300ms var(--ease-soft);
        outline: none;
        -webkit-appearance: none;
        appearance: none;
    }

    .filter-input { padding-left: 2.4rem; }

    .filter-select {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%2364748b' d='M6 8L0 0h12z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 1rem center;
        padding-right: 2.5rem;
        cursor: pointer;
    }

    .filter-input:focus,
    .filter-select:focus {
        border-color: var(--c-primary);
        background: var(--c-white);
        box-shadow: 0 0 0 4px rgba(99,102,241,.12);
    }

    .filter-actions {
        display: flex;
        gap: .6rem;
        flex-direction: column;
        align-items: stretch;
    }

    .btn-filter,
    .btn-reset {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: .8rem 1.35rem;
        min-height: 46px;
        border-radius: var(--radius-md);
        font-family: inherit;
        font-weight: 600;
        font-size: .9rem;
        cursor: pointer;
        transition: all 300ms var(--ease-soft);
        text-decoration: none;
        border: none;
        white-space: nowrap;
    }
    .btn-filter {
        background: var(--c-ink-900);
        color: #fff;
    }
    .btn-filter:hover {
        background: var(--c-primary);
        transform: translateY(-2px);
        box-shadow: 0 10px 24px rgba(99,102,241,.3);
    }
    .btn-reset {
        background: var(--c-white);
        border: 1.5px solid var(--c-gray-200);
        color: var(--c-ink-500);
    }
    .btn-reset:hover {
        border-color: var(--c-primary);
        color: var(--c-primary);
        background: var(--c-gray-50);
    }

    /* ============================================================
       TABLEAU
    ============================================================ */
    .table-card {
        background: var(--c-white);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
        overflow: hidden;
        border: 1px solid var(--c-gray-100);
        width: 100%;
    }

    .table-wrapper {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        font-size: .88rem;
        color: var(--c-ink-600);
    }

    .data-table thead th {
        text-align: left;
        padding: .9rem 1rem;
        font-size: .7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: var(--c-ink-400);
        background: var(--c-gray-50);
        border-bottom: 1.5px solid var(--c-gray-200);
        white-space: nowrap;
    }

    .data-table tbody td {
        padding: .95rem 1rem;
        border-bottom: 1px solid var(--c-gray-100);
        vertical-align: middle;
    }

    .data-table tbody tr { transition: background 250ms ease; }
    .data-table tbody tr:hover { background: var(--c-gray-50); }
    .data-table tbody tr:last-child td { border-bottom: none; }

    .text-right { text-align: right; }

    /* ============================================================
       ✅ FIX DÉFINITIF — Restaure <table> en desktop
       Contre les règles du layout qui forcent display: block
    ============================================================ */
    @media (min-width: 1024px) {
        .index-container .data-table {
            display: table !important;
            width: 100% !important;
            max-width: 100%;
            table-layout: fixed !important;
            border-collapse: collapse !important;
        }
        .index-container .data-table thead { display: table-header-group !important; }
        .index-container .data-table tbody { display: table-row-group !important; }
        .index-container .data-table tr    { display: table-row !important; }
        .index-container .data-table th,
        .index-container .data-table td    { display: table-cell !important; }

        /* ✅ Distribution des 9 colonnes = 100% */
        .index-container .data-table thead th:nth-child(1) { width: 14%; } /* Nom */
        .index-container .data-table thead th:nth-child(2) { width: 8%; }  /* Section */
        .index-container .data-table thead th:nth-child(3) { width: 8%; }  /* Option */
        .index-container .data-table thead th:nth-child(4) { width: 8%; }  /* Capacité */
        .index-container .data-table thead th:nth-child(5) { width: 7%; }  /* Âge */
        .index-container .data-table thead th:nth-child(6) { width: 10%; } /* Mode */
        .index-container .data-table thead th:nth-child(7) { width: 8%; }  /* Frais insc. */
        .index-container .data-table thead th:nth-child(8) { width: 15%; } /* Frais périodiques */
        .index-container .data-table thead th:nth-child(9) { width: 22%; } /* Actions */
    }

    /* ✅ TABLETTE : scroll horizontal si nécessaire */
    @media (min-width: 768px) and (max-width: 1023px) {
        .data-table { min-width: 1100px; }
    }

    /* Empêche le contenu de déborder de sa cellule */
    .salle-cell { overflow: hidden; }
    .cell-primary {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .cell-text {
        color: var(--c-ink-600);
        display: inline-block;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .cell-strong {
        font-weight: 700;
        color: var(--c-ink-900);
    }

    /* ============================================================
       CELLULES SPÉCIFIQUES
    ============================================================ */
    .salle-cell {
        display: flex;
        align-items: center;
        gap: .75rem;
        min-width: 0;
    }

    .avatar {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
        color: #4f46e5;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: .8rem;
        flex-shrink: 0;
        transition: transform 400ms var(--ease-out-expo);
    }
    .data-table tbody tr:hover .avatar { transform: scale(1.06) rotate(-3deg); }

    .cell-primary {
        font-weight: 600;
        color: var(--c-ink-900);
        font-size: .92rem;
        min-width: 0;
    }

    .badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: .3rem .7rem;
        border-radius: var(--radius-full);
        font-size: .72rem;
        font-weight: 700;
        white-space: nowrap;
        line-height: 1.4;
    }
    .badge i { font-size: .7rem; }
    .badge-blue   { background: #dbeafe; color: #1d4ed8; }
    .badge-orange { background: #ffedd5; color: #c2410c; }

    .capacity-badge {
        display: inline-block;
        background: var(--c-gray-100);
        color: var(--c-ink-600);
        padding: .25rem .55rem;
        border-radius: 8px;
        font-size: .78rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .frais-detail {
        display: flex;
        flex-direction: column;
        gap: .1rem;
        line-height: 1.3;
    }
    .frais-montant {
        font-weight: 700;
        color: var(--c-ink-900);
        font-size: .9rem;
    }
    .frais-periode {
        font-size: .76rem;
        color: var(--c-ink-500);
    }
    .frais-annuel,
    .frais-tranches {
        font-size: .7rem;
        color: var(--c-ink-400);
    }

    /* ============================================================
       ACTIONS
    ============================================================ */
    .action-cell { white-space: nowrap; }

    .action-icons {
        display: inline-flex;
        justify-content: flex-end;
        gap: .35rem;
    }

    .action-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 10px;
        color: var(--c-ink-500);
        text-decoration: none;
        transition: all 300ms var(--ease-soft);
        background: transparent;
        border: 1.5px solid transparent;
        cursor: pointer;
        font-size: .9rem;
        flex-shrink: 0;
    }
    .action-icon:hover {
        background: var(--c-gray-100);
        color: var(--c-ink-900);
        border-color: var(--c-gray-200);
        transform: translateY(-1px);
    }
    .action-icon.danger:hover {
        background: #fee2e2;
        color: #dc2626;
        border-color: #fecaca;
    }
    .inline-form { display: inline-block; }

    /* ============================================================
       ÉTAT VIDE
    ============================================================ */
    .empty-cell { padding: 0 !important; }
    .empty-state {
        text-align: center;
        padding: clamp(2.5rem, 6vw, 4rem) 1rem;
        color: var(--c-ink-400);
    }
    .empty-state i {
        font-size: clamp(2rem, 5vw, 3rem);
        color: var(--c-gray-200);
        margin-bottom: 1rem;
        display: block;
    }
    .empty-state p { font-size: .95rem; margin: 0; }

    /* ============================================================
       PAGINATION
    ============================================================ */
    .pagination-wrapper {
        margin-top: clamp(1rem, 2.5vw, 1.75rem);
        display: flex;
        justify-content: center;
        overflow-x: auto;
        padding: .25rem 0;
    }
    .pagination-wrapper :deep(> *) { max-width: 100%; }

    /* ============================================================
       ✅ ANIMATIONS BIDIRECTIONNELLES
    ============================================================ */
    .index-container [data-reveal] {
        opacity: 0;
        transform: translateY(32px) scale(.985);
        filter: blur(6px);
        transition:
            opacity 700ms var(--ease-out-expo),
            transform 700ms var(--ease-out-expo),
            filter 700ms var(--ease-out-expo);
        will-change: opacity, transform, filter;
    }

    .index-container [data-reveal="auto"][data-scroll-dir="up"]   { transform: translateY(-32px); }
    .index-container [data-reveal="auto"][data-scroll-dir="down"] { transform: translateY(32px); }

    .index-container [data-reveal].is-visible {
        opacity: 1;
        transform: translate(0, 0) scale(1);
        filter: blur(0);
    }

    .index-container [data-delay="1"] { transition-delay: 60ms; }
    .index-container [data-delay="2"] { transition-delay: 120ms; }
    .index-container [data-delay="3"] { transition-delay: 180ms; }
    .index-container [data-delay="4"] { transition-delay: 240ms; }

    @media (prefers-reduced-motion: reduce) {
        .index-container [data-reveal] {
            opacity: 1 !important;
            transform: none !important;
            filter: none !important;
        }
        .stat-card:hover, .btn-primary:hover, .btn-filter:hover,
        .action-icon:hover, .data-table tbody tr:hover .avatar { transform: none; }
    }

    /* ============================================================
       RESPONSIVE — TABLETTE (≥ 640px)
    ============================================================ */
    @media (min-width: 640px) {
        .index-header {
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        .index-header .btn-primary { width: auto; }

        .stats-grid-4 { grid-template-columns: repeat(2, 1fr); }

        .filter-form {
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            align-items: end;
        }
        .filter-actions {
            grid-column: 1 / -1;
            flex-direction: row;
            justify-content: flex-end;
        }
        .filter-actions > * { flex: 0 1 auto; }
    }

    /* ============================================================
       RESPONSIVE — DESKTOP (≥ 1024px)
    ============================================================ */
    @media (min-width: 1024px) {
        .stats-grid-4 { grid-template-columns: repeat(4, 1fr); }

        .filter-form {
            grid-template-columns: 2fr 1fr auto;
            align-items: end;
            gap: .9rem;
        }
        .filter-actions {
            grid-column: auto;
            flex-direction: row;
            justify-content: flex-end;
        }
    }

    /* ============================================================
       ✅ RESPONSIVE MOBILE (≤ 767px) — Filtres repliables + Cartes
    ============================================================ */
    @media (max-width: 767px) {
        /* Filtres : toggle visible, form replié */
        .filter-toggle { display: flex; }

        .filter-form {
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            margin-top: 0;
            transition:
                max-height 500ms var(--ease-out-expo),
                opacity 350ms ease,
                margin-top 350ms ease,
                padding-top 350ms ease;
            padding-top: 0;
        }
        .filter-form.is-open {
            max-height: 1200px;
            opacity: 1;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--c-gray-100);
        }

        .filter-actions {
            flex-direction: row;
            gap: .5rem;
        }
        .filter-actions > * { flex: 1; }

        /* Tableau → cartes */
        .table-card {
            background: transparent;
            box-shadow: none;
            border: none;
            border-radius: 0;
            overflow: visible;
        }
        .table-wrapper { overflow-x: visible; }

        .data-table {
            display: block;
            min-width: 0;
            font-size: .92rem;
            border-collapse: separate;
            border-spacing: 0;
        }
        .data-table thead { display: none; }
        .data-table tbody { display: block; }

        .data-table tr {
            display: block;
            background: var(--c-white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--c-gray-100);
            margin-bottom: 1rem;
            overflow: hidden;
            transition: box-shadow 300ms var(--ease-soft), transform 300ms var(--ease-soft);
        }
        .data-table tr:not(.empty-row):hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .data-table tbody td {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: .8rem 1.1rem;
            border-bottom: 1px solid var(--c-gray-100);
            text-align: right;
            min-height: 50px;
        }
        .data-table tbody td:last-child { border-bottom: none; }

        .data-table tbody td::before {
            content: attr(data-label);
            font-weight: 700;
            color: var(--c-ink-400);
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            flex-shrink: 0;
            text-align: left;
        }

        /* Cellule Nom : avatar + nom à droite */
        .data-table td[data-label="Nom"] { justify-content: space-between; }
        .data-table td[data-label="Nom"]::before { order: 0; }
        .data-table td[data-label="Nom"] .salle-cell {
            order: 1;
            justify-content: flex-end;
            margin-left: auto;
        }

        /* Cellule Mode : badge à droite */
        .data-table td[data-label="Mode"] { justify-content: space-between; }

        /* Frais périodiques : block à droite */
        .data-table td[data-label="Frais périodiques"] {
            align-items: flex-start;
        }
        .data-table td[data-label="Frais périodiques"] .frais-detail {
            align-items: flex-end;
            text-align: right;
        }

        /* Actions en pied de carte */
        .data-table td.action-cell {
            justify-content: flex-end;
            padding: .75rem 1rem;
            background: var(--c-gray-50);
            border-top: 1.5px solid var(--c-gray-100);
            min-height: auto;
        }
        .data-table td.action-cell::before { display: none; }

        .action-icons { width: 100%; justify-content: flex-end; gap: .5rem; }

        .action-icon {
            width: 42px;
            height: 42px;
            border-radius: 11px;
            background: var(--c-white);
            border-color: var(--c-gray-200);
        }

        /* Ligne vide */
        .data-table tr.empty-row {
            background: var(--c-white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--c-gray-100);
        }
        .data-table tr.empty-row td { display: block; padding: 0; }
        .data-table tr.empty-row td::before { display: none; }

        .cell-text, .cell-primary, .cell-strong { text-align: right; }
    }

    /* ============================================================
       RESPONSIVE — PETIT MOBILE (≤ 480px)
    ============================================================ */
    @media (max-width: 480px) {
        .index-container { padding-inline: .85rem; }

        .stat-card { padding: .9rem 1rem; gap: .75rem; }
        .stat-label { font-size: .68rem; }
        .stat-value { font-size: 1.35rem; }

        .btn-primary {
            padding: .75rem 1.15rem;
            font-size: .88rem;
            width: 100%;
        }

        .data-table tbody td {
            padding: .75rem 1rem;
            font-size: .88rem;
            min-height: 48px;
        }
        .data-table tbody td::before { font-size: .66rem; }

        .action-icon { width: 40px; height: 40px; font-size: .88rem; }

        .avatar { width: 34px; height: 34px; font-size: .75rem; }

        .badge { font-size: .68rem; padding: .25rem .6rem; }
        .badge i { display: none; }

        .frais-montant { font-size: .85rem; }
        .frais-periode { font-size: .72rem; }
        .frais-annuel, .frais-tranches { font-size: .66rem; }
    }

    /* ============================================================
       RESPONSIVE — TRÈS PETIT MOBILE (≤ 360px)
    ============================================================ */
    @media (max-width: 360px) {
        .data-table tbody td {
            font-size: .84rem;
            padding: .65rem .85rem;
            gap: .5rem;
        }
        .data-table tbody td::before { font-size: .62rem; }
    }

    /* ============================================================
       IMPRESSION
    ============================================================ */
    @media print {
        .index-container { padding: 0; max-width: 100%; }
        .btn-primary, .filter-card, .action-cell, .pagination-wrapper { display: none !important; }
        .stat-card, .table-card {
            box-shadow: none;
            border: 1px solid #ddd;
            break-inside: avoid;
        }
        .index-container [data-reveal] {
            opacity: 1 !important;
            transform: none !important;
            filter: none !important;
        }
    }
</style>

<script>
    (function () {
        'use strict';

        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        /* ============================================================
           DÉTECTION DE DIRECTION DE SCROLL
        ============================================================ */
        let lastY = window.scrollY;
        let scrollDir = 'down';

        window.addEventListener('scroll', () => {
            const y = window.scrollY;
            if (Math.abs(y - lastY) > 4) {
                scrollDir = y > lastY ? 'down' : 'up';
                lastY = y;
            }
        }, { passive: true });

        /* ============================================================
           ANIMATIONS BIDIRECTIONNELLES
        ============================================================ */
        function initReveal() {
            const els = document.querySelectorAll('.index-container [data-reveal]');
            if (!els.length) return;

            if (prefersReducedMotion || !('IntersectionObserver' in window)) {
                els.forEach(el => el.classList.add('is-visible'));
                return;
            }

            const io = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    const el = entry.target;
                    const isAuto = el.dataset.reveal === 'auto';

                    if (entry.isIntersecting) {
                        if (isAuto) el.dataset.scrollDir = scrollDir;
                        el.classList.add('is-visible');
                    } else if (entry.intersectionRatio === 0) {
                        el.classList.remove('is-visible');
                        if (isAuto) delete el.dataset.scrollDir;
                    }
                });
            }, {
                threshold: [0, 0.1],
                rootMargin: '0px 0px -30px 0px'
            });

            els.forEach(el => io.observe(el));
        }

        /* ============================================================
           FILTRES REPLIABLES (MOBILE)
        ============================================================ */
        function initFilterToggle() {
            const toggle = document.getElementById('filterToggle');
            const form   = document.getElementById('filterForm');
            if (!toggle || !form) return;

            const mq = window.matchMedia('(max-width: 767px)');

            const handleViewport = () => {
                if (mq.matches) {
                    const hasActive = new URLSearchParams(window.location.search).toString().length > 0;
                    if (hasActive) {
                        form.classList.add('is-open');
                        toggle.setAttribute('aria-expanded', 'true');
                    } else {
                        form.classList.remove('is-open');
                        toggle.setAttribute('aria-expanded', 'false');
                    }
                } else {
                    form.classList.remove('is-open');
                    toggle.setAttribute('aria-expanded', 'false');
                }
            };

            toggle.addEventListener('click', () => {
                const isOpen = form.classList.toggle('is-open');
                toggle.setAttribute('aria-expanded', String(isOpen));
            });

            mq.addEventListener('change', handleViewport);
            handleViewport();
        }

        /* ============================================================
           INIT
        ============================================================ */
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                initReveal();
                initFilterToggle();
            });
        } else {
            initReveal();
            initFilterToggle();
        }
    })();
</script>
@endsection