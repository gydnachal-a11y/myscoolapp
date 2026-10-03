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
            <h1 class="index-title"><strong>Salaires</strong> du personnel</h1>
            <p class="index-subtitle">Gérez la rémunération du personnel enseignant</p>
        </div>

        {{-- ✅ Actions : Personnel + Avances --}}
        <div class="header-actions">
            <a href="{{ route('admin.users.index') }}" class="btn-secondary">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Personnel</span>
            </a>

            {{-- ✅ NOUVEAU : lien vers les avances --}}
            <a href="{{ route('admin.avances.index') }}" class="btn-secondary btn-avances">
                <i class="fa-solid fa-hand-holding-dollar"></i>
                <span>Avances</span>
            </a>
        </div>
    </div>

    {{-- ============ STATISTIQUES ============ --}}
    <div class="stats-grid stats-grid-5">
        <div class="stat-card border-indigo" data-reveal="auto" data-delay="1">
            <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
            <div class="stat-content">
                <p class="stat-value">{{ $users->total() }}</p>
                <p class="stat-label">Total personnel</p>
            </div>
        </div>
        <div class="stat-card border-blue" data-reveal="auto" data-delay="2">
            <div class="stat-icon"><i class="fa-solid fa-robot"></i></div>
            <div class="stat-content">
                <p class="stat-value">{{ $nbAutomatique ?? 0 }}</p>
                <p class="stat-label">Salaire automatique</p>
            </div>
        </div>
        <div class="stat-card border-gray" data-reveal="auto" data-delay="3">
            <div class="stat-icon"><i class="fa-solid fa-user-pen"></i></div>
            <div class="stat-content">
                <p class="stat-value">{{ $nbManuel ?? 0 }}</p>
                <p class="stat-label">Salaire manuel</p>
            </div>
        </div>
        <div class="stat-card border-green" data-reveal="auto" data-delay="4">
            <div class="stat-icon"><i class="fa-solid fa-coins"></i></div>
            <div class="stat-content">
                <p class="stat-value">{{ number_format($totalSalairesUSD ?? 0, 0, ',', ' ') }} $</p>
                <p class="stat-label">Total salaires (USD)</p>
            </div>
        </div>
        <div class="stat-card border-purple" data-reveal="auto" data-delay="5">
            <div class="stat-icon"><i class="fa-solid fa-coins"></i></div>
            <div class="stat-content">
                <p class="stat-value">{{ number_format($totalSalairesFC ?? 0, 0, ',', ' ') }} FC</p>
                <p class="stat-label">Total salaires (FC)</p>
            </div>
        </div>
    </div>

    {{-- ============ FILTRES ============ --}}
    <div class="filter-card" data-reveal="auto" data-delay="1">
        <button type="button" class="filter-toggle" id="filterToggle" aria-expanded="false" aria-controls="filterBody">
            <span class="filter-toggle-label">
                <i class="fa-solid fa-sliders"></i>
                Filtres
                @php
                    $activeFilters = collect(['search', 'type_salaire', 'avec_cours'])
                        ->filter(fn($k) => request()->filled($k))->count();
                @endphp
                @if($activeFilters > 0)
                    <span class="filter-badge">{{ $activeFilters }}</span>
                @endif
            </span>
            <i class="fa-solid fa-chevron-down filter-chevron"></i>
        </button>

        <div class="filter-body" id="filterBody">
            <form method="GET" action="{{ route('admin.salaires.index') }}" class="filter-form">
                <div class="filter-field">
                    <label class="filter-label" for="f-search">
                        <i class="fa-regular fa-user" aria-hidden="true"></i> Recherche
                    </label>
                    <div class="filter-input-wrap">
                        <i class="fa-solid fa-magnifying-glass filter-input-icon"></i>
                        <input type="text" name="search" id="f-search" class="filter-input"
                               placeholder="Nom ou email..."
                               value="{{ request('search') }}">
                    </div>
                </div>
                <div class="filter-field">
                    <label class="filter-label" for="f-type">
                        <i class="fa-regular fa-gear" aria-hidden="true"></i> Type de salaire
                    </label>
                    <select name="type_salaire" id="f-type" class="filter-select">
                        <option value="">Tous</option>
                        <option value="manuel" {{ request('type_salaire') == 'manuel' ? 'selected' : '' }}>Manuel</option>
                        <option value="automatique" {{ request('type_salaire') == 'automatique' ? 'selected' : '' }}>Automatique</option>
                    </select>
                </div>
                <div class="filter-field">
                    <label class="filter-label" for="f-cours">
                        <i class="fa-regular fa-book" aria-hidden="true"></i> Cours
                    </label>
                    <label class="checkbox-label" for="f-cours">
                        <input type="checkbox" name="avec_cours" id="f-cours" value="1"
                               class="form-check-input" {{ request('avec_cours') ? 'checked' : '' }}>
                        <span>Uniquement avec cours</span>
                    </label>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn-filter">
                        <i class="fa-solid fa-filter"></i> Filtrer
                    </button>
                    @if(request()->hasAny(['type_salaire', 'avec_cours', 'search']))
                        <a href="{{ route('admin.salaires.index') }}" class="btn-reset">
                            <i class="fa-solid fa-rotate-left"></i> Réinitialiser
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- ============ TABLEAU ============ --}}
    <div class="table-card" data-reveal="auto" data-delay="2">
        <div class="table-header">
            <div class="table-title">
                <i class="fa-solid fa-list title-icon-sm"></i>
                <span>Salaires enregistrés</span>
                <span class="badge-count">{{ $users->total() }}</span>
            </div>
            <div class="table-actions">
                <span class="text-muted text-sm">
                    <i class="fa-solid fa-arrow-right-arrow-left"></i>
                    Taux : <strong>{{ number_format($tauxChange ?? 2800, 2, ',', ' ') }} FC/USD</strong>
                </span>
            </div>
        </div>

        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Fonction</th>
                        <th>Section</th>
                        <th>Type salaire</th>
                        <th class="text-right">Salaire actuel</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        @php
                            $salaireUsd = match($user->type_salaire) {
                                'manuel' => $user->salaire_mensuel_usd ?? 0,
                                default => $user->salaire_ajuste_usd ?? $user->salaire_auto_base_usd ?? 0,
                            };
                            $salaireFc = match($user->type_salaire) {
                                'manuel' => $user->salaire_mensuel_fc ?? 0,
                                default => $user->salaire_ajuste_fc ?? $user->salaire_auto_base_fc ?? 0,
                            };
                            $typeLabel = $user->type_salaire === 'automatique' ? 'Automatique' : 'Manuel';
                            $badgeClass = $user->type_salaire === 'automatique' ? 'badge-blue' : 'badge-gray';
                            $avatar = strtoupper(substr($user->name, 0, 1));
                        @endphp
                        <tr>
                            <td data-label="Nom">
                                <div class="cell-user">
                                    <div class="avatar" aria-hidden="true">{{ $avatar }}</div>
                                    <span class="cell-primary">{{ $user->name }}</span>
                                </div>
                            </td>
                            <td data-label="Fonction">
                                <span class="cell-text">{{ $user->fonction->nom ?? '—' }}</span>
                            </td>
                            <td data-label="Section">
                                <span class="cell-text">{{ $user->section->nom ?? '—' }}</span>
                            </td>
                            <td data-label="Type salaire">
                                <span class="badge {{ $badgeClass }}">
                                    <i class="fa-solid {{ $user->type_salaire === 'automatique' ? 'fa-robot' : 'fa-user-pen' }}" aria-hidden="true"></i>
                                    {{ $typeLabel }}
                                </span>
                            </td>
                            <td data-label="Salaire actuel" class="text-right">
                                <div class="cell-money">
                                    <strong>{{ number_format($salaireUsd, 0, ',', ' ') }} $</strong>
                                    <span class="text-muted small">{{ number_format($salaireFc, 0, ',', ' ') }} FC</span>
                                </div>
                            </td>
                            <td data-label="Actions" class="text-center action-cell">
                                <div class="action-icons">
                                    <a href="{{ route('admin.salaires.edit', $user) }}" class="action-icon" title="Fixer / Modifier" aria-label="Fixer le salaire de {{ $user->name }}">
                                        <i class="fa-solid fa-coins"></i>
                                        <span class="action-label">Fixer</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="empty-row">
                            <td colspan="6" class="empty-cell">
                                <div class="empty-state">
                                    <i class="fa-regular fa-inbox"></i>
                                    <p>Aucun personnel trouvé.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($users->hasPages())
            <div class="pagination-container">
                {{ $users->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>

<style>
    /* ============================================================
       TOKENS
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

        max-width: 1400px;
        margin: 0 auto;
        padding: clamp(1rem, 2.5vw, 2rem) clamp(.75rem, 2vw, 1.25rem);
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    .index-container *,
    .index-container *::before,
    .index-container *::after { box-sizing: border-box; }

    .index-container h1,
    .index-container h2,
    .index-container h3 {
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
    @media (min-width: 768px) {
        .index-header {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }
    }

    .index-header-text { min-width: 0; flex: 1; }

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

    /* ✅ Actions de l'en-tête (Personnel + Avances) */
    .header-actions {
        display: flex;
        flex-direction: column;
        gap: .6rem;
        align-items: stretch;
    }
    @media (min-width: 640px) {
        .header-actions {
            flex-direction: row;
            align-items: center;
            flex-wrap: wrap;
        }
    }

    /* ============================================================
       BOUTONS
    ============================================================ */
    .btn-secondary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: .8rem 1.4rem;
        min-height: 46px;
        background: var(--c-white);
        border: 1.5px solid var(--c-gray-200);
        color: var(--c-ink-500);
        border-radius: var(--radius-md);
        font-family: inherit;
        font-weight: 600;
        font-size: .92rem;
        text-decoration: none;
        transition: all 400ms var(--ease-out-expo);
        white-space: nowrap;
        cursor: pointer;
    }
    .btn-secondary:hover {
        border-color: var(--c-primary);
        color: var(--c-primary);
        background: var(--c-gray-50);
        transform: translateY(-2px);
        box-shadow: 0 12px 28px rgba(99,102,241,.15);
    }
    .btn-secondary:active { transform: translateY(0); }

    /* ✅ Variante pour le bouton Avances (couleur violette) */
    .btn-avances {
        border-color: #ddd6fe;
        color: #7c3aed;
        background: #f5f3ff;
    }
    .btn-avances i { color: #7c3aed; }
    .btn-avances:hover {
        border-color: #7c3aed;
        color: #6d28d9;
        background: #ede9fe;
        box-shadow: 0 12px 28px rgba(124,58,237,.2);
    }

    /* ============================================================
       STATS
    ============================================================ */
    .stats-grid {
        display: grid;
        gap: clamp(.75rem, 1.5vw, 1.25rem);
        margin-bottom: clamp(1.25rem, 2.5vw, 1.75rem);
    }
    .stats-grid-5 { grid-template-columns: 1fr; }

    .stat-card {
        background: var(--c-white);
        padding: clamp(1rem, 2vw, 1.25rem) clamp(1rem, 2vw, 1.35rem);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
        display: flex;
        align-items: center;
        gap: clamp(.75rem, 1.5vw, 1rem);
        border-left: 4px solid;
        transition: transform 400ms var(--ease-out-expo), box-shadow 400ms var(--ease-out-expo);
        min-width: 0;
    }
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-md);
    }

    .border-indigo { border-left-color: #667eea; }
    .border-blue   { border-left-color: #3b82f6; }
    .border-gray   { border-left-color: #94a3b8; }
    .border-green  { border-left-color: #22c55e; }
    .border-purple { border-left-color: #a855f7; }

    .stat-icon {
        width: clamp(42px, 5vw, 48px);
        height: clamp(42px, 5vw, 48px);
        border-radius: var(--radius-md);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: clamp(1.05rem, 2vw, 1.2rem);
        flex-shrink: 0;
        transition: transform 400ms var(--ease-out-expo);
    }
    .stat-card:hover .stat-icon { transform: scale(1.08) rotate(-5deg); }

    .border-indigo .stat-icon { background: #eef2ff; color: #667eea; }
    .border-blue   .stat-icon { background: #dbeafe; color: #3b82f6; }
    .border-gray   .stat-icon { background: var(--c-gray-100); color: #64748b; }
    .border-green  .stat-icon { background: #f0fdf4; color: #22c55e; }
    .border-purple .stat-icon { background: #faf5ff; color: #a855f7; }

    .stat-content { min-width: 0; flex: 1; }

    .stat-label {
        font-size: clamp(.68rem, 1.2vw, .72rem);
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
        font-size: clamp(1.1rem, 2.2vw, 1.35rem);
        font-weight: 800;
        color: var(--c-ink-900);
        margin: 0 0 .1rem;
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
        margin-bottom: clamp(1.25rem, 2.5vw, 1.75rem);
        border: 1px solid var(--c-gray-100);
        overflow: hidden;
    }

    .filter-toggle {
        display: none;
        width: 100%;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        padding: .9rem 1.15rem;
        background: var(--c-gray-50);
        border: none;
        font-family: inherit;
        font-weight: 600;
        font-size: .95rem;
        color: var(--c-ink-700);
        cursor: pointer;
        transition: all 300ms var(--ease-soft);
        min-height: 48px;
    }
    .filter-toggle:hover { color: var(--c-primary); }
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

    .filter-body { padding: clamp(1rem, 2vw, 1.5rem); }

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
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .filter-label i { color: var(--c-primary); font-size: .75rem; }

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

    .checkbox-label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: .92rem;
        color: var(--c-ink-600);
        cursor: pointer;
        padding: .8rem 1rem;
        min-height: 46px;
        border: 1.5px solid var(--c-gray-200);
        border-radius: var(--radius-md);
        background: var(--c-gray-50);
        transition: all 300ms var(--ease-soft);
    }
    .checkbox-label:hover {
        border-color: var(--c-primary);
        background: var(--c-white);
    }
    .form-check-input {
        width: 18px;
        height: 18px;
        border: 1.5px solid #cbd5e1;
        border-radius: 4px;
        cursor: pointer;
        appearance: none;
        -webkit-appearance: none;
        position: relative;
        transition: all 0.2s;
        flex-shrink: 0;
        background: white;
    }
    .form-check-input:checked {
        background-color: var(--c-primary);
        border-color: var(--c-primary);
    }
    .form-check-input:checked::after {
        content: '✓';
        position: absolute;
        color: white;
        font-size: 12px;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-weight: 700;
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
        border: 1px solid var(--c-gray-100);
        overflow: hidden;
        width: 100%;
    }

    .table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid var(--c-gray-100);
        flex-wrap: wrap;
        gap: .5rem;
    }

    .table-title {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--c-ink-900);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .title-icon-sm { color: var(--c-primary); font-size: 1rem; }

    .badge-count {
        background: var(--c-gray-100);
        color: var(--c-ink-600);
        padding: .15rem .65rem;
        border-radius: var(--radius-full);
        font-size: .75rem;
        font-weight: 700;
    }

    .table-actions { font-size: .82rem; }
    .text-muted { color: var(--c-ink-400); }
    .text-sm { font-size: .8rem; }

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

    .data-table .text-right  { text-align: right; }
    .data-table .text-center { text-align: center; }

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

        .index-container .data-table thead th:nth-child(1) { width: 22%; }
        .index-container .data-table thead th:nth-child(2) { width: 14%; }
        .index-container .data-table thead th:nth-child(3) { width: 14%; }
        .index-container .data-table thead th:nth-child(4) { width: 14%; }
        .index-container .data-table thead th:nth-child(5) { width: 22%; }
        .index-container .data-table thead th:nth-child(6) { width: 14%; }
    }

    .cell-user { overflow: hidden; }
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

    .cell-user {
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
        min-width: 0;
    }

    .cell-money {
        display: flex;
        flex-direction: column;
        gap: .1rem;
    }
    .cell-money strong {
        color: var(--c-ink-900);
        font-weight: 700;
        font-size: .92rem;
    }
    .cell-money .small {
        font-size: .75rem;
        color: var(--c-ink-400);
        font-weight: 500;
    }

    .badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: .3rem .7rem;
        border-radius: var(--radius-full);
        font-size: .73rem;
        font-weight: 700;
        white-space: nowrap;
        line-height: 1.4;
    }
    .badge i { font-size: .7rem; }
    .badge-blue { background: #dbeafe; color: #1d4ed8; }
    .badge-gray { background: var(--c-gray-100); color: var(--c-ink-500); }

    /* ============================================================
       ACTIONS
    ============================================================ */
    .action-cell { white-space: nowrap; }

    .action-icons {
        display: inline-flex;
        justify-content: center;
        gap: .4rem;
    }

    .action-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .35rem;
        min-width: 36px;
        height: 36px;
        padding: 0 .6rem;
        border-radius: 10px;
        color: var(--c-ink-500);
        background: transparent;
        border: 1.5px solid transparent;
        cursor: pointer;
        transition: all 300ms var(--ease-soft);
        font-size: .9rem;
        text-decoration: none;
        font-weight: 600;
        flex-shrink: 0;
    }
    .action-icon:hover {
        background: #f5f4ff;
        color: var(--c-primary);
        border-color: #c7d2fe;
        transform: translateY(-1px);
    }

    .action-label { display: none; }

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
        color: #cbd5e1;
        margin-bottom: 1rem;
        display: block;
    }
    .empty-state p {
        font-size: .95rem;
        margin: 0;
    }

    /* ============================================================
       PAGINATION
    ============================================================ */
    .pagination-container {
        padding: 1rem 1.5rem;
        border-top: 1px solid var(--c-gray-100);
        display: flex;
        justify-content: center;
        overflow-x: auto;
    }

    /* ============================================================
       ANIMATIONS BIDIRECTIONNELLES
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
    .index-container [data-delay="5"] { transition-delay: 300ms; }

    @media (prefers-reduced-motion: reduce) {
        .index-container [data-reveal] {
            opacity: 1 !important;
            transform: none !important;
            filter: none !important;
        }
        *, *::before, *::after {
            animation-duration: 0.01ms !important;
            transition-duration: 0.01ms !important;
        }
        .stat-card:hover, .stat-card:hover .stat-icon,
        .btn-secondary:hover, .btn-filter:hover,
        .action-icon:hover,
        .data-table tbody tr:hover .avatar { transform: none; }
    }

    /* ============================================================
       RESPONSIVE — TABLETTE
    ============================================================ */
    @media (min-width: 640px) {
        .stats-grid-5 { grid-template-columns: repeat(2, 1fr); }

        .filter-form {
            grid-template-columns: repeat(2, 1fr);
            align-items: end;
        }
        .filter-actions {
            grid-column: 1 / -1;
            flex-direction: row;
            justify-content: flex-end;
        }
        .filter-actions > * { flex: 0 1 auto; }
    }

    @media (min-width: 768px) {
        .stats-grid-5 { grid-template-columns: repeat(3, 1fr); }
    }

    @media (min-width: 1024px) {
        .stats-grid-5 { grid-template-columns: repeat(5, 1fr); }

        .filter-form {
            grid-template-columns: 2fr 1fr 1.5fr auto;
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
       RESPONSIVE MOBILE (≤ 767px)
    ============================================================ */
    @media (max-width: 767px) {
        .filter-toggle { display: flex; }

        .filter-body {
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            padding-top: 0;
            padding-bottom: 0;
            transition:
                max-height 500ms var(--ease-out-expo),
                opacity 350ms ease,
                padding-top 350ms ease,
                padding-bottom 350ms ease;
        }
        .filter-body.is-open {
            max-height: 1200px;
            opacity: 1;
            padding-top: 1rem;
            padding-bottom: 1.25rem;
        }

        .filter-actions {
            flex-direction: row;
            gap: .5rem;
        }
        .filter-actions > * { flex: 1; }

        /* ✅ Boutons de l'en-tête en pleine largeur sur mobile */
        .header-actions {
            flex-direction: column;
        }
        .header-actions .btn-secondary {
            width: 100%;
        }

        .table-card {
            background: transparent;
            box-shadow: none;
            border: none;
            border-radius: 0;
            overflow: visible;
        }
        .table-header {
            background: var(--c-white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--c-gray-100);
            margin-bottom: 1rem;
            box-shadow: var(--shadow-sm);
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

        .data-table tbody tr {
            display: block;
            background: var(--c-white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--c-gray-100);
            margin-bottom: 1rem;
            padding: 1rem;
            transition: box-shadow 300ms var(--ease-soft), transform 300ms var(--ease-soft), border-color 300ms var(--ease-soft);
        }
        .data-table tbody tr:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
            border-color: #c7d2fe;
        }

        .data-table tbody td {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: .55rem 0 !important;
            border-bottom: 1px solid var(--c-gray-100);
            text-align: right;
            min-height: 42px;
        }
        .data-table tbody td:last-child { border-bottom: none; }

        .data-table tbody td::before {
            content: attr(data-label);
            font-weight: 700;
            color: var(--c-ink-400);
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            flex-shrink: 0;
            text-align: left;
        }

        .data-table td[data-label="Nom"] { justify-content: space-between; }
        .data-table td[data-label="Nom"]::before { order: 0; }
        .data-table td[data-label="Nom"] .cell-user {
            order: 1;
            justify-content: flex-end;
            margin-left: auto;
        }

        .data-table td[data-label="Salaire actuel"] {
            align-items: flex-start;
        }
        .data-table td[data-label="Salaire actuel"] .cell-money {
            align-items: flex-end;
            text-align: right;
        }

        .data-table td.action-cell {
            justify-content: flex-end;
            padding: .75rem 0 0 !important;
            border-top: 1.5px dashed var(--c-gray-200);
            margin-top: .5rem;
            min-height: auto;
        }
        .data-table td.action-cell::before { display: none; }

        .action-icons { width: 100%; justify-content: flex-end; }

        .action-icon {
            width: 42px;
            height: 42px;
            border-radius: 11px;
            background: var(--c-white);
            border-color: var(--c-gray-200);
            padding: 0 1rem;
        }
        .action-label { display: inline; font-size: .82rem; font-weight: 600; }

        .data-table tr.empty-row {
            background: var(--c-white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--c-gray-100);
            padding: 0;
        }
        .data-table tr.empty-row td { display: block; padding: 0 !important; border: none; }
        .data-table tr.empty-row td::before { display: none; }

        .cell-text, .cell-primary { text-align: right; }
        .badge-count { display: none; }
    }

    /* ============================================================
       RESPONSIVE — PETIT MOBILE (≤ 480px)
    ============================================================ */
    @media (max-width: 480px) {
        .index-container { padding-inline: .85rem; }

        .stats-grid-5 { grid-template-columns: repeat(2, 1fr); gap: .75rem; }
        .stat-card { padding: .85rem .9rem; gap: .65rem; }
        .stat-icon { width: 36px; height: 36px; font-size: 1rem; }
        .stat-value { font-size: 1rem; }
        .stat-label { font-size: .62rem; }

        .index-title { font-size: 1.3rem; }
        .index-subtitle { font-size: .82rem; }

        .btn-secondary {
            width: 100%;
            font-size: .88rem;
        }

        .filter-body { padding-inline: .85rem; }

        .data-table tbody tr { padding: .9rem; }

        .data-table tbody td {
            padding: .5rem 0 !important;
            font-size: .88rem;
            min-height: 40px;
        }
        .data-table tbody td::before { font-size: .64rem; }

        .avatar { width: 34px; height: 34px; font-size: .75rem; }
        .cell-primary { font-size: .92rem; }

        .badge { font-size: .68rem; padding: .25rem .6rem; }
        .badge i { display: none; }

        .action-icon { height: 40px; font-size: .82rem; }
    }

    @media (max-width: 360px) {
        .data-table tbody td {
            font-size: .84rem;
            padding: .4rem 0 !important;
            gap: .5rem;
        }
        .data-table tbody td::before { font-size: .6rem; }

        .stat-value { font-size: .95rem; }
    }

    /* ============================================================
       IMPRESSION
    ============================================================ */
    @media print {
        .index-container { padding: 0; max-width: 100%; }
        .btn-secondary, .header-actions, .filter-card,
        .action-cell, .pagination-container { display: none !important; }
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

        let lastY = window.scrollY;
        let scrollDir = 'down';

        window.addEventListener('scroll', () => {
            const y = window.scrollY;
            if (Math.abs(y - lastY) > 4) {
                scrollDir = y > lastY ? 'down' : 'up';
                lastY = y;
            }
        }, { passive: true });

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

        function initFilterToggle() {
            const toggle = document.getElementById('filterToggle');
            const body   = document.getElementById('filterBody');
            if (!toggle || !body) return;

            const mq = window.matchMedia('(max-width: 767px)');

            const handleViewport = () => {
                if (mq.matches) {
                    const hasActive = new URLSearchParams(window.location.search).toString().length > 0;
                    if (hasActive) {
                        body.classList.add('is-open');
                        toggle.setAttribute('aria-expanded', 'true');
                    } else {
                        body.classList.remove('is-open');
                        toggle.setAttribute('aria-expanded', 'false');
                    }
                } else {
                    body.classList.remove('is-open');
                    toggle.setAttribute('aria-expanded', 'false');
                }
            };

            toggle.addEventListener('click', () => {
                const isOpen = body.classList.toggle('is-open');
                toggle.setAttribute('aria-expanded', String(isOpen));
            });

            mq.addEventListener('change', handleViewport);
            handleViewport();
        }

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