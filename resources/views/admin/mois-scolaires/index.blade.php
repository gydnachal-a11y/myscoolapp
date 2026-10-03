@extends('layouts.admin')

@section('page_title', 'Mois scolaires')
@section('page_subtitle', "Gérez les mois de l'année académique")

@section('content')
<div class="index-container">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap">

    {{-- ============ EN-TÊTE ============ --}}
    <div class="index-header" data-reveal="auto">
        <div class="index-header-text">
            <h1 class="index-title">
                <strong>Mois scolaires</strong>
                <span class="count-badge">{{ $mois->total() }}</span>
            </h1>
            <p class="index-subtitle">Gérez les mois de l'année académique</p>
        </div>
        <div class="header-actions">
            <form action="{{ route('admin.mois-scolaires.generer') }}" method="POST" class="inline-form">
                @csrf
                <button type="submit" class="btn-secondary"
                        @if($mois->total() > 0)
                            onclick="return confirm('Des mois existent déjà. Continuer quand même ?')"
                        @endif>
                    <i class="fa-solid fa-bolt" aria-hidden="true"></i>
                    <span>Générer</span>
                </button>
            </form>
            <a href="{{ route('admin.mois-scolaires.create') }}" class="btn-primary">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                <span>Ajouter un mois</span>
            </a>
            <form action="{{ route('admin.mois-scolaires.vider') }}" method="POST" class="inline-form"
                  onsubmit="return confirm('Vider TOUS les mois scolaires ? Cette action est irréversible.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-danger" @disabled($mois->total() === 0)>
                    <i class="fa-solid fa-trash" aria-hidden="true"></i>
                    <span>Vider</span>
                </button>
            </form>
        </div>
    </div>

    {{-- ============ FILTRE ============ --}}
    <div class="filter-card" data-reveal="auto" data-delay="1">
        <form method="GET" class="filter-form" role="search">
            <div class="filter-field">
                <label for="annee-scolaire-filter" class="filter-label">Filtrer par année</label>
                <select id="annee-scolaire-filter"
                        name="annee_scolaire_id"
                        onchange="this.form.submit()"
                        class="filter-select">
                    <option value="">Toutes les années</option>
                    @foreach($annees as $an)
                        <option value="{{ $an->id }}" @selected((int) $anneeId === $an->id)>
                            {{ $an->libelle }}
                            @if(!$an->cloturee) · en cours @endif
                        </option>
                    @endforeach
                </select>
            </div>

            @if($anneeId)
                <a href="{{ route('admin.mois-scolaires.index') }}" class="btn-reset" title="Effacer le filtre">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    <span>Effacer le filtre</span>
                </a>
            @endif
        </form>
    </div>

    {{-- ============ TABLEAU / CARTES ============ --}}
    <div class="table-card" data-reveal="auto" data-delay="2">
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">Mois</th>
                        <th scope="col">Nom du mois</th>
                        <th scope="col">Période</th>
                        <th scope="col">Année</th>
                        <th scope="col" class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mois as $m)
                    <tr>
                        <td data-label="Mois">
                            <span class="cell-primary">{{ $m->mois }}</span>
                        </td>

                        <td data-label="Nom du mois">
                            <span class="cell-text">{{ $m->nom_mois ?? '—' }}</span>
                        </td>

                        <td data-label="Période">
                            <span class="cell-text">{{ $m->periode }}</span>
                        </td>

                        <td data-label="Année">
                            @if($m->anneeScolaire)
                                <span class="year-badge">
                                    {{ $m->anneeScolaire->libelle }}
                                    @if(!$m->anneeScolaire->cloturee)
                                        <span class="year-badge-dot" title="Année en cours">●</span>
                                    @endif
                                </span>
                            @else
                                <span class="cell-text">—</span>
                            @endif
                        </td>

                        <td data-label="Actions" class="text-right action-cell">
                            <div class="action-icons">
                                <a href="{{ route('admin.mois-scolaires.edit', $m) }}"
                                   class="action-icon"
                                   aria-label="Modifier le mois {{ $m->mois }}"
                                   title="Modifier">
                                    <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                </a>
                                <form action="{{ route('admin.mois-scolaires.destroy', $m) }}"
                                      method="POST"
                                      class="inline-form"
                                      onsubmit="return confirm('Supprimer le mois « {{ $m->mois }} » ?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="action-icon danger"
                                            aria-label="Supprimer le mois {{ $m->mois }}"
                                            title="Supprimer">
                                        <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr class="empty-row">
                        <td colspan="5" class="empty-cell">
                            <div class="empty-state">
                                <i class="fa-regular fa-calendar-xmark empty-icon" aria-hidden="true"></i>
                                <p class="empty-title">Aucun mois enregistré</p>
                                <p class="empty-text">
                                    @if($anneeId)
                                        Aucun mois pour cette année. Ajoutez-en un ou générez-les automatiquement.
                                    @else
                                        Commencez par générer les mois de l'année en cours.
                                    @endif
                                </p>
                                <div class="empty-actions">
                                    <a href="{{ route('admin.mois-scolaires.create') }}" class="btn-primary">
                                        <i class="fa-solid fa-plus" aria-hidden="true"></i> Ajouter un mois
                                    </a>
                                    <form action="{{ route('admin.mois-scolaires.generer') }}" method="POST" class="inline-form">
                                        @csrf
                                        <button type="submit" class="btn-secondary">
                                            <i class="fa-solid fa-bolt" aria-hidden="true"></i> Générer automatiquement
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ============ PAGINATION ============ --}}
    @if($mois->hasPages())
        <div class="pagination-wrapper" data-reveal="auto">
            {{ $mois->links() }}
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
        gap: 1.25rem;
        margin-bottom: clamp(1.5rem, 3vw, 2.25rem);
    }

    .index-title {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .6rem;
        font-size: clamp(1.4rem, 3vw, 1.85rem);
        font-weight: 700;
        color: var(--c-ink-900);
        margin: 0 0 .25rem;
        line-height: 1.15;
    }
    .index-title strong { font-weight: 800; }

    .count-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 30px;
        height: 28px;
        padding: 0 .65rem;
        background: #eef2ff;
        color: #4f46e5;
        border-radius: var(--radius-full);
        font-size: .78rem;
        font-weight: 700;
    }

    .index-subtitle {
        color: var(--c-ink-400);
        font-size: clamp(.85rem, 1.4vw, .95rem);
        margin: 0;
        line-height: 1.5;
    }

    .header-actions {
        display: flex;
        flex-direction: column;
        gap: .6rem;
        align-items: stretch;
    }

    /* ============================================================
       BOUTONS
    ============================================================ */
    .btn-primary,
    .btn-secondary,
    .btn-danger,
    .btn-reset {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: .8rem 1.35rem;
        min-height: 46px;
        border-radius: var(--radius-md);
        font-family: inherit;
        font-weight: 600;
        font-size: .9rem;
        text-decoration: none;
        transition: all 400ms var(--ease-out-expo);
        border: none;
        cursor: pointer;
        color: #fff;
        white-space: nowrap;
        text-align: center;
    }
    .btn-primary   { background: var(--c-ink-900); }
    .btn-secondary { background: #16a34a; }
    .btn-danger    { background: #ef4444; }

    .btn-primary:hover:not(:disabled),
    .btn-secondary:hover:not(:disabled),
    .btn-danger:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }
    .btn-primary:hover:not(:disabled)   { background: var(--c-primary); box-shadow: 0 12px 28px rgba(99,102,241,.35); }
    .btn-secondary:hover:not(:disabled) { background: #15803d; box-shadow: 0 12px 28px rgba(22,163,74,.3); }
    .btn-danger:hover:not(:disabled)    { background: #dc2626; box-shadow: 0 12px 28px rgba(239,68,68,.3); }

    .btn-primary:disabled,
    .btn-secondary:disabled,
    .btn-danger:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .btn-reset {
        background: transparent;
        color: var(--c-ink-500);
        border: 1.5px solid var(--c-gray-200);
        padding: .65rem 1.1rem;
        font-size: .88rem;
    }
    .btn-reset:hover {
        background: var(--c-gray-50);
        color: var(--c-ink-900);
        border-color: #cbd5e1;
        transform: translateY(-1px);
    }

    .inline-form { display: inline-block; }

    /* ============================================================
       FILTRE
    ============================================================ */
    .filter-card {
        background: var(--c-white);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
        padding: clamp(1rem, 2vw, 1.5rem);
        margin-bottom: clamp(1.25rem, 2.5vw, 2rem);
        border: 1px solid var(--c-gray-100);
    }

    .filter-form {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }

    .filter-field {
        display: flex;
        flex-direction: column;
        gap: .45rem;
        flex: 1;
        min-width: 0;
    }

    .filter-label {
        font-size: .78rem;
        font-weight: 700;
        color: var(--c-ink-600);
        letter-spacing: .02em;
    }

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
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%2364748b' d='M6 8L0 0h12z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 1rem center;
        padding-right: 2.5rem;
        cursor: pointer;
    }
    .filter-select:focus {
        border-color: var(--c-primary);
        background-color: var(--c-white);
        box-shadow: 0 0 0 4px rgba(99,102,241,.12);
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
        font-size: .9rem;
        color: var(--c-ink-600);
    }

    .data-table thead th {
        text-align: left;
        padding: 1rem 1.25rem;
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: var(--c-ink-400);
        background: var(--c-gray-50);
        border-bottom: 1.5px solid var(--c-gray-200);
        white-space: nowrap;
    }

    .data-table tbody td {
        padding: 1rem 1.25rem;
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
    @media (min-width: 768px) {
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

        /* Distribution des 5 colonnes = 100% */
        .index-container .data-table thead th:nth-child(1) { width: 10%; } /* Mois */
        .index-container .data-table thead th:nth-child(2) { width: 22%; } /* Nom du mois */
        .index-container .data-table thead th:nth-child(3) { width: 26%; } /* Période */
        .index-container .data-table thead th:nth-child(4) { width: 20%; } /* Année */
        .index-container .data-table thead th:nth-child(5) { width: 22%; } /* Actions */
    }

    /* ✅ TABLETTE : scroll horizontal si nécessaire */
    @media (min-width: 768px) and (max-width: 1023px) {
        .data-table { min-width: 800px; }
    }

    /* Empêche le débordement du contenu */
    .cell-primary {
        font-weight: 600;
        color: var(--c-ink-900);
        display: inline-block;
        max-width: 100%;
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

    /* ============================================================
       BADGE ANNÉE
    ============================================================ */
    .year-badge {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .3rem .75rem;
        background: var(--c-gray-100);
        border-radius: var(--radius-full);
        font-size: .78rem;
        font-weight: 600;
        color: var(--c-ink-700);
        white-space: nowrap;
    }
    .year-badge-dot {
        color: #16a34a;
        font-size: .5rem;
        line-height: 1;
        animation: pulseDot 2s ease-in-out infinite;
    }
    @keyframes pulseDot {
        0%, 100% { opacity: 1;   transform: scale(1); }
        50%      { opacity: .55; transform: scale(1.2); }
    }

    /* ============================================================
       ACTIONS
    ============================================================ */
    .action-cell { white-space: nowrap; }

    .action-icons {
        display: inline-flex;
        justify-content: flex-end;
        gap: .4rem;
    }

    .action-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        border-radius: 10px;
        color: var(--c-ink-500);
        text-decoration: none;
        transition: all 300ms var(--ease-soft);
        background: transparent;
        border: 1.5px solid transparent;
        cursor: pointer;
        font-size: .95rem;
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

    /* ============================================================
       ÉTAT VIDE
    ============================================================ */
    .empty-cell { padding: 0 !important; }
    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: .75rem;
        text-align: center;
        padding: clamp(2.5rem, 6vw, 4rem) 1rem;
        color: var(--c-ink-400);
    }
    .empty-icon {
        font-size: clamp(2rem, 5vw, 3rem);
        color: #cbd5e1;
        margin-bottom: .25rem;
    }
    .empty-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--c-ink-700);
        margin: 0;
    }
    .empty-text {
        color: var(--c-ink-400);
        font-size: .92rem;
        max-width: 420px;
        margin: 0;
        line-height: 1.55;
    }
    .empty-actions {
        display: flex;
        gap: .75rem;
        margin-top: .75rem;
        flex-wrap: wrap;
        justify-content: center;
    }

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

    @media (prefers-reduced-motion: reduce) {
        .index-container [data-reveal] {
            opacity: 1 !important;
            transform: none !important;
            filter: none !important;
        }
        .btn-primary:hover, .btn-secondary:hover, .btn-danger:hover, .btn-reset:hover,
        .action-icon:hover { transform: none; }
        .year-badge-dot { animation: none; }
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
        .header-actions {
            flex-direction: row;
            align-items: center;
            flex-wrap: wrap;
            gap: .5rem;
        }
        .header-actions .btn-primary,
        .header-actions .btn-secondary,
        .header-actions .btn-danger { width: auto; }
    }

    /* ============================================================
       RESPONSIVE — DESKTOP (≥ 1024px)
    ============================================================ */
    @media (min-width: 1024px) {
        .filter-form {
            flex-direction: row;
            align-items: flex-end;
            flex-wrap: wrap;
        }
        .filter-field { flex: 1; min-width: 220px; }
    }

    /* ============================================================
       ✅ RESPONSIVE MOBILE (≤ 767px) — Cartes
    ============================================================ */
    @media (max-width: 767px) {
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
            padding: .85rem 1.15rem;
            border-bottom: 1px solid var(--c-gray-100);
            text-align: right;
            min-height: 52px;
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

        .data-table tr.empty-row {
            background: var(--c-white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--c-gray-100);
        }
        .data-table tr.empty-row td { display: block; padding: 0; }
        .data-table tr.empty-row td::before { display: none; }

        .cell-primary, .cell-text { text-align: right; }

        .header-actions { flex-direction: column; }
        .header-actions .inline-form { width: 100%; }
        .header-actions .btn-primary,
        .header-actions .btn-secondary,
        .header-actions .btn-danger { width: 100%; }
    }

    /* ============================================================
       RESPONSIVE — PETIT MOBILE (≤ 480px)
    ============================================================ */
    @media (max-width: 480px) {
        .index-container { padding-inline: .85rem; }

        .btn-primary, .btn-secondary, .btn-danger, .btn-reset {
            padding: .75rem 1.15rem;
            font-size: .86rem;
        }

        .data-table tbody td {
            padding: .75rem 1rem;
            font-size: .88rem;
            min-height: 48px;
        }
        .data-table tbody td::before { font-size: .66rem; }

        .action-icon { width: 40px; height: 40px; font-size: .9rem; }

        .year-badge { font-size: .72rem; padding: .25rem .6rem; }

        .count-badge { font-size: .72rem; min-width: 26px; height: 24px; }
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
        .btn-primary, .btn-secondary, .btn-danger, .btn-reset,
        .filter-card, .action-cell, .pagination-wrapper,
        .header-actions, .empty-actions { display: none !important; }
        .table-card { box-shadow: none; border: 1px solid #ddd; break-inside: avoid; }
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

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initReveal);
        } else {
            initReveal();
        }
    })();
</script>
@endsection